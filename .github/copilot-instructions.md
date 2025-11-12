<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Project-Specific Guidelines

### Team Background & Preferences
- The development team has a **Bootstrap CSS background** and is still learning **Tailwind CSS**.
- Prioritize code simplicity and reusability to help the team understand and maintain the codebase.
- When making UI changes, provide brief explanations of Tailwind utility classes when they differ significantly from Bootstrap approaches.

### Form Components (Critical)
- **Always use existing Blade components** when creating forms instead of writing raw HTML with Tailwind classes.
- Check `resources/views/components/` directory for available components before creating new ones:
  - `<x-input>` - Text input fields
  - `<x-select>` - Dropdown/select fields
  - `<x-textarea>` - Textarea fields
  - `<x-button>` - Buttons (primary, secondary, danger, etc.)
  - `<x-card>` - Card containers
  - `<x-badge>` - Status badges
  - `<x-alert>` - Alert messages
  - `<x-stat-card>` - Statistics display cards
  - `<x-icon>` - SVG icons (39 icons available)
  - `<x-sync.*>` - Synchronization components (status-badge, progress-bar, stat-card)
- **Component flexibility**: You may edit existing components to add new features or variants, but ensure:
  - Components remain flexible and reusable
  - Components maintain simplicity (no over-engineering)
  - Default behavior is preserved unless explicitly changed
  - New props/attributes are optional and well-documented with inline comments

### Icon Component (Critical)
- **NEVER use inline SVG** - always use the centralized `<x-icon>` component
- **Icon component location**: `resources/views/components/icon.blade.php`
- **Available icons**: 39 icons across 5 categories:
  - Common (13): refresh, check-circle, x-circle, spinner, chevron-down, clipboard, clock, cog, chart, check, x, exclamation, info
  - Dashboard (3): shopping-bag, currency-dollar, cube
  - Action (4): plus, pencil, trash, inbox
  - Job/Data (10): academic-cap, users, book-open, user-group, clipboard-list, trophy, document-text, shield-check, beaker
  - Navigation (11): menu-bars, search, sun, moon, bell, user, logout, home, database, arrow-right, document-chart
- **Usage patterns**:
  ```blade
  <!-- Basic usage -->
  <x-icon name="refresh" size="5" />
  
  <!-- With custom classes -->
  <x-icon name="user" size="5" class="text-blue-600 dark:text-blue-400" />
  
  <!-- With Alpine.js directives -->
  <x-icon name="sun" size="5" x-show="darkMode" />
  <x-icon name="moon" size="5" x-show="!darkMode" />
  ```
- **Available sizes**: `size="3"`, `size="4"`, `size="5"` (default), `size="6"`, `size="8"`
- **Adding new icons**: Add to `$icons` array in `icon.blade.php` component

### Code Refactoring Best Practices
- **Replace inline SVG with icon component**: Use `<x-icon>` instead of copy-pasting SVG code
- **Extract repeated patterns into components**: If you see similar HTML blocks 3+ times, create a component
- **Keep backups**: Create `.backup` files before major refactoring (e.g., `navbar.blade.php.backup`)
- **Run Pint after changes**: Always run `vendor/bin/pint --dirty` after editing Blade files
- **Document refactoring**: Update or create documentation in `docs/features/` for significant refactoring
- **Check for existing components first**: Before creating new components, search `resources/views/components/` directory

### Testing Organization
- **Manual tests** (user-driven test scenarios, exploratory testing notes) go in `tests/Manual/` directory
- **Automated tests** (Pest/PHPUnit) remain in `tests/Feature/` and `tests/Unit/` directories
- Manual test files should be markdown format describing test steps, expected results, and actual results

### Documentation Organization
- **All documentation** must be created in the `docs/` directory
- Use clear, hierarchical structure within `docs/`:
  - `docs/setup/` - Installation and configuration guides
  - `docs/features/` - Feature documentation
  - `docs/api/` - API documentation
  - `docs/architecture/` - System architecture and design decisions
  - `docs/deployment/` - Deployment and DevOps guides
