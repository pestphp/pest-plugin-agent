@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
## Pest AI Plugin

- `vendor/bin/pest --ai="<code>"` runs a one-off assertion without creating a test file. The test is created, executed, and cleaned up automatically.
- **Important:** Always use `vendor/bin/pest`, never just `pest`, to avoid command not found errors.
- Always use fully qualified class names (e.g. `\App\Models\User`, `\Illuminate\Support\Facades\Mail`) since the generated test has no `use` imports.
- This tool is an assistant for quick verification, not a replacement for writing actual tests. Do not use `pest --ai` instead of creating proper test files.
- Use `pest --ai` only when you need to quickly verify a change works — browser rendering, a route response, a model relationship, etc.
- If the project lacks the necessary setup (factories, seeders, database migrations), do not use `pest --ai` to work around it. Ask the user to create the missing setup first.
- If you take screenshots with `pest --ai`, always delete the screenshot files afterward.

### Browser Testing Setup

- Before using `visit()` or any browser testing, ensure Pest's Browser Plugin is installed. If it is not, recommend installing it with the following commands:
  - `{{ $assist->composerCommand('require pestphp/pest-plugin-browser --dev') }}`
  - `{{ $assist->nodePackageManagerCommand('install playwright@latest') }}`
  - `npx playwright install`

### Database & RefreshDatabase

- If a `pest --ai` test needs database access and fails because migrations haven't run, check `tests/Pest.php` for a commented-out `RefreshDatabase` trait (e.g. `// uses(RefreshDatabase::class)->in('Feature');`).
- Before uncommenting it, ask the user if they are comfortable with it — only do so if the project is using an in-memory database (e.g. SQLite `:memory:`) for tests. If the project uses a persistent test database, uncommenting `RefreshDatabase` will wipe it on every run.

### Verifying Backend Changes

- Use `pest --ai` to quickly confirm backend changes work. Use factories to seed data within the test:

```
vendor/bin/pest --ai="$user = \App\Models\User::factory()->create(); expect($user->exists)->toBeTrue();"
```

```
vendor/bin/pest --ai="$post = \App\Models\Post::factory()->create(); expect($post->author)->not->toBeNull();"
```

```
vendor/bin/pest --ai="$user = \App\Models\User::factory()->create(); $response = $this->actingAs($user)->get('/api/users'); $response->assertStatus(200);"
```

### Verifying Frontend Changes

- After any frontend change (Blade templates, Livewire components, CSS, JavaScript), use `pest --ai` with browser testing to verify the result visually.
- You don't need absolute URLs in `visit()`. Just use the path (e.g. `visit('/dashboard')`) and Pest will resolve it.
- **Note:** Always provide a descriptive `filename:` to screenshots. Delete screenshot files after reviewing them.

Take screenshots to confirm the page renders correctly:

```
vendor/bin/pest --ai="visit('/')->screenshot(filename: 'homepage');"
vendor/bin/pest --ai="visit('/dashboard')->screenshot(filename: 'dashboard', fullPage: true);"
vendor/bin/pest --ai="visit('/')->screenshotElement('.hero', filename: 'hero-section');"
```

Assert page content and element state:

```
vendor/bin/pest --ai="visit('/')->assertSee('Welcome');"
vendor/bin/pest --ai="visit('/login')->assertPresent('input[name=email]');"
vendor/bin/pest --ai="visit('/')->assertVisible('.navbar');"
```

Test responsiveness by emulating devices:

```
vendor/bin/pest --ai="visit('/')->on()->mobile()->screenshot(filename: 'homepage-mobile');"
vendor/bin/pest --ai="visit('/')->on()->iPhone14Pro()->screenshot(filename: 'homepage-iphone14pro');"
vendor/bin/pest --ai="visit('/')->resize(375, 812)->screenshot(filename: 'homepage-375x812');"
```

Verify interactions work:

```
vendor/bin/pest --ai="visit('/')->click('Login')->assertPathIs('/login');"
vendor/bin/pest --ai="visit('/contact')->type('email', 'test@example.com')->press('Send')->assertSee('Message sent');"
```

Check for JavaScript errors and accessibility issues:

```
vendor/bin/pest --ai="visit('/')->assertNoJavaScriptErrors();"
vendor/bin/pest --ai="visit('/')->assertNoAccessibilityIssues();"
```

Visual regression testing to catch unintended UI changes:

```
vendor/bin/pest --ai="visit('/')->assertScreenshotMatches();"
```

### Combining Browser and Backend Assertions

- Combine browser interactions with backend assertions to verify side effects like emails, notifications, queued jobs, or database changes.
- **Important:** Always assert a frontend change first (e.g. `assertSee`, `assertPathIs`) to confirm the action was processed before checking backend side effects.

```
vendor/bin/pest --ai="\Illuminate\Support\Facades\Mail::fake(); visit('/contact')->type('email', 'test@example.com')->type('message', 'Hello')->press('Send')->assertSee('Message sent'); \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ContactForm::class);"
```

```
vendor/bin/pest --ai="\Illuminate\Support\Facades\Notification::fake(); visit('/register')->type('name', 'John')->type('email', 'john@example.com')->type('password', 'password')->press('Register')->assertPathIs('/dashboard'); \Illuminate\Support\Facades\Notification::assertSentTo(\App\Models\User::first(), \App\Notifications\WelcomeNotification::class);"
```

```
vendor/bin/pest --ai="visit('/checkout')->type('card', '4242424242424242')->press('Pay')->assertSee('Transaction processed'); expect(\App\Models\Order::count())->toBe(1);"
```
