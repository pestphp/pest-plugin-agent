---
name: pest-plugin-ai
description: One-shot Pest verification CLI for Laravel/PHP agents. Use whenever the user wants to quickly check that a change actually works — hitting a route, asserting a model relationship or factory, checking a queued job/mail/notification fires, screenshotting a page, asserting visible content, testing a click or form submission, checking for JavaScript errors, asserting accessibility, doing visual regression, or testing responsive layouts. Triggers include "verify this works", "did my change break X", "screenshot the homepage", "check this route returns 200", "make sure the mail fires", "test the login form", "see if the page renders", "check it on mobile", "is the form working", or any one-off behavioral check on a Laravel app that does not warrant a permanent test file. Also use after any Blade/Livewire/CSS/JS change to visually confirm the result. opening tinker for behavioral checks, hitting endpoints with curl, or eyeballing the browser yourself.
---

# pest-plugin-ai

`vendor/bin/pest --ai="<code>"` wraps `<code>` in `it('verify', function () { ... })`, runs it with the project's normal Pest config, and deletes the temp file. For quick verification only — not a substitute for real tests.

## Rules

- Use `vendor/bin/pest`, never bare `pest`.
- Fully qualify every class (no `use` imports exist): `\App\Models\User`, `\Illuminate\Support\Facades\Mail`.
- Don't use `--ai` to skip writing real tests, or to paper over missing factories/seeders/migrations — ask the user to add the setup.
- Delete screenshot files after reviewing.
- Quote with double quotes outside, escape `$` as `\$` so the shell doesn't eat variables.

## Backend

```bash
vendor/bin/pest --ai="\$user = \App\Models\User::factory()->create(); expect(\$user->exists)->toBeTrue();"
vendor/bin/pest --ai="\$user = \App\Models\User::factory()->create(); \$this->actingAs(\$user)->get('/api/users')->assertStatus(200);"
```

## Browser (after any Blade/Livewire/CSS/JS change)

Requires `pestphp/pest-plugin-browser` — if `visit()` is undefined, install:

```bash
composer require pestphp/pest-plugin-browser --dev
npm install playwright@latest && npx playwright install
```

Use relative paths in `visit()`. Always pass a descriptive `filename:` to screenshots.

```bash
vendor/bin/pest --ai="visit('/')->screenshot(filename: 'homepage');"
vendor/bin/pest --ai="visit('/dashboard')->screenshot(filename: 'dashboard', fullPage: true);"
vendor/bin/pest --ai="visit('/')->screenshotElement('.hero', filename: 'hero');"
vendor/bin/pest --ai="visit('/')->assertSee('Welcome');"
vendor/bin/pest --ai="visit('/login')->assertPresent('input[name=email]');"
vendor/bin/pest --ai="visit('/')->on()->iPhone14Pro()->screenshot(filename: 'home-iphone');"
vendor/bin/pest --ai="visit('/')->resize(375, 812)->screenshot(filename: 'home-375x812');"
vendor/bin/pest --ai="visit('/')->click('Login')->assertPathIs('/login');"
vendor/bin/pest --ai="visit('/contact')->type('email', 'a@b.com')->press('Send')->assertSee('Message sent');"
vendor/bin/pest --ai="visit('/')->assertNoJavaScriptErrors();"
vendor/bin/pest --ai="visit('/')->assertNoAccessibilityIssues();"
vendor/bin/pest --ai="visit('/')->assertScreenshotMatches();"
```

## Combining browser + backend

Drive the UI, then assert the side effect. Always assert a frontend signal first (`assertSee`, `assertPathIs`) so you know the action was processed.

```bash
vendor/bin/pest --ai="\Illuminate\Support\Facades\Mail::fake(); visit('/contact')->type('email', 'a@b.com')->type('message', 'Hi')->press('Send')->assertSee('Message sent'); \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ContactForm::class);"
vendor/bin/pest --ai="visit('/checkout')->type('card', '4242424242424242')->press('Pay')->assertSee('Transaction processed'); expect(\App\Models\Order::count())->toBe(1);"
```

## RefreshDatabase

If a check fails on missing tables, look in `tests/Pest.php` for a commented `// uses(RefreshDatabase::class)->in('Feature');`. Don't uncomment it without asking — it wipes the test DB every run unless it's SQLite `:memory:`.

## Pitfalls

- **`use` inside the snippet is invalid.** The code runs inside a closure body — namespace imports must happen at file top, which you don't control. Always FQCN.
- **`__DIR__` / `__FILE__` resolve to `/tmp`**, not your tests folder. Don't read fixtures by relative path; pass absolute paths or use `base_path()` / `storage_path()`.
- **One `--ai` per invocation.** You can't chain multiple verifications in a single command. Run them separately.
- **Every failure reports the test name as `verify`.** If you batch checks into one snippet, the failure won't tell you which one broke. Keep snippets focused on a single behavior.
- **Traits can't be added inline.** `RefreshDatabase`, `WithFaker`, etc. must be wired through `tests/Pest.php` `uses()`; the snippet inherits whatever is already configured.
- **Screenshots persist on failure too.** A failed assertion still leaves the PNG in the project root. Sweep them up regardless of outcome.
- **Shell escaping bites.** Backticks, `!` (in zsh), and `$` will all be interpreted before PHP sees them. Escape `$` as `\$`, avoid `!`, and prefer single quotes inside the snippet (`'foo'`) over double.