- Documentation files must be in **Markdown format**
- Include code examples and screenshots where applicable
- Keep documentation up-to-date when making related code changes

### Directory Structure Rules
- **Never create** `tests/Manual/` or `docs/` subdirectories without user approval for new categories
- **Always check** if similar documentation/manual tests exist before creating new files
- Follow existing naming conventions in each directory

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3.18
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- livewire/livewire (LIVEWIRE) - v3
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Docker Environment (Critical)
- **This project runs on Docker** - all commands must be executed inside Docker containers
- **Never run commands directly** on host machine (e.g., `npm run build`, `php artisan`, `composer`)
- **Always use Docker Compose** to execute commands in appropriate containers

### Docker Command Patterns

**Frontend/Node Commands:**
```bash
# ❌ WRONG - Don't run on host
npm run build
npm run dev
npm install

# ✅ CORRECT - Run in Docker node container
docker compose exec node npm run build
docker compose exec node npm run dev
docker compose exec node npm install
```

**PHP/Artisan Commands:**
```bash
# ❌ WRONG
php artisan migrate
composer install

# ✅ CORRECT - Run in Docker app container
docker compose exec app php artisan migrate
docker compose exec app composer install
```

**When to Rebuild Assets:**
- After modifying Blade files with new Tailwind classes
- After adding/removing components
- After changing JavaScript/CSS in `resources/` folder
- When you see "Vite manifest" errors
- **Command:** `docker compose exec node npm run build`

### Available Docker Services
- `app` - PHP-FPM container (Laravel application)
- `web` - Nginx container (web server)
- `node` - Node.js container (frontend build tools)

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, run `docker compose exec node npm run build`
- For development with auto-reload: `docker compose exec node npm run dev`
- All npm commands must run inside Docker `node` container

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] <name>` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, run `docker compose exec node npm run build` to rebuild assets.
- For development with auto-reload, use `docker compose exec node npm run dev`.


=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.


=== livewire/core rules ===

## Livewire Core
- Use the `search-docs` tool to find exact version specific documentation for how to write Livewire & Livewire tests.
- Use the `php artisan make:livewire [Posts\CreatePost]` artisan command to create new components
- State should live on the server, with the UI reflecting it.
- All Livewire requests hit the Laravel backend, they're like regular HTTP requests. Always validate form data, and run authorization checks in Livewire actions.

## Livewire Best Practices
- Livewire components require a single root element.
- Use `wire:loading` and `wire:dirty` for delightful loading states.
- Add `wire:key` in loops:

    ```blade
    @foreach ($items as $item)
        <div wire:key="item-{{ $item->id }}">
            {{ $item->name }}
        </div>
    @endforeach
    ```

- Prefer lifecycle hooks like `mount()`, `updatedFoo()` for initialization and reactive side effects:

<code-snippet name="Lifecycle hook examples" lang="php">
    public function mount(User $user) { $this->user = $user; }
    public function updatedSearch() { $this->resetPage(); }
</code-snippet>


## Testing Livewire

<code-snippet name="Example Livewire component test" lang="php">
    Livewire::test(Counter::class)
        ->assertSet('count', 0)
        ->call('increment')
        ->assertSet('count', 1)
        ->assertSee(1)
        ->assertStatus(200);
</code-snippet>


    <code-snippet name="Testing a Livewire component exists within a page" lang="php">
        $this->get('/posts/create')
        ->assertSeeLivewire(CreatePost::class);
    </code-snippet>


=== livewire/v3 rules ===

## Livewire 3

### Component Structure
- This project uses **Class-based Livewire components** (NOT Volt).
- All Livewire components must be created as separate PHP class files in `app/Livewire/` directory.
- Component views must be in `resources/views/livewire/` directory.
- Use `php artisan make:livewire ComponentName` to create new components.

### Class-based Component Example

<code-snippet name="Standard Livewire Component Class" lang="php">
<?php

namespace App\Livewire;

use Livewire\Component;

class Counter extends Component
{
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }

    public function decrement(): void
    {
        $this->count--;
    }

    public function render()
    {
        return view('livewire.counter');
    }
}
</code-snippet>

<code-snippet name="Component View (resources/views/livewire/counter.blade.php)" lang="blade">
<div>
    <h1>Count: {{ $count }}</h1>
    <button wire:click="increment">+</button>
    <button wire:click="decrement">-</button>
</div>
</code-snippet>

### Component Conventions
- Class name must be in PascalCase (e.g., `CreatePost`, `UserSettings`)
- File location: `app/Livewire/CreatePost.php`
- View location: `resources/views/livewire/create-post.blade.php`
- Namespace: `App\Livewire`

### Key Changes From Livewire 2
- These things changed in Livewire 2, but may not have been updated in this application. Verify this application's setup to ensure you conform with application conventions.
    - Use `wire:model.live` for real-time updates, `wire:model` is now deferred by default.
    - Components now use the `App\Livewire` namespace (not `App\Http\Livewire`).
    - Use `$this->dispatch()` to dispatch events (not `emit` or `dispatchBrowserEvent`).
    - Use the `components.layouts.app` view as the typical layout path (not `layouts.app`).

### New Directives
- `wire:show`, `wire:transition`, `wire:cloak`, `wire:offline`, `wire:target` are available for use. Use the documentation to find usage examples.

### Alpine
- Alpine is now included with Livewire, don't manually include Alpine.js.
- Plugins included with Alpine: persist, intersect, collapse, and focus.

### Alpine.js with Blade (Critical)
- **NEVER use curly braces `{}` in Alpine.js `:class` directive** - Blade will fail to parse it
- **Use ternary operator instead** for conditional classes

**❌ WRONG - Will cause syntax error**:
```blade
<x-icon name="chevron-down" :class="{ 'rotate-180': isOpen }" />
```

**✅ CORRECT - Use ternary operator**:
```blade
<div :class="isOpen ? 'rotate-180' : ''">
    <x-icon name="chevron-down" />
