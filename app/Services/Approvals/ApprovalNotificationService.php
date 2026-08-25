<?php

namespace App\Services\Approvals;

use App\Mail\Approvals\ApprovalWorkflowMail;
use App\Services\Notifications\NotificationChannel;
use App\Services\Notifications\NotificationDedupService;
use App\Services\Notifications\NotificationDefinition;
use App\Services\Notifications\NotificationDispatcher;
use App\Support\Approvals\ApprovableUrlResolver;
use Filament\Actions\Action;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Webkul\Security\Models\User;
use Wezlo\FilamentApproval\FilamentApprovalPlugin;
use Wezlo\FilamentApproval\Models\Approval;
use Wezlo\FilamentApproval\Models\ApprovalStepInstance;

class ApprovalNotificationService
{
    public function __construct(
        private readonly NotificationDispatcher $notifications,
        private readonly NotificationDedupService $dedup,
        private readonly ApprovableUrlResolver $urls,
    ) {}

    public function notifyStepApprovers(ApprovalStepInstance $stepInstance, string $notificationKey): void
    {
        if (! Schema::hasTable('approval_step_instances')) {
            return;
        }

        $stepInstance->loadMissing(['approval.approvable', 'step']);
        $approvable = $stepInstance->approval?->approvable;
        $viewUrl = $this->urls->resolve($approvable);
        $recordLabel = $this->urls->label($approvable);
        $stepName = $stepInstance->step?->name ?? __('approval-notifications.fallback.step');

        foreach ($this->resolveUsers($stepInstance->assigned_approver_ids ?? []) as $user) {
            $dedupKey = "{$notificationKey}:step:{$stepInstance->id}:user:{$user->id}";

            $this->dedup->rememberIfNotSent($dedupKey, function () use (
                $user,
                $notificationKey,
                $recordLabel,
                $stepName,
                $viewUrl,
                $stepInstance,
            ): void {
                $this->dispatchToUser(
                    user: $user,
                    notificationKey: $notificationKey,
                    title: __("approval-notifications.{$notificationKey}.title", ['step' => $stepName]),
                    body: __("approval-notifications.{$notificationKey}.body", [
                        'record' => $recordLabel,
                        'step'   => $stepName,
                    ]),
                    color: $this->colorForKey($notificationKey),
                    viewUrl: $viewUrl,
                    mailFactory: fn (User $recipient): ApprovalWorkflowMail => new ApprovalWorkflowMail(
                        recipient: $recipient,
                        notificationKey: $notificationKey,
                        recordLabel: $recordLabel,
                        stepName: $stepName,
                        viewUrl: $viewUrl,
                        deadline: $stepInstance->sla_deadline,
                    ),
                );
            });
        }
    }

    public function notifySubmitter(Approval $approval, string $notificationKey): void
    {
        if (! $approval->submitted_by) {
            return;
        }

        $approval->loadMissing('approvable');
        $submitter = $this->resolveUsers([$approval->submitted_by])->first();

        if (! $submitter instanceof User) {
            return;
        }

        $approvable = $approval->approvable;
        $viewUrl = $this->urls->resolve($approvable);
        $recordLabel = $this->urls->label($approvable);
        $dedupKey = "{$notificationKey}:approval:{$approval->id}:submitter:{$submitter->id}";

        $this->dedup->rememberIfNotSent($dedupKey, function () use (
            $submitter,
            $notificationKey,
            $recordLabel,
            $viewUrl,
        ): void {
            $this->dispatchToUser(
                user: $submitter,
                notificationKey: $notificationKey,
                title: __("approval-notifications.{$notificationKey}.title"),
                body: __("approval-notifications.{$notificationKey}.body", [
                    'record' => $recordLabel,
                ]),
                color: $this->colorForKey($notificationKey),
                viewUrl: $viewUrl,
                mailFactory: fn (User $recipient): ApprovalWorkflowMail => new ApprovalWorkflowMail(
                    recipient: $recipient,
                    notificationKey: $notificationKey,
                    recordLabel: $recordLabel,
                    viewUrl: $viewUrl,
                ),
            );
        });
    }

    protected function dispatchToUser(
        User $user,
        string $notificationKey,
        string $title,
        string $body,
        string $color,
        ?string $viewUrl,
        callable $mailFactory,
    ): void {
        $actions = [];

        if ($viewUrl) {
            $actions[] = Action::make('view')
                ->label(__('approval-notifications.actions.view'))
                ->url($viewUrl);
        }

        $this->notifications->dispatch(
            NotificationDefinition::make($notificationKey)
                ->recipients([$user])
                ->title($title)
                ->body($body)
                ->icon('heroicon-o-clipboard-document-check')
                ->color($color)
                ->required()
                ->actions($actions)
                ->channels([NotificationChannel::Database, NotificationChannel::Mail])
                ->mail(fn (User $recipient) => $mailFactory($recipient))
                ->build(),
        );
    }

    /** @param  array<int, int|string>  $userIds */
    protected function resolveUsers(array $userIds): Collection
    {
        $userModel = FilamentApprovalPlugin::resolveUserModel();

        return $userModel::query()
            ->whereIn('id', array_values(array_unique(array_filter($userIds))))
            ->get();
    }

    protected function colorForKey(string $notificationKey): string
    {
        return match ($notificationKey) {
            'approvals.rejected'  => 'danger',
            'approvals.completed' => 'success',
            'approvals.escalated' => 'danger',
            default               => 'warning',
        };
    }
}
