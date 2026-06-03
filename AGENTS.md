# AGENTS.md — AureusERP Custom ERP Project

> Read this file fully before writing a single line of code.
> Every rule exists for a reason. When rules conflict, PROJECT OVERRIDES win.

---

## 1. Project Overview

This is a **customised AureusERP installation** — an open-source Laravel ERP
built on the following stack:

| Package | Version |
|---|---|
| PHP | 8.4 |
| laravel/framework | v13 |
| filament/filament | v5 |
| livewire/livewire | v4 |
| tailwindcss | v4 |
| pestphp/pest | v4 |
| phpunit/phpunit | v12 |
| rector/rector | v2 |
| laravel/pint | v1 |
| wezlo/filament-approval | latest |
| saade/filament-fullcalendar | latest |

**Client:** Omani organisation (non-profit / government-adjacent)
**Language:** Bilingual — Arabic (primary) + English
**Currency:** Omani Rial (OMR) — 3 decimal places — symbol: ر.ع.
**Direction:** RTL (Arabic) / LTR (English) — follows app locale

---

## 2. Installed Custom Plugins

All custom plugins live in `plugins/webkul/{name}/`:

| Plugin | Namespace | Install Command |
|---|---|---|
| `meetings` | `Webkul\Meetings` | `php artisan meetings:install` |
| `correspondence` | `Webkul\Correspondence` | `php artisan correspondence:install` |
| `document-archive` | `Webkul\DocumentArchive` | `php artisan document-archive:install` |
| `my-notes` | `Webkul\MyNotes` | `php artisan my-notes:install` |

### Approval Workflow (shared system)
All approval logic uses `wezlo/filament-approval`.
- Model trait: `app/Traits/HasApprovalWorkflow.php`
- Filament trait: `app/Filament/Traits/HasApprovalActions.php`
- Applied to: Invoice, PurchaseOrder, Meeting, Correspondence
- **NEVER write custom approval logic** — always use these traits

---

## 3. The Golden Rules (Non-Negotiable)

```
RULE 1: READ BEFORE WRITE
  Always read the existing file before modifying it.
  Always study the pattern of existing plugins before creating new ones.
  Run: cat plugins/webkul/meetings/src/MeetingsPlugin.php
  before creating any new plugin.

RULE 2: NEVER MODIFY CORE WEBKUL PLUGINS
  Files inside plugins/webkul/ that we did not create are READ-ONLY.
  Extend via Laravel traits, method overriding, or event listeners only.
  Exception: adding HasApprovalWorkflow / HasApprovalActions is allowed.

RULE 3: NO HARDCODED STRINGS
  Every user-facing string goes through trans() or __().
  Never: ->label('My Label')
  Always: ->label(__('module::file.key'))

RULE 4: ALL FILES IN PRIVATE DISK
  User-uploaded files always go to storage/app/private/
  Always serve via signed temporary URLs — never direct paths.

RULE 5: QUEUE ALL EMAILS
  Every Mailable must implement ShouldQueue.
  Never send email synchronously.

RULE 6: WRAP CROSS-PLUGIN QUERIES
  Before querying another plugin's table:
    if (!Schema::hasTable('meetings')) { return []; }
  Before using another plugin's class:
    if (!class_exists(\Webkul\Meetings\Models\Meeting::class)) { ... }

RULE 7: IDEMPOTENT SEEDERS
  Every seeder checks before inserting:
    if (Model::exists()) { return; }

RULE 8: APPROVAL USES EXISTING SYSTEM
  Never build custom approval logic.
  Add HasApprovalWorkflow to model + HasApprovalActions to ViewRecord.

RULE 9: STOP ON PACKAGE NOT FOUND
  If composer require fails, STOP and report the error.
  Do not write custom code to replace a missing package.

RULE 10: TESTS OVER VERIFICATION SCRIPTS
  Do not create one-off tinker scripts to verify behavior.
  Write a Pest test instead — it proves the behavior permanently.
```

---

## 4. PHP 8.4 Standards

