---
name: pest-plugin-ai
description: One-shot Pest verification CLI for Laravel and PHP agents. Use whenever the user wants to quickly check that a change actually works, including hitting a route, asserting a model relationship or factory, checking a queued job, mail, or notification fires, screenshotting a page, asserting visible content, testing a click or form submission, checking for JavaScript errors, asserting accessibility, doing visual regression, or testing responsive layouts. Triggers include "verify this works", "did my change break X", "screenshot the homepage", "check this route returns 200", "make sure the mail fires", "test the login form", "see if the page renders", "check it on mobile", "is the form working", or any one-off behavioral check on a Laravel app that does not warrant a permanent test file. Also use after any Blade, Livewire, CSS, or JS change to visually confirm the result. Prefer `vendor/bin/pest --ai="<code>"` over writing throwaway test files.
---

# pest-plugin-ai

One-shot Pest verification for AI agents. Wrap any PHP snippet in `vendor/bin/pest --ai="<code>"`. Pest creates a temporary test, runs it, and deletes it. The snippet lives inside `it('verify', function () { ... })`, so use Pest's expectation API and any helpers available in the test suite (`visit()`, `actingAs()`, `Mail::fake()`, factories, and so on).

## How it works

`pest --ai="<code>"` writes a temp file shaped like this:

```php
<?php

it('verify', function () {
    /* your snippet goes here */
});
```

It then runs with the project's normal Pest configuration (Feature and Browser namespace `uses`, traits applied via `tests/Pest.php`) and cleans up afterwards. The file has **no `use` imports**, so every class must be fully qualified.

## Critical rules

- **The snippet must be valid PHP, not natural language.** `--ai="visit '/' and check it works"` is a parse error. Translate the user's request into PHP statements (`visit('/')->assertOk();`) before invoking.
- **Use `vendor/bin/pest`, never bare `pest`.** The bare command often is not on `PATH` and produces "command not found" instead of a real result.
- **Fully qualify every class name:** `\App\Models\User`, `\Illuminate\Support\Facades\Mail`, `\App\Notifications\WelcomeNotification`. The generated test has no `use` statements, so unqualified names throw `Class "User" not found`.
- **Use the documented browser API exactly.** Methods like `onMobile()` or `mobileView()` do not exist — the chain is `->on()->mobile()`, `->on()->iPhone14Pro()`, or `->resize(w, h)`. If a method is not shown in this skill, do not invent it.
- **Do not replace real tests with `--ai`.** This is a verification probe, not a way to skip writing tests. If the behavior is worth a regression guard, write a proper test file.
- **Do not paper over missing setup.** If a check fails because a factory, seeder, or migration is missing, stop and ask the user to add it. Do not bend `--ai` invocations into fixtures.
- **Delete screenshots after reviewing them.** They land in the project root and clutter the repo if left behind.
- **Quote the snippet so the shell preserves PHP syntax.** Use double quotes outside, single quotes inside, and escape `$` as `\$` so the shell does not interpolate variables.

## Backend verification

Seed state with factories inside the snippet. Do not rely on existing data.

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

## Frontend and browser verification

Browser features come from `pestphp/pest-plugin-browser`. Full API reference: https://pestphp.com/docs/browser-testing. If `visit()` is undefined, install it first:

```bash
composer require pestphp/pest-plugin-browser --dev
npm install playwright@latest
npx playwright install
```

Use relative paths in `visit()`. Pest resolves them against the app URL. Always pass a descriptive `filename:` to screenshots so the file is easy to locate (and delete) afterwards. After any Blade, Livewire, CSS, or JS change, reach for these to visually confirm the result.

**`screenshot()`'s first positional argument is `$fullPage` (bool), not the filename.** `screenshot('/tmp/foo.png')` throws `Argument #1 ($fullPage) must be of type bool, string given`. Always pass the filename as a named argument: `screenshot(filename: 'foo')`. You cannot redirect screenshots to an arbitrary path — they always land in the project root with the given filename.

### Smoke screenshots

Screenshot API reference: https://pestphp.com/docs/browser-testing#screenshot

```bash
vendor/bin/pest --ai="visit('/')->screenshot(filename: 'homepage');"
vendor/bin/pest --ai="visit('/dashboard')->screenshot(filename: 'dashboard', fullPage: true);"
vendor/bin/pest --ai="visit('/')->screenshotElement('.hero', filename: 'hero-section');"
```

### Content and element assertions