</div>
```

**✅ ALTERNATIVE - Wrap icon in div with Alpine directive**:
```blade
<div class="transition-transform" :class="isOpen ? 'rotate-180' : ''">
    <x-icon name="chevron-down" size="4" />
</div>
```

**Why**: Blade uses curly braces for its own syntax (`{{ }}`, `{!! !!}`), so Alpine's object syntax `{ key: value }` conflicts and causes parse errors.

### Lifecycle Hooks
- You can listen for `livewire:init` to hook into Livewire initialization, and `fail.status === 419` for the page expiring:

<code-snippet name="livewire:load example" lang="js">
document.addEventListener('livewire:init', function () {
    Livewire.hook('request', ({ fail }) => {
        if (fail && fail.status === 419) {
            alert('Your session expired');
        }
    });

    Livewire.hook('message.failed', (message, component) => {
        console.error(message);
    });
});
</code-snippet>

### Component Properties & Methods
- Use public properties for reactive state
- Use `#[Locked]` attribute for properties that shouldn't be modified from the frontend
- Use computed properties with the `#[Computed]` attribute for derived state
- Lifecycle hooks: `mount()`, `hydrate()`, `updated($property)`, `updatedPropertyName()`

<code-snippet name="Livewire Properties Example" lang="php">
use Livewire\Attributes\{Computed, Locked};

class UserProfile extends Component
{
    public string $name = '';
    
    #[Locked]
    public int $userId;
    
    public function mount(int $userId): void
    {
        $this->userId = $userId;
        $this->name = User::find($userId)->name;
    }
    
    #[Computed]
    public function user()
    {
        return User::find($this->userId);
    }
    
