---
name: pest-plugin-ai
description: One-shot Pest verification CLI for Laravel/PHP agents. Use whenever the user wants to quickly check that a change actually works — hitting a route, asserting a model relationship or factory, checking a queued job/mail/notification fires, screenshotting a page, asserting visible content, testing a click or form submission, checking for JavaScript errors, asserting accessibility, doing visual regression, or testing responsive layouts. Triggers include "verify this works", "did my change break X", "screenshot the homepage", "check this route returns 200", "make sure the mail fires", "test the login form", "see if the page renders", "check it on mobile", "is the form working", or any one-off behavioral check on a Laravel app that does not warrant a permanent test file. Also use after any Blade/Livewire/CSS/JS change to visually confirm the result. opening tinker for behavioral checks, hitting endpoints with curl, or eyeballing the browser yourself.
---

# pest-plugin-ai

One-shot Pest verification for AI agents. Wrap any PHP snippet in
`vendor/bin/pest --ai="<code>"` and Pest creates a temporary test, runs it,
and deletes it. The snippet lives inside `it('verify', function () { ... })`,
so use Pest's expectation API and any helpers available in the test suite
(`visit()`, `actingAs()`, `Mail::fake()`, factories, etc.).

## How it works

`pest --ai="<code>"` writes a temp file like:

```php
<?php
it('verify', function () {
    <code>
});
```

Then runs it with the project's normal Pest configuration (Feature/Browser
namespace `uses`, traits applied via `tests/Pest.php`, etc.) and cleans up
afterwards. Because the file has **no `use` imports**, every class must be
fully qualified.

## Critical rules

- **Always use `vendor/bin/pest`**, never bare `pest`. The bare command often
  isn't on `PATH` and you'll get "command not found" instead of a real result.
- **Always fully qualify class names**: `\App\Models\User`, `\Illuminate\Support\Facades\Mail`,
  `\App\Notifications\WelcomeNotification`. The generated test has no
  `use` statements, so unqualified names will throw `Class "User" not found`.
- **Don't replace real tests with `--ai`**. This is a verification probe, not a
  way to skip writing tests. If the behavior is worth keeping a regression
  guard on, write a proper test file.
- **Don't paper over missing setup.** If a check fails because a factory,
  seeder, or migration is missing, stop and ask the user to add it. Don't
  bend `--ai` invocations into fixtures.
- **Delete screenshots after reviewing them.** They land in the project root
  and clutter the repo if left behind.
- **Quote the snippet so the shell preserves PHP syntax.** Use double quotes
  on the outside and single quotes inside (or escape `$` as `\$` if you need
  shell interpolation suppressed).

## Backend verification

Use factories to seed state inside the snippet — don't rely on existing data.

```bash
vendor/bin/pest --ai="\$user = \App\Models\User::factory()->create(); expect(\$user->exists)->toBeTrue();"
```

```bash
vendor/bin/pest --ai="\$post = \App\Models\Post::factory()->create(); expect(\$post->author)->not->toBeNull();"
```

```bash
vendor/bin/pest --ai="\$user = \App\Models\User::factory()->create(); \$response = \$this->actingAs(\$user)->get('/api/users'); \$response->assertStatus(200);"
```

Mail, notifications, and queued jobs work via the standard fakes:

```bash
vendor/bin/pest --ai="\Illuminate\Support\Facades\Mail::fake(); \App\Models\User::factory()->create()->notify(new \App\Notifications\Welcome()); \Illuminate\Support\Facades\Notification::assertSentTo(...);"
```

## Frontend / browser verification

Browser features come from `pestphp/pest-plugin-browser`. If `visit()` is not
defined, install it before continuing:

```bash
composer require pestphp/pest-plugin-browser --dev
npm install playwright@latest
npx playwright install
```

Use relative paths in `visit()` — Pest resolves them against the app URL.
Always pass a descriptive `filename:` to screenshots so the file is easy to
locate (and delete) afterwards. After any Blade/Livewire/CSS/JS change,
reach for these to visually confirm the result.

**Smoke screenshots** to confirm a page renders:

```bash
vendor/bin/pest --ai="visit('/')->screenshot(filename: 'homepage');"
vendor/bin/pest --ai="visit('/dashboard')->screenshot(filename: 'dashboard', fullPage: true);"
vendor/bin/pest --ai="visit('/')->screenshotElement('.hero', filename: 'hero-section');"
```

**Content / element assertions**:

