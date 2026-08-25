<?php

namespace App\Filament\Pages\Settings;

use App\Services\Notifications\NotificationPreferenceService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Webkul\Security\Models\User;
use Webkul\Support\Filament\Clusters\Settings;

class ManageNotificationPreferences extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $cluster = Settings::class;

    protected static ?int $navigationSort = 15;

    protected string $view = 'filament.pages.settings.manage-notification-preferences';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(NotificationPreferenceService $preferences): void
    {
        $user = $this->getAuthenticatedUser();
        $formData = ['categories' => $preferences->preferencesForUser($user)];

        if ($this->hasTaskOffsets($preferences)) {
            $formData['task_offsets'] = $preferences->taskReminderOffsetsForUser($user);
        }

        $this->form->fill($formData);
    }

    public function form(Schema $schema): Schema
    {
        $preferenceService = app(NotificationPreferenceService::class);
        $components = [];

        foreach ($preferenceService->categories() as $category => $definition) {
            $required = (bool) ($definition['required'] ?? false);

            $components[] = Section::make(__($definition['label']))
                ->description(__($definition['description']))
                ->schema([
                    Grid::make(2)->schema([
                        Toggle::make("categories.{$category}.database_enabled")
                            ->label(__('notifications.preferences.database'))
                            ->disabled($required)
                            ->dehydrated(! $required)
                            ->columnSpan(1),
                        Toggle::make("categories.{$category}.email_enabled")
                            ->label(__('notifications.preferences.email'))
                            ->disabled($required)
                            ->dehydrated(! $required)
                            ->columnSpan(1),
                    ]),
                ])
                ->columnSpanFull();
        }

        if ($this->hasTaskOffsets($preferenceService)) {
            $components[] = Section::make(__('notifications.preferences.task_offsets'))
                ->description(__('notifications.preferences.task_offsets_description'))
                ->schema([
                    Grid::make(2)->schema([
                        Toggle::make('task_offsets.notify_same_day')
                            ->label(__('notifications.preferences.offsets.same_day'))
                            ->columnSpan(1),
                        Toggle::make('task_offsets.notify_one_day_before')
                            ->label(__('notifications.preferences.offsets.one_day'))
                            ->columnSpan(1),
                        Toggle::make('task_offsets.notify_three_days_before')
                            ->label(__('notifications.preferences.offsets.three_days'))
                            ->columnSpan(1),
                        Toggle::make('task_offsets.notify_seven_days_before')
                            ->label(__('notifications.preferences.offsets.seven_days'))
                            ->columnSpan(1),
                    ]),
                ])
                ->columnSpanFull();
        }

        return $schema
            ->components($components)
            ->statePath('data');
    }

    public function save(NotificationPreferenceService $preferences): void
    {
        $user = $this->getAuthenticatedUser();
        $state = $this->form->getState();

        $preferences->savePreferences($user, $state['categories'] ?? []);

        if (isset($state['task_offsets']) && is_array($state['task_offsets'])) {
            $preferences->saveTaskReminderOffsets($user, $state['task_offsets']);
        }

        Notification::make()
            ->title(__('notifications.preferences.saved.title'))
            ->body(__('notifications.preferences.saved.body'))
            ->success()
            ->send();
    }

    public static function getNavigationLabel(): string
    {
        return __('notifications.preferences.navigation');
    }

    public function getTitle(): string
    {
        return __('notifications.preferences.title');
    }

    public function getHeading(): string
    {
        return __('notifications.preferences.heading');
    }

    public function getSubheading(): ?string
    {
        return __('notifications.preferences.subheading');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('notifications.preferences.actions.save'))
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }

    protected function getAuthenticatedUser(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }

    protected function hasTaskOffsets(NotificationPreferenceService $preferences): bool
    {
        return (bool) ($preferences->categories()['tasks']['task_offsets'] ?? false);
    }
}
