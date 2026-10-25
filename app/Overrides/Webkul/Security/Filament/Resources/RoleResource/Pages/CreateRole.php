<?php

namespace Webkul\Security\Filament\Resources\RoleResource\Pages;

use App\Services\Security\RolePermissionResolver;
use App\Support\Audit\AuditRecorder;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Collection;
use Webkul\Security\Filament\Resources\RoleResource;
use Webkul\Security\Models\Role;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected ?bool $hasUnsavedDataChangesAlert = true;

    protected Collection $permissions;

    /**
     * @var array<string, string>
     */
    protected $listeners = [
        'advanced-permissions-updated' => 'syncAdvancedPermissions',
    ];

    /**
     * @param  list<string>  $permissions
     */
    public function syncAdvancedPermissions(array $permissions): void
    {
        $this->data['advanced_permissions'] = $permissions;
        $this->data['advanced_editor_open'] = true;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->permissions = app(RolePermissionResolver::class)->resolve($data);

        return [
            'name'       => $data['name'],
            'guard_name' => $data['guard_name'] ?? Utils::getFilamentAuthGuard(),
        ];
    }

    protected function afterCreate(): void
    {
        /** @var Role $record */
        $record = $this->record;

        $before = $record->permissions()->pluck('name');

        $record->syncPermissionsByNames($this->permissions);

        AuditRecorder::recordRolePermissions($record, $before, $record->permissions()->pluck('name'));

        $this->permissions = collect();
    }

    protected function getCreatedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('security::filament/resources/role/pages/create-role.notification.title'))
            ->body(__('security::filament/resources/role/pages/create-role.notification.body'));
    }
}