```php
// Constructor property promotion — always
public function __construct(
    public readonly MeetingRepository $meetings,
    private readonly NotificationService $notifications,
) {}

// Explicit return types and type hints — always
public function canBeConfirmed(?User $user = null): bool
{
    return $this->isFullyApproved() && $user?->can('confirm_meeting');
}

// Enum keys: TitleCase backed by snake_case value
enum MeetingStatus: string
{
    case Draft           = 'draft';
    case PendingApproval = 'pending_approval';
    case Confirmed       = 'confirmed';
    case Archived        = 'archived';
}

// Array shape PHPDoc for complex returns
/**
 * @return array{done: int, total: int, percent: float}
 */
public function getChecklistProgress(): array { ... }

// Descriptive names — not abbreviated
$isRegisteredForApproval   // not $registered
$pendingApprovalCount       // not $count
$hasExpiringDocuments       // not $expiring

// Curly braces always — even single-line bodies
if ($condition) {
    return true;
}
```

---

## 5. AureusERP Project Overrides (Filament v5)

**These override general Filament v5 conventions. PROJECT WINS.**

### Icons — Heroicons (string names)

Core Webkul plugins (accounting, support, plugin-manager, etc.) use **Filament's built-in Heroicons as strings** — not the `Heroicon` enum, and not Tabler (`ti-*`).

```php
// Navigation / resources — outline (default)
protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

// Actions, widgets, infolists
->icon('heroicon-o-plus-circle')
->descriptionIcon('heroicon-o-clock')

// List-page tabs / compact filters — solid (accounting pattern)
->icon('heroicon-s-banknotes')
->icon('heroicon-s-check-badge')
```

**Variants:**
| Prefix | Use for |
|--------|---------|
| `heroicon-o-*` | Navigation, actions, infolists (default) |
| `heroicon-s-*` | List tabs, filter chips, compact UI |
| `heroicon-m-*` | Rare — mini size when needed |

**Do NOT use:**
```php
// Tabler — NOT installed; causes SvgNotFound at runtime
->icon('ti-notes')                    // WRONG

// Heroicon enum — core plugins use strings, not the enum
->navigationIcon(Heroicon::OutlinedDocument)  // WRONG
```

Reference: `plugins/webkul/accounting/src/Filament/` for real usage patterns.

### Plugin branding icons (separate from Filament UI icons)

Plugin **module icons** in the plugin manager are custom SVGs — not Heroicons:

```php
// In {Name}ServiceProvider.php
$package->icon('accounting');  // → public/svg/accounting.svg
```

Add the SVG to `public/svg/{name}.svg` (see `accounting.svg`, `employees.svg`, etc.). These are for plugin-manager branding only — do not use them as `$navigationIcon` or `->icon()` values in Filament resources.

### Plugin Structure — Self-Contained Resources
```
// AureusERP convention — form + table inline in resource
plugins/webkul/meetings/src/Filament/Resources/MeetingResource.php

// Do NOT extract to separate Schema/Table classes
Resources/Meetings/Schemas/MeetingSchema.php  // WRONG
```

### Widget Polling — Disabled
```php
// This project — on page load only
protected static ?string $pollingInterval = null;
```

---

## 6. Filament v5 — Correct Namespaces

```php
// Form fields
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;

// Layout components (NOT from Forms\Components)
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Fieldset;

// Schema utilities
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

// Infolist entries
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\BadgeEntry;
use Filament\Infolists\Components\ImageEntry;

// Table columns
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;

// Table filters
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;

// Actions — ALWAYS from this namespace, never sub-namespaces
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;

// These sub-namespaces were REMOVED in v5 — never use:
// Filament\Tables\Actions\*   WRONG
// Filament\Forms\Actions\*    WRONG
// Filament\Infolists\Actions\* WRONG
```

---

## 7. Filament v5 — Critical API Changes

```php
// Action modals use ->schema() NOT ->form()
Action::make('approve')
    ->schema([
        Textarea::make('comment')->required(),
    ])
    ->action(fn (array $data) => $this->approve($data));

// Table uses recordActions() NOT actions()
->recordActions([ViewAction::make(), EditAction::make()])

// Bulk actions
->groupedBulkActions([DeleteBulkAction::make()])

// Toolbar actions
->toolbarActions([CreateAction::make()])

// Schema top level
$schema->components([Section::make()->schema([...])])

// Repeater uses ->schema() not ->fields()
Repeater::make('items')->schema([TextInput::make('name')])

// Correct property types — do not change modifiers
protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
protected static string|\UnitEnum|null $navigationGroup = 'Meetings';
protected string $view = 'filament.pages.dashboard'; // NOT static on Page/Widget
```

---

## 8. Filament v5 — Common Mistakes