```bash
vendor/bin/pest --ai="visit('/')->assertSee('Welcome');"
vendor/bin/pest --ai="visit('/login')->assertPresent('input[name=email]');"
vendor/bin/pest --ai="visit('/')->assertVisible('.navbar');"
```

**Responsive checks** via device emulation or explicit viewport:

```bash
vendor/bin/pest --ai="visit('/')->on()->mobile()->screenshot(filename: 'home-mobile');"
vendor/bin/pest --ai="visit('/')->on()->iPhone14Pro()->screenshot(filename: 'home-iphone14pro');"
vendor/bin/pest --ai="visit('/')->resize(375, 812)->screenshot(filename: 'home-375x812');"
```

**Interaction flows**:

```bash
vendor/bin/pest --ai="visit('/')->click('Login')->assertPathIs('/login');"
vendor/bin/pest --ai="visit('/contact')->type('email', 'test@example.com')->press('Send')->assertSee('Message sent');"
```

**Health checks** for JS errors, accessibility, and visual drift:

```bash
vendor/bin/pest --ai="visit('/')->assertNoJavaScriptErrors();"
vendor/bin/pest --ai="visit('/')->assertNoAccessibilityIssues();"
vendor/bin/pest --ai="visit('/')->assertScreenshotMatches();"
```

## Combining browser and backend

The most powerful pattern: drive the UI, then assert the side effect. Always
assert a frontend signal first (`assertSee`, `assertPathIs`) so you know the
action was processed before checking what it touched on the backend.

```bash
vendor/bin/pest --ai="\Illuminate\Support\Facades\Mail::fake(); visit('/contact')->type('email', 'test@example.com')->type('message', 'Hello')->press('Send')->assertSee('Message sent'); \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ContactForm::class);"
```

```bash
vendor/bin/pest --ai="\Illuminate\Support\Facades\Notification::fake(); visit('/register')->type('name', 'John')->type('email', 'john@example.com')->type('password', 'password')->press('Register')->assertPathIs('/dashboard'); \Illuminate\Support\Facades\Notification::assertSentTo(\App\Models\User::first(), \App\Notifications\WelcomeNotification::class);"
```

```bash
vendor/bin/pest --ai="visit('/checkout')->type('card', '4242424242424242')->press('Pay')->assertSee('Transaction processed'); expect(\App\Models\Order::count())->toBe(1);"
```

## Database & RefreshDatabase

If a check fails with "no such table" or similar, look in `tests/Pest.php`
for a commented `RefreshDatabase` line, e.g.
`// uses(RefreshDatabase::class)->in('Feature');`.

**Do not silently uncomment it.** Ask the user first. If the project's test
database is persistent (anything other than SQLite `:memory:`), enabling
`RefreshDatabase` will wipe it on every run. Confirm the test DB is in-memory
or otherwise expendable before flipping the switch.

## Pitfalls

- **`use` inside the snippet is invalid.** The code runs inside a closure body — namespace imports must happen at file top, which you don't control. Always FQCN.
- **`__DIR__` / `__FILE__` resolve to `/tmp`**, not your tests folder. Don't read fixtures by relative path; pass absolute paths or use `base_path()` / `storage_path()`.
- **One `--ai` per invocation.** You can't chain multiple verifications in a single command. Run them separately.
- **Every failure reports the test name as `verify`.** If you batch checks into one snippet, the failure won't tell you which one broke. Keep snippets focused on a single behavior.
- **Traits can't be added inline.** `RefreshDatabase`, `WithFaker`, etc. must be wired through `tests/Pest.php` `uses()`; the snippet inherits whatever is already configured.
- **Browser tests need a reachable app.** `visit('/foo')` hits the configured app URL — make sure `php artisan serve` (or your usual dev server) is running, or the browser plugin's built-in server is configured.
- **Screenshots persist on failure too.** A failed assertion still leaves the PNG in the project root. Sweep them up regardless of outcome.
- **Shell escaping bites.** Backticks, `!` (in zsh), and `$` will all be interpreted before PHP sees them. Escape `$` as `\$`, avoid `!`, and prefer single quotes inside the snippet (`'foo'`) over double.

## When NOT to use

- The behavior deserves a permanent regression guard → write a real test file
  in `tests/Feature` or `tests/Browser`.
- The check needs more than ~3 statements or any helper function → write a
  real test file; long shell-quoted snippets are painful to read and edit.
- The user is asking for a fix or refactor, not a verification → use the
  appropriate edit/test workflow, not `--ai`.