    public function updatedName(): void
    {
        // Called when $name is updated
        $this->validate(['name' => 'required|min:3']);
    }
}
</code-snippet>


=== livewire/testing rules ===

## Testing Livewire Components

- Test Livewire components using Pest in `tests/Feature/Livewire/` directory
- Use `Livewire::test()` to test components

<code-snippet name="Basic Livewire Component Test" lang="php">
use App\Livewire\Counter;
use Livewire\Livewire;

test('counter increments', function () {
    Livewire::test(Counter::class)
        ->assertSet('count', 0)
        ->call('increment')
        ->assertSet('count', 1)
        ->assertSee('Count: 1');
});
</code-snippet>

<code-snippet name="Testing Component With Form" lang="php">
use App\Livewire\CreatePost;
use App\Models\{User, Post};
use Livewire\Livewire;

test('creates post successfully', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CreatePost::class)
        ->set('title', 'Test Post')
        ->set('content', 'Test Content')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('post-created');

    expect(Post::where('title', 'Test Post')->exists())->toBeTrue();
});
</code-snippet>

<code-snippet name="Testing Component On Page" lang="php">
test('post creation page shows livewire component', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/posts/create')
        ->assertSeeLivewire(CreatePost::class);
});
</code-snippet>


=== livewire/patterns rules ===

## Common Livewire Patterns

### Real-Time Search
<code-snippet name="Real-Time Search Pattern" lang="php">
class SearchProducts extends Component
{
    public string $search = '';

    public function render()
    {
        $products = Product::query()
            ->when($this->search, fn($q) => 
                $q->where('name', 'like', "%{$this->search}%")
            )
            ->get();

        return view('livewire.search-products', [
            'products' => $products
        ]);
    }
}
</code-snippet>

<code-snippet name="Real-Time Search View" lang="blade">
<div>
    <input 
        type="text" 
        wire:model.live.debounce.300ms="search"
        placeholder="Search products..."
    >
    
    <div wire:loading>Searching...</div>
    
    @foreach($products as $product)
        <div wire:key="product-{{ $product->id }}">
            {{ $product->name }}
        </div>
    @endforeach
</div>
</code-snippet>

### Form Validation
<code-snippet name="Form Validation Pattern" lang="php">
class CreatePost extends Component
{
    public string $title = '';
    public string $content = '';

    protected function rules(): array
    {
        return [
            'title' => 'required|min:3|max:255',
            'content' => 'required|min:10',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        Post::create($validated);

        $this->dispatch('post-created');
        $this->reset();
    }

    public function updated($property): void
    {
        $this->validateOnly($property);
    }
}
</code-snippet>

### Loading States
<code-snippet name="Loading States Pattern" lang="blade">
<button wire:click="save" wire:loading.attr="disabled">
    <span wire:loading.remove wire:target="save">Save Post</span>
    <span wire:loading wire:target="save">Saving...</span>
</button>
</code-snippet>


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== pest/core rules ===

## Pest

### Testing
- If you need to verify a feature is working, write or update a Unit / Feature test.

### Pest Tests
- All tests must be written using Pest. Use `php artisan make:test --pest <name>`.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files - these are core to the application.
- Tests should test all of the happy paths, failure paths, and weird paths.
- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Pest tests look and behave like this:
<code-snippet name="Basic Pest Test Example" lang="php">
it('is true', function () {
    expect(true)->toBeTrue();
});
</code-snippet>

### Running Tests
- Run the minimal number of tests using an appropriate filter before finalizing code edits.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).
- When the tests relating to your changes are passing, ask the user if they would like to run the entire test suite to ensure everything is still passing.

### Pest Assertions
- When asserting status codes on a response, use the specific method like `assertForbidden` and `assertNotFound` instead of using `assertStatus(403)` or similar, e.g.:
<code-snippet name="Pest Example Asserting postJson Response" lang="php">
it('returns all', function () {
    $response = $this->postJson('/api/docs', []);

    $response->assertSuccessful();
});
</code-snippet>