| Mistake | Correct Approach |
|---|---|
| `->form()` in action modals | `->schema()` |
| `->actions()` in tables | `->recordActions()` |
| `->bulkActions()` | `->groupedBulkActions()` |
| `Filament\Tables\Actions\DeleteAction` | `Filament\Actions\DeleteAction` |
| `BelongsToSelect::make()` | `Select::make()->relationship()` |
| `Repeater::make()->fields()` | `Repeater::make()->schema()` |
| `->dehydrated(false)` on saved fields | Only for UI-only helper fields |
| `$navigationIcon` as `?string` | `string\|BackedEnum\|null` |
| `$view` as `static` on Page/Widget | `protected string $view` |
| File without `->visibility('public')` | Default is private — always explicit |
| Select without `->searchable()->preload()` | Always add for relationship selects |
| Grid children without `->columnSpan()` | Always set span explicitly |
| `'ti-*'` Tabler icon strings | `'heroicon-o-*'` string (Tabler not installed) |
| Heroicon enum for icons | `'heroicon-o-*'` / `'heroicon-s-*'` string |

---

## 9. Plugin Architecture Pattern

Every custom plugin MUST follow this exact structure:

```
plugins/webkul/{name}/
  composer.json
  src/
    {Name}Plugin.php
    {Name}ServiceProvider.php
    Models/
      {Model}.php
    Filament/
      Resources/
        {Model}Resource.php
        {Model}Resource/
          Pages/
            List{Models}.php
            Create{Model}.php
            View{Model}.php
            Edit{Model}.php
          RelationManagers/
            {Relation}RelationManager.php
      Pages/
        {Name}Dashboard.php
      Widgets/
        {Name}StatsWidget.php
    Console/
      Commands/
        InstallCommand.php
  database/
    migrations/
  resources/
    lang/
      en/{name}.php
      ar/{name}.php
```

### Plugin Class Pattern
```php
class MeetingsPlugin implements Plugin
{
    public function getId(): string
    {
        return 'meetings';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([MeetingResource::class])
            ->pages([MeetingDashboard::class, MeetingCalendar::class]);
    }

    public function boot(Panel $panel): void {}

    public static function make(): static
    {
        return app(static::class);
    }
}
```

---

## 10. Model Standards

```php
class Meeting extends Model
{
    use SoftDeletes;
    use HasFactory;
    use HasApprovalWorkflow; // if approval needed

    protected $fillable = [
        'title', 'type', 'status', 'meeting_date',
        'location', 'project_id', 'company_id', 'creator_id',
    ];

    protected $casts = [
        'meeting_date' => 'datetime',
        'is_archived'  => 'boolean',
        'type'         => MeetingType::class,
        'status'       => MeetingStatus::class,
    ];

    // Auto-generate reference numbers in a DB transaction
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (empty($model->meeting_number)) {
                DB::transaction(function () use ($model): void {
                    $year = now()->year;
                    $seq  = str_pad(
                        static::whereYear('created_at', $year)->count() + 1,
                        4, '0', STR_PAD_LEFT,
                    );
                    $model->meeting_number = "MTG-{$year}-{$seq}";
                });
            }
        });
    }
}
```

### Enum Pattern (always implement all three contracts)
```php
enum MeetingType: string implements HasLabel, HasColor, HasIcon
{
    case Internal  = 'internal';
    case External  = 'external';
    case Emergency = 'emergency';
    case Board     = 'board';

    public function getLabel(): string
    {
        return __('meetings::meetings.type.' . $this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Internal  => 'blue',
            self::External  => 'purple',
            self::Emergency => 'danger',
            self::Board     => 'success',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Internal  => 'heroicon-o-building-office-2',
            self::External  => 'heroicon-o-globe-alt',
            self::Emergency => 'heroicon-o-exclamation-triangle',
            self::Board     => 'heroicon-o-user-group',
        };
    }
}
```

---

## 11. Approval Workflow — How to Apply

**Step 1 — Model:**
```php
use App\Traits\HasApprovalWorkflow;

class PurchaseOrder extends Model
{
    use HasApprovalWorkflow;

    public function confirm(): void
    {
        if (!$this->isFullyApproved()) {
            throw new \RuntimeException(
                __('purchases::messages.approval_required')
            );
        }
    }
}
```

