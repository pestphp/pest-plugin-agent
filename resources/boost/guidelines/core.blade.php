@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
## Pest AI Plugin

- Use `vendor/bin/pest --ai="<code>"` to verify that generated code actually works. Run assertions on-the-fly without creating test files.
- Always use `vendor/bin/pest --ai` after making code changes to confirm the changes behave as expected.
- The `--ai` flag creates a temporary test, executes it, and cleans up automatically.
- **Important:** Always use `vendor/bin/pest`, never just `pest`, to avoid command not found errors.
- Always use fully qualified class names (e.g. `\App\Models\User`, `\Illuminate\Support\Facades\Mail`) since the generated test has no `use` imports.

### Browser Testing Setup

- Before using `visit()` or any browser testing, ensure Pest's Browser Plugin is installed. If it is not, recommend installing it with the following commands:
  - `{{ $assist->composerCommand('require pestphp/pest-plugin-browser --dev') }}`
  - `{{ $assist->nodePackageManagerCommand('install playwright@latest') }}`
  - `npx playwright install`

### Verifying Backend Changes

- After creating or modifying models, routes, or logic, verify with `vendor/bin/pest --ai`. Use factories to seed data within the test:
  - `vendor/bin/pest --ai="\$user = \App\Models\User::factory()->create(); expect(\$user->exists)->toBeTrue();"`
  - `vendor/bin/pest --ai="\$post = \App\Models\Post::factory()->create(); expect(\$post->author)->not->toBeNull();"`
  - `vendor/bin/pest --ai="\$user = \App\Models\User::factory()->create(); \$response = \actingAs(\$user)->get('/api/users'); \$response->assertStatus(200);"`

### Verifying Frontend Changes (IMPORTANT)

- After any frontend change (Blade templates, Livewire components, CSS, JavaScript), use `vendor/bin/pest --ai` with browser testing to verify the result visually.
- You don't need the absolute URL in `visit()`. Just use the path (e.g. `visit('/dashboard')`) and Pest will resolve it correctly.
- Take screenshots to confirm the page renders correctly.
  - **Note:** Always provide a descriptive `filename:` to screenshots.
  - `vendor/bin/pest --ai="\visit('/')->screenshot(filename: 'homepage');"`
  - `vendor/bin/pest --ai="\visit('/dashboard')->screenshot(filename: 'dashboard', fullPage: true);"`
  - `vendor/bin/pest --ai="\visit('/')->screenshotElement('.hero', filename: 'hero-section');"`
- Assert page content and element state:
  - `vendor/bin/pest --ai="\visit('/')->assertSee('Welcome');"`
  - `vendor/bin/pest --ai="\visit('/login')->assertPresent('input[name=email]');"`
  - `vendor/bin/pest --ai="\visit('/')->assertVisible('.navbar');"`
- Test responsiveness by emulating devices:
  - `vendor/bin/pest --ai="\visit('/')->on()->mobile()->screenshot(filename: 'homepage-mobile');"`
  - `vendor/bin/pest --ai="\visit('/')->on()->iPhone14Pro()->screenshot(filename: 'homepage-iphone14pro');"`
  - `vendor/bin/pest --ai="\visit('/')->resize(375, 812)->screenshot(filename: 'homepage-375x812');"`
- Verify interactions work:
  - `vendor/bin/pest --ai="\visit('/')->click('Login')->assertPathIs('/login');"`
  - `vendor/bin/pest --ai="\visit('/contact')->type('email', 'test@example.com')->press('Send')->assertSee('Message sent');"`
- Check for JavaScript errors and accessibility issues:
  - `vendor/bin/pest --ai="\visit('/')->assertNoJavaScriptErrors();"`
  - `vendor/bin/pest --ai="\visit('/')->assertNoAccessibilityIssues();"`
- Use visual regression testing to catch unintended UI changes:
  - `vendor/bin/pest --ai="\visit('/')->assertScreenshotMatches();"`
- Combine browser interactions with backend assertions to verify side effects like emails, notifications, queued jobs, or database changes.
  - **Important:** Always assert a frontend change first (e.g. `assertSee`, `assertPathIs`) to confirm the action was processed before checking backend side effects.
  - `vendor/bin/pest --ai="\Illuminate\Support\Facades\Mail::fake(); \visit('/contact')->type('email', 'test@example.com')->type('message', 'Hello')->press('Send')->assertSee('Message sent'); \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ContactForm::class);"`
  - `vendor/bin/pest --ai="\Illuminate\Support\Facades\Notification::fake(); \visit('/register')->type('name', 'John')->type('email', 'john@example.com')->type('password', 'password')->press('Register')->assertPathIs('/dashboard'); \Illuminate\Support\Facades\Notification::assertSentTo(\App\Models\User::first(), \App\Notifications\WelcomeNotification::class);"`
  - `vendor/bin/pest --ai="\Illuminate\Support\Facades\Queue::fake(); \visit('/export')->press('Export CSV')->assertSee('Export started'); \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\ExportCsv::class);"`
  - `vendor/bin/pest --ai="\visit('/checkout')->type('card', '4242424242424242')->press('Pay')->assertSee('Transaction processed'); expect(\App\Models\Order::count())->toBe(1);"`
