<?php

namespace App\Filament\Clusters\System\Pages;

use App\Enums\Operations\HealthStatus;
use App\Jobs\Operations\RunHealthCheckJob;
use App\Mail\Operations\SystemTestMail;
use App\Models\Operations\SystemHealthSnapshot;
use App\Services\Operations\HealthCheckService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class SystemHealthDashboard extends Page
{
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-heart';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.system-health-dashboard';

    /** @var array<string, mixed>|null */
    public ?array $snapshot = null;

    public function mount(HealthCheckService $healthChecks): void
    {
        $this->snapshot = $healthChecks->latest();
    }

    public static function getNavigationGroup(): string
    {
        return __('operations.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('operations.health.navigation');
    }

    public function getTitle(): string
    {
        return __('operations.health.title');
    }

    public function getSubheading(): ?string
    {
        if ($this->snapshot === null) {
            return __('operations.health.no_checks_yet');
        }

        return __('operations.health.last_checked', [
            'time' => $this->snapshot['checked_at'] ?? '—',
        ]);
    }

    public function overallStatus(): HealthStatus
    {
        $value = $this->snapshot['overall_status'] ?? null;

        if ($value instanceof HealthStatus) {
            return $value;
        }

        if (is_string($value)) {
            return HealthStatus::tryFrom($value) ?? HealthStatus::Unknown;
        }

        return HealthStatus::Unknown;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('runHealthCheck')
                ->label(__('operations.health.run_check'))
                ->icon('heroicon-o-arrow-path')
                ->authorize(fn (): bool => auth()->user()?->can('run_system_health_check') ?? false)
                ->action(function (): void {
                    RunHealthCheckJob::dispatch('manual', auth()->id());

                    Notification::make()
                        ->title(__('operations.health.queued'))
                        ->success()
                        ->send();
                }),
            Action::make('sendTestEmail')
                ->label(__('operations.notifications.send_test_email'))
                ->icon('heroicon-o-envelope')
                ->authorize(fn (): bool => auth()->user()?->can('manage_operations_settings') ?? false)
                ->action(function (): void {
                    $email = auth()->user()?->email;

                    if (! filled($email)) {
                        Notification::make()
                            ->title(__('operations.notifications.test_email_missing'))
                            ->danger()
                            ->send();

                        return;
                    }

                    Mail::to($email)->queue(new SystemTestMail);

                    Notification::make()
                        ->title(__('operations.notifications.test_email_sent', ['email' => $email]))
                        ->success()
                        ->send();
                }),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function history(): array
    {
        if (! Schema::hasTable('system_health_snapshots')) {
            return [];
        }

        return SystemHealthSnapshot::query()
            ->latest('checked_at')
            ->limit(14)
            ->get(['overall_status', 'checked_at'])
            ->map(fn (SystemHealthSnapshot $snapshot): array => [
                'status' => $snapshot->overall_status,
                'time'   => $snapshot->checked_at?->toDateTimeString(),
            ])
            ->all();
    }
}