**Step 2 — ViewRecord Page:**
```php
use App\Filament\Traits\HasApprovalActions;
use Webkul\Chatter\Filament\Actions\ChatterAction;

class ViewPurchaseOrder extends ViewRecord
{
    use HasApprovalActions;

    protected function getHeaderActions(): array
    {
        return [
            ChatterAction::make()->setResource(static::$resource),
            ...$this->getApprovalActions(),
        ];
    }
}
```

**Step 3 — Resource:**
```php
// table():      ApprovalStatusColumn::make()
// getRelations(): ApprovalsRelationManager::class
// infolist():   ApprovalStatusSection::make()
```

**Step 4 — Admin UI (no code):**
`Approvals → Approval Flows → New → select model → configure steps`

---

## 12. Form Patterns

```php
// Reactive field — live(onBlur) for text, live() for selects
TextInput::make('title')
    ->live(onBlur: true)
    ->afterStateUpdated(fn (Set $set, ?string $state) =>
        $set('slug', Str::slug($state ?? ''))
    ),

// Conditional visibility
Select::make('type')->options(MeetingType::class)->live(),
Select::make('project_id')
    ->visible(fn (Get $get): bool => $get('type') === 'external')
    ->relationship('project', 'name')
    ->searchable()
    ->preload(),

// Section + Grid — always set columnSpan explicitly
Section::make(__('meetings::meetings.sections.details'))
    ->schema([
        Grid::make(2)->schema([
            TextInput::make('title')->columnSpanFull(),
            DateTimePicker::make('meeting_date')->columnSpan(1),
            TextInput::make('location')->columnSpan(1),
        ]),
    ]),

// Repeater — always ->schema() not ->fields()
Repeater::make('attendees')
    ->relationship()
    ->schema([
        Select::make('user_id')
            ->relationship('user', 'name')
            ->searchable()
            ->preload(),
        Select::make('role')->options(AttendeeRole::class),
    ])
    ->columns(2),
```

---

## 13. Table Patterns

```php
// Computed column
TextColumn::make('full_name')
    ->state(fn (Employee $record): string =>
        "{$record->first_name} {$record->last_name}"
    ),

// OMR money — always 3 decimal places
TextColumn::make('amount')
    ->formatStateUsing(fn (float $state): string =>
        'ر.ع. ' . number_format($state, 3)
    )
    ->sortable(),

// Enum badge
BadgeColumn::make('status')->colors(MeetingStatus::class),

// Filters
SelectFilter::make('type')->options(MeetingType::class),
Filter::make('overdue')
    ->query(fn (Builder $query) =>
        $query->where('due_date', '<', now())
              ->whereNotIn('status', ['completed', 'cancelled'])
    ),

// Toggleable columns
TextColumn::make('notes')->toggleable(isToggledHiddenByDefault: true),

// Action group — always: View, Edit, Delete
->recordActions([
    ActionGroup::make([
        ViewAction::make(),
        EditAction::make(),
        DeleteAction::make(),
    ]),
])
```

---

## 14. Currency Rules

```php
// OMR = 3 decimal places. ALWAYS. Never 2.
'ر.ع. ' . number_format($amount, 3)       // Arabic
'OMR ' . number_format($amount, 3)          // English

TextColumn::make('amount')
    ->formatStateUsing(fn ($state) => 'ر.ع. ' . number_format($state, 3))

// In PDF/Blade
{{ 'ر.ع. ' . number_format($invoice->amount, 3) }}
```

---

## 15. File Storage Rules

```php
// Always private disk
Storage::disk('private')->put($path, $contents);

// Always signed URL for serving
$url = Storage::disk('private')->temporaryUrl($path, now()->addMinutes(60));

// Storage paths per module:
// storage/app/private/meetings/{year}/
// storage/app/private/correspondence/{year}/{reference}/
// storage/app/private/employees/{employee_id}/documents/
// storage/app/private/purchases/receipts/{year}/
// storage/app/private/documents/{company_id}/{folder_path}/
// storage/app/private/notes/voice/{user_id}/
// storage/app/private/submissions/{year}/{ticket}/

Storage::disk('public')->put(...) // NEVER
```

---

## 16. Notifications & Mail