### Mocking
- Mocking can be very helpful when appropriate.
- When mocking, you can use the `Pest\Laravel\mock` Pest function, but always import it via `use function Pest\Laravel\mock;` before using it. Alternatively, you can use `$this->mock()` if existing tests do.
- You can also create partial mocks using the same import or self method.

### Datasets
- Use datasets in Pest to simplify tests which have a lot of duplicated data. This is often the case when testing validation rules, so consider going with this solution when writing tests for validation rules.

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>


=== pest/v4 rules ===

## Pest 4

- Pest v4 is a huge upgrade to Pest and offers: browser testing, smoke testing, visual regression testing, test sharding, and faster type coverage.
- Browser testing is incredibly powerful and useful for this project.
- Browser tests should live in `tests/Browser/`.
- Use the `search-docs` tool for detailed guidance on utilizing these features.

### Browser Testing
- You can use Laravel features like `Event::fake()`, `assertAuthenticated()`, and model factories within Pest v4 browser tests, as well as `RefreshDatabase` (when needed) to ensure a clean state for each test.
- Interact with the page (click, type, scroll, select, submit, drag-and-drop, touch gestures, etc.) when appropriate to complete the test.
- If requested, test on multiple browsers (Chrome, Firefox, Safari).
- If requested, test on different devices and viewports (like iPhone 14 Pro, tablets, or custom breakpoints).
- Switch color schemes (light/dark mode) when appropriate.
- Take screenshots or pause tests for debugging when appropriate.

### Example Tests

<code-snippet name="Pest Browser Test Example" lang="php">
it('may reset the password', function () {
    Notification::fake();

    $this->actingAs(User::factory()->create());

    $page = visit('/sign-in'); // Visit on a real browser...

    $page->assertSee('Sign In')
        ->assertNoJavascriptErrors() // or ->assertNoConsoleLogs()
        ->click('Forgot Password?')
        ->fill('email', 'nuno@laravel.com')
        ->click('Send Reset Link')
        ->assertSee('We have emailed your password reset link!')

    Notification::assertSent(ResetPassword::class);
});
</code-snippet>

<code-snippet name="Pest Smoke Testing Example" lang="php">
$pages = visit(['/', '/about', '/contact']);

$pages->assertNoJavascriptErrors()->assertNoConsoleLogs();
</code-snippet>


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.

### Dynamic Classes (Critical)
- **NEVER use dynamic Tailwind classes** with string interpolation - Tailwind cannot compile them at build time
- **Always use full class names** with PHP match expressions or conditional logic

**❌ WRONG - Dynamic classes won't compile:**
```blade
<div class="bg-{{ $color }}-600">  <!-- Won't work -->
<button class="text-{{ $job['color'] }}-500">  <!-- Won't work -->
```

**✅ CORRECT - Use match() for full class names:**
```blade
@php
    $bgClass = match($color) {
        'blue' => 'bg-blue-600',
        'green' => 'bg-green-600',
        'red' => 'bg-red-600',
        default => 'bg-gray-600',
    };
@endphp
<div class="{{ $bgClass }}">  <!-- Will compile correctly -->
```

**Why**: Tailwind's JIT compiler scans files at build time for complete class names. Partial strings like `bg-{{ $var }}-600` aren't recognized and won't be included in the compiled CSS.

**After fixing dynamic classes**: Always rebuild assets with `docker compose exec node npm run build`


=== tailwindcss/v4 rules ===

## Tailwind 4

- Always use Tailwind CSS v4 - do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, configuration is CSS-first using the `@theme` directive — no separate `tailwind.config.js` file is needed.
<code-snippet name="Extending Theme in CSS" lang="css">
@theme {
  --color-brand: oklch(0.72 0.11 178);
}
</code-snippet>

- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff">
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>


### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option - use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |
</laravel-boost-guidelines>