```bash
vendor/bin/pest --ai="visit('/')->assertSee('Welcome');"
vendor/bin/pest --ai="visit('/login')->assertPresent('input[name=email]');"
vendor/bin/pest --ai="visit('/')->assertVisible('.navbar');"
```

### Responsive checks

Emulate a device or set an explicit viewport:

```bash
vendor/bin/pest --ai="visit('/')->on()->mobile()->screenshot(filename: 'home-mobile');"
vendor/bin/pest --ai="visit('/')->on()->iPhone14Pro()->screenshot(filename: 'home-iphone14pro');"
vendor/bin/pest --ai="visit('/')->resize(375, 812)->screenshot(filename: 'home-375x812');"
```

### Interaction flows

```bash
vendor/bin/pest --ai="visit('/')->click('Login')->assertPathIs('/login');"
vendor/bin/pest --ai="visit('/contact')->type('email', 'test@example.com')->press('Send')->assertSee('Message sent');"
```

### Health checks

JavaScript errors, accessibility, and visual drift:

```bash
vendor/bin/pest --ai="visit('/')->assertNoJavaScriptErrors();"
vendor/bin/pest --ai="visit('/')->assertNoAccessibilityIssues();"
vendor/bin/pest --ai="visit('/')->assertScreenshotMatches();"
```

## Combining browser and backend

Drive the UI, then assert the side effect. Always assert a frontend signal first (`assertSee`, `assertPathIs`) so you know the action was processed before checking what it touched on the backend.

```bash
vendor/bin/pest --ai="\Illuminate\Support\Facades\Mail::fake(); visit('/contact')->type('email', 'test@example.com')->type('message', 'Hello')->press('Send')->assertSee('Message sent'); \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ContactForm::class);"
```

```bash
vendor/bin/pest --ai="\Illuminate\Support\Facades\Notification::fake(); visit('/register')->type('name', 'John')->type('email', 'john@example.com')->type('password', 'password')->press('Register')->assertPathIs('/dashboard'); \Illuminate\Support\Facades\Notification::assertSentTo(\App\Models\User::first(), \App\Notifications\WelcomeNotification::class);"
```

```bash
vendor/bin/pest --ai="visit('/checkout')->type('card', '4242424242424242')->press('Pay')->assertSee('Transaction processed'); expect(\App\Models\Order::count())->toBe(1);"
```

## Database and RefreshDatabase

If a check fails with "no such table" or similar, look in `tests/Pest.php` for a commented `RefreshDatabase` line, for example `// uses(RefreshDatabase::class)->in('Feature');`.

**Do not silently uncomment it.** Ask the user first. If the project's test database is persistent (anything other than SQLite `:memory:`), enabling `RefreshDatabase` wipes it on every run. Confirm the test database is in-memory or otherwise expendable before flipping the switch.

## Pitfalls

- **`use` inside the snippet is invalid.** The code runs inside a closure body, so namespace imports must happen at file top, which you do not control. Always use fully qualified class names.
- **`__DIR__` and `__FILE__` resolve to `/tmp`**, not the tests folder. Do not read fixtures by relative path. Pass absolute paths or use `base_path()` and `storage_path()`.
- **One `--ai` per invocation.** Multiple verifications cannot be chained in a single command. Run them separately.
- **Every failure reports the test name as `verify`.** If you batch checks into one snippet, the failure will not tell you which one broke. Keep snippets focused on a single behavior.
- **Traits cannot be added inline.** `RefreshDatabase`, `WithFaker`, and similar traits must be wired through `tests/Pest.php` `uses()`. The snippet inherits whatever is already configured.
- **Browser tests need a reachable app.** `visit('/foo')` hits the configured app URL, so make sure `php artisan serve` (or your usual dev server) is running, or the browser plugin's built-in server is configured.
- **Screenshots persist on failure too.** A failed assertion still leaves the PNG in the project root. Sweep them up regardless of outcome.
- **Shell escaping bites.** Backticks, `!` (in zsh history), and `$` are all interpreted before PHP sees them. Escape `$` as `\$`, avoid `!`, and prefer single quotes inside the snippet (`'foo'`) over double.

## When NOT to use

- The behavior deserves a permanent regression guard. Write a real test file in `tests/Feature` or `tests/Browser` instead.
- The check needs more than roughly three statements or any helper function. Long shell-quoted snippets are painful to read and edit; write a real test file.
- The user is asking for a fix or refactor, not a verification. Use the appropriate edit and test workflow, not `--ai`.