```php
// Filament database notification
Notification::make()
    ->title(__('meetings::notifications.confirmed_title'))
    ->body(__('meetings::notifications.confirmed_body', [
        'title' => $meeting->title,
        'date'  => $meeting->meeting_date->format('d M Y'),
    ]))
    ->success()
    ->sendToDatabase($user);

// Queued Mailable — always implements ShouldQueue
class MeetingConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Meeting $meeting,
        public readonly User $recipient,
    ) {}
}

dispatch(new MeetingConfirmationMail($meeting, $user)); // always dispatch
Mail::to($user)->send(...); // NEVER — synchronous
```

---

## 17. Scheduled Commands

All registered in `routes/console.php`. All idempotent.

```php
Schedule::command('meetings:notify-overdue-tasks')->dailyAt('08:00');
Schedule::command('correspondence:notify-overdue')->dailyAt('09:00');
Schedule::command('hr:notify-expiring-documents')->dailyAt('08:00');
Schedule::command('purchases:remind-receipts')->dailyAt('09:00');
Schedule::command('notes:send-reminders')->everyFiveMinutes();
Schedule::command('submissions:remind-unresolved')->weeklyOn(1, '09:00');
Schedule::command('documents:archive-expired')->dailyAt('06:00');
Schedule::command('documents:cleanup-share-links')->dailyAt('06:00');
```

---

## 18. Translation Structure

```php
// Plugin translations
plugins/webkul/{name}/resources/lang/en/{name}.php
plugins/webkul/{name}/resources/lang/ar/{name}.php

// Usage
__('meetings::meetings.status.confirmed')
__('correspondence::correspondence.type.official')

// Global
lang/en/approval.php    lang/ar/approval.php
lang/en/dashboard.php   lang/ar/dashboard.php

// Never hardcode Arabic in PHP or Blade
->label('تأكيد الاجتماع')         // WRONG
->label(__('meetings::...confirm')) // CORRECT
```

---

## 19. PDF Export Standards

- **RTL** when Arabic locale active
- **Font:** Amiri or Cairo — never Latin fonts
- **Logo:** from company settings — never hardcoded path
- **OMR:** 3 decimal places always
- **Storage:** `storage/app/private/{module}/pdf/{reference}.pdf`
- **Footer:** reference number + page X of Y

```
// Blade views (already created):
resources/views/meetings/pdf/meeting-minutes.blade.php
resources/views/correspondence/pdf/letter.blade.php
resources/views/dashboard/pdf/dashboard-report.blade.php
```

---

## 20. Dashboard Architecture

```php
use Filament\Pages\Dashboard as BaseDashboard;
use Webkul\Support\Filament\Clusters\Dashboard as DashboardCluster;

class Dashboard extends BaseDashboard
{
    use BaseDashboard\Concerns\HasFiltersForm;

    protected static string $routePath = '/';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $cluster = DashboardCluster::class;
    protected static ?string $pollingInterval = null; // disabled

    public function getWidgets(): array
    {
        $user = auth()->user();

        return match (true) {
            $user->hasRole('general_manager') => static::getGMWidgets(),
            $user->hasRole('finance_manager') => static::getFinanceWidgets(),
            $user->hasRole('hr_manager')      => static::getHRWidgets(),
            $user->hasRole('manager')          => static::getManagerWidgets(),
            default                            => static::getEmployeeWidgets(),
        };
    }

    public function getColumns(): int|string|array
    {
        return ['default' => 1, 'sm' => 2, 'lg' => 4];
    }
}

// Every widget wraps cross-plugin queries:
if (!Schema::hasTable('meetings')) {
    return [Stat::make('', __('dashboard.plugin_not_installed'))->color('gray')];
}

// All dashboard widgets in: app/Filament/Widgets/Dashboard/
```

### 20.1 Filament Widget Patterns

