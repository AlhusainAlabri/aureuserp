<?php

namespace App\Filament\Pages\Settings;

use App\Support\Accounting\PeriodLock;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;
use Webkul\Accounting\Filament\Clusters\Configuration;
use Webkul\PluginManager\Package;
use Webkul\Security\Models\User;
use Webkul\Support\Filament\Clusters\Settings;

/**
 * Where finance closes a period: everything posted on or before the lock date, per company, stops changing.
 */
class ManageLockDate extends Page implements HasForms
{
    use InteractsWithForms;

    /**
     * The key Filament Shield generates for this page; pinned by ManageLockDatePageTest.
     */
    public const PERMISSION = 'page_filament_manage_lock_date';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.pages.settings.manage-lock-date';

    /** @var array<string, mixed> */
    public ?array $data = [];

    /**
     * Accounting is an optional plugin and only an installed plugin registers its cluster, so a site without it
     * gets the page in the general Settings cluster instead of a page whose cluster does not exist.
     *
     * @return class-string<Cluster>|null
     */
    public static function getCluster(): ?string
    {
        return Package::isPluginInstalled('accounting') ? Configuration::class : Settings::class;
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof Authenticatable
            && method_exists($user, 'can')
            && $user->can(static::PERMISSION);
    }

    public function mount(): void
    {
        $companyId = $this->defaultCompanyId();

        $this->form->fill([
            'company_id' => $companyId,
            'lock_date'  => $this->storedLockDate($companyId),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('audit.lock_page.section'))
                    ->description(__('audit.lock_page.help'))
                    ->schema([
                        Select::make('company_id')
                            ->label(__('audit.lock_page.company'))
                            ->options(fn (): array => $this->allowedCompanies())
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get): void {
                                $companyId = $get('company_id');

                                $set('lock_date', $this->storedLockDate(is_numeric($companyId) ? (int) $companyId : null));
                            }),
                        DatePicker::make('lock_date')
                            ->label(__('audit.lock_page.lock_date'))
                            ->helperText(__('audit.lock_page.lock_date_help'))
                            ->maxDate(fn (): CarbonImmutable => CarbonImmutable::today())
                            ->native(false)
                            ->closeOnDateSelection()
                            ->displayFormat('d/m/Y')
                            ->format('Y-m-d')
                            ->nullable(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $user = Filament::auth()->user();

        abort_unless($user instanceof Authenticatable, 403);

        $companyId = (int) $state['company_id'];
        $date = filled($state['lock_date'] ?? null) ? CarbonImmutable::parse($state['lock_date']) : null;

        try {
            PeriodLock::setLockDate($companyId, $date, $user);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages([
                'data.lock_date' => $exception->errors()[PeriodLock::COLUMN] ?? $exception->getMessage(),
            ]);
        }

        Notification::make()
            ->title(__('audit.lock_page.saved_title'))
            ->body($date
                ? __('audit.lock_page.saved_locked', ['date' => $date->format('d/m/Y')])
                : __('audit.lock_page.saved_cleared'))
            ->success()
            ->send();
    }

    public static function getNavigationLabel(): string
    {
        return __('audit.lock_page.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return static::getCluster() === Configuration::class
            ? __('accounting::filament/clusters/configurations.navigation.group')
            : null;
    }

    public function getTitle(): string
    {
        return __('audit.lock_page.title');
    }

    public function getSubheading(): ?string
    {
        return __('audit.lock_page.subheading');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('audit.lock_page.save'))
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function allowedCompanies(): array
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        return $user->allowedCompanies()->orderBy('name')->pluck('name', 'companies.id')->all();
    }

    protected function defaultCompanyId(): ?int
    {
        $user = Filament::auth()->user();
        $allowed = array_keys($this->allowedCompanies());

        if ($user instanceof User && in_array($user->default_company_id, $allowed, true)) {
            return (int) $user->default_company_id;
        }

        return $allowed[0] ?? null;
    }

    protected function storedLockDate(?int $companyId): ?string
    {
        return PeriodLock::lockDateFor($companyId)?->toDateString();
    }
}
