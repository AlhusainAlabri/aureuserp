<?php

namespace Webkul\Security\Filament\Resources\RoleResource\Pages;

use App\Services\Security\ModulePermissionResolver;
use App\Services\Security\RolePermissionResolver;
use App\Support\Audit\AuditRecorder;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Webkul\Security\Filament\Resources\RoleResource;
use Webkul\Security\Models\Role;

class EditRole extends EditRecord
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

    protected function getActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (DeleteAction $action, Model $record): void {
                    if (RoleResource::isProtectedRoleRecord($record)) {
                        Notification::make()
                            ->danger()
                            ->title(__('security::filament/resources/role.notification.system-role-delete.title'))
                            ->body(__('security::filament/resources/role.notification.system-role-delete.body'))
                            ->send();

                        $action->cancel();
                    }
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Role $record */
        $record = $this->record;
        $permissions = $record->permissions()->pluck('name');
        $resolver = app(ModulePermissionResolver::class);

        return RoleResource::prepareFormDefaults([
            ...$data,
            'starting_point'       => 'blank',
            'module_access'        => $resolver->inferModuleAccess($permissions),
            'advanced_permissions' => $permissions->all(),
            'advanced_editor_open' => false,
        ], $record);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Role $record */
        $record = $this->record;

        $this->permissions = app(RolePermissionResolver::class)->resolve($data, $record);

        return [
            'name'       => $data['name'],
            'guard_name' => $data['guard_name'] ?? Utils::getFilamentAuthGuard(),
        ];
    }

    protected function afterSave(): void
    {
        /** @var Role $record */
        $record = $this->record;

        $before = $record->permissions()->pluck('name');

        $record->syncPermissionsByNames($this->permissions);

        AuditRecorder::recordRolePermissions($record, $before, $record->permissions()->pluck('name'));

        $this->permissions = collect();
    }

    protected function getSavedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('security::filament/resources/role/pages/edit-role.notification.title'))
            ->body(__('security::filament/resources/role/pages/edit-role.notification.body'));
    }
}