Official Filament v5 references:
- [Widgets overview](https://filamentphp.com/docs/5.x/widgets/overview)
- [Stats overview widgets](https://filamentphp.com/docs/5.x/widgets/stats-overview)
- [Chart widgets](https://filamentphp.com/docs/5.x/widgets/charts)

```php
// Dashboard page — inline filters + responsive grid
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class MeetingDashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Schema $schema): Schema { /* DatePicker, Select */ }

    public function getColumns(): int|array
    {
        return ['default' => 1, 'md' => 2, 'lg' => 12];
    }
}

// StatsOverview — clickable cards with sparklines
Stat::make(__('meetings::...'), $count)
    ->description(__('...'))
    ->descriptionIcon('heroicon-m-arrow-trending-up')
    ->chart([1, 4, 2, 8, 3, 6])
    ->color('warning')
    ->url(MeetingResource::getUrl('index', ['activeTab' => 'pending_approval']))
    ->extraAttributes(['class' => 'cursor-pointer']);

// ChartWidget — time series via flowframe/laravel-trend
class MeetingsTrendChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $pollingInterval = null;
    protected int|string|array $columnSpan = 7;

    protected function getType(): string { return 'line'; }
}

// TableWidget — limit rows, header “view all” action, columnSpan for grid
```

**Project conventions:**
- `$pollingInterval = null` on every dashboard widget (load on page open only)
- Dashboard filters: `HasFiltersForm` on the page + `InteractsWithPageFilters` on widgets
- Plugin meeting widgets: `plugins/webkul/meetings/src/Filament/Widgets/`
- Cross-plugin queries: guard with `Schema::hasTable()` before querying
- All user-facing strings via `__('meetings::meetings....')` — never hardcode Arabic/English
- Stat cards link to filtered `ListMeetings` tabs via `->url()` and `activeTab`

**Anti-patterns:** polling widgets in this project; Heroicon enums for plugin nav icons; hardcoded chart labels.

---

## 21. Testing Standards (Pest v4)

```php
beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

// List
it('lists meetings', function (): void {
    $meetings = Meeting::factory()->count(3)->create();

    livewire(ListMeetings::class)
        ->assertCanSeeTableRecords($meetings);
});

// Create
it('can create a meeting', function (): void {
    livewire(CreateMeeting::class)
        ->fillForm([
            'title'        => 'Q2 Planning',
            'type'         => MeetingType::Internal,
            'meeting_date' => now()->addDays(3)->format('Y-m-d H:i'),
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas(Meeting::class, ['title' => 'Q2 Planning']);
});

// Edit — no assertRedirect() on edit pages
it('can update a meeting', function (): void {
    $meeting = Meeting::factory()->create();

    livewire(EditMeeting::class, ['record' => $meeting->id])
        ->fillForm(['title' => 'Updated'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas(Meeting::class, ['id' => $meeting->id, 'title' => 'Updated']);
});

// Validation
it('requires title', function (): void {
    livewire(CreateMeeting::class)
        ->fillForm(['title' => null])
        ->call('create')
        ->assertHasFormErrors(['title' => 'required'])
        ->assertNotNotified();
});

// Table action
it('can archive a meeting', function (): void {
    $meeting = Meeting::factory()->confirmed()->create();

    livewire(ListMeetings::class)
        ->callAction(TestAction::make('archive')->table($meeting))
        ->assertNotified();

    expect($meeting->fresh()->status)->toBe(MeetingStatus::Archived);
});

// Relation manager
it('can add attendees', function (): void {
    $meeting = Meeting::factory()->create();

    livewire(AttendeesRelationManager::class, [
        'ownerRecord' => $meeting,
        'pageClass'   => EditMeeting::class,
    ])
        ->callAction('create', data: [
            'user_id' => User::factory()->create()->id,
        ])
        ->assertHasNoFormErrors();
});
```

---

## 22. Code Formatting

Run Pint after every PHP change:

```bash
vendor/bin/pint --dirty --format agent
```

Always use `--format` (fix in place). Never use `--test` mode.

---

## 23. Livewire 4 Features

```php
// wire:sort — built-in drag-and-drop, no external package needed
<ul wire:sort="reorderItems">
    @foreach ($items as $item)
        <li wire:sort.item="{{ $item->id }}">{{ $item->content }}</li>
    @endforeach
</ul>

// Islands — isolated re-render regions
@island
    <livewire:meeting-calendar :meeting="$meeting" />
@endisland

// Async — fire-and-forget for logging/analytics
#[Async]
public function logView(): void
{
    $this->record->incrementViewCount();
}

// wire:model — blur for text inputs, live for selects
<input wire:model.blur="title">
<select wire:model.live="type">
```

---

## 24. GitHub & Deployment

### Branches
```
main          ← upstream AureusERP mirror (never commit client work)
client/oman   ← all client customisations
feature/*     ← individual features off client/oman
```

### Remotes
```bash
origin    → github.com/YOUR_USERNAME/aureuserp
upstream  → github.com/aureuserp/aureuserp
```

### Upgrade
```bash
git fetch upstream
git diff HEAD upstream/main -- plugins/webkul/
git checkout client/oman
git merge upstream/main
composer install --no-dev --optimize-autoloader
php artisan migrate && php artisan shield:generate --all
php artisan optimize:clear
```

### Deploy (`./deploy.sh`)
```bash
php artisan down --secret="TOKEN"
git pull origin client/oman
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan shield:generate --all
php artisan optimize
php artisan filament:cache-components  # production only — never in dev
php artisan queue:restart
php artisan up
```

---

## 25. Local Dev Credentials (agents)

Read `auth.md` for the canonical admin login. Database access for local development:

| Setting | Value |
|---|---|
| Database | `nodhum_erp` |
| Username | `root` |
| Password | `hgpsdkk` |
| Admin email | `nodhumtech@gmail.com` (see `auth.md`) |
| Admin password | see `auth.md` |

Reset admin password when needed:

```bash
php artisan tinker --execute='\Webkul\Security\Models\User::where("email", "nodhumtech@gmail.com")->update(["password" => \Illuminate\Support\Facades\Hash::make("Oman@999")]);'
```

---

## 26. Before You Start Any Task

```bash
# 1. Read existing relevant files
cat plugins/webkul/meetings/src/MeetingsPlugin.php

# 2. Check which tables exist
php artisan tinker --execute '
    collect(["meetings","correspondences","doc_files",
             "employee_submissions","notes","employee_warnings",
             "employee_documents","departments"])
    ->each(fn($t) => dump($t.": ".(Schema::hasTable($t)?"yes":"no")));
'

# 3. Check plugins and migrations
php artisan erp:plugin:list
php artisan migrate:status | grep "No"

# 4. Verify packages
composer show wezlo/filament-approval
composer show saade/filament-fullcalendar

# 5. Format after every PHP change
vendor/bin/pint --dirty --format agent

# 6. Run tests before declaring done
php artisan test --compact --filter=MeetingPluginTest
```

---

## 27. Quick Reference — Key Files

```
app/Traits/HasApprovalWorkflow.php           # approval model trait
app/Filament/Traits/HasApprovalActions.php   # approval page trait
app/Filament/Pages/Dashboard.php             # main dashboard
app/Filament/Widgets/Dashboard/              # all dashboard widgets
deploy.sh                                    # production deploy
routes/console.php                           # scheduled commands
app/Providers/Filament/AdminPanelProvider.php
public/images/logo.svg
public/images/logo-dark.svg
public/images/favicon.png
docs/HOW_TO_ADD_APPROVAL.md
config/document-archive.php
config/approval.php
```

---

## 28. Complete Mistakes Reference

| Mistake | Correct Approach |
|---|---|
| `->form()` in action modals | `->schema()` — Filament v5 |
| `->actions()` in tables | `->recordActions()` — Filament v5 |
| `->bulkActions()` | `->groupedBulkActions()` |
| `Filament\Tables\Actions\*` | `Filament\Actions\*` — always |
| `BelongsToSelect::make()` | `Select::make()->relationship()` |
| `Repeater::make()->fields()` | `Repeater::make()->schema()` |
| `->dehydrated(false)` on saved fields | Only for UI-only helper fields |
| `$navigationIcon` as `?string` | `string\|BackedEnum\|null` |
| `$view` as `static` on Page/Widget | `protected string $view` |
| Grid children without `->columnSpan()` | Always set span explicitly |
| `'ti-*'` Tabler icon strings | `'heroicon-o-*'` string (Tabler not installed) |
| Heroicon enum for icons | `'heroicon-o-*'` / `'heroicon-s-*'` string |
| Custom approval logic | `HasApprovalWorkflow` trait |
| Files in public disk | Private disk + signed URL |
| Arabic text hardcoded in PHP | `lang/ar/` translation keys |
| Sending mail synchronously | Dispatch a queued Mailable |
| Cross-plugin query without check | `Schema::hasTable()` first |
| Seeder without idempotency | Check before every insert |
| OMR with 2 decimal places | Always 3 decimal places |
| `filament:cache-components` in dev | Production deploy only |
| One-off tinker verification | Write a Pest test instead |
| `->label('Hardcoded text')` | `->label(__('module::file.key'))` |

---

*Last updated: May 2026*
*Stack: PHP 8.4 · Laravel 13 · FilamentPHP 5 · Livewire 4 · Pest 4*
*AureusERP + wezlo/filament-approval + saade/filament-fullcalendar*
