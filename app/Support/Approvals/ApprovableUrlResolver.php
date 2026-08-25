<?php

namespace App\Support\Approvals;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class ApprovableUrlResolver
{
    public function resolve(?Model $approvable): ?string
    {
        if ($approvable === null) {
            return null;
        }

        $resourceClass = config('notifications.approvable_resources.'.$approvable::class);

        if (! is_string($resourceClass) || ! class_exists($resourceClass) || ! is_subclass_of($resourceClass, Resource::class)) {
            return null;
        }

        return $resourceClass::getUrl('view', ['record' => $approvable->getKey()]);
    }

    public function label(?Model $approvable): string
    {
        if ($approvable === null) {
            return __('approval-notifications.fallback.record');
        }

        if (method_exists($approvable, 'getModelTitle')) {
            return $approvable->getModelTitle();
        }

        if (filled($approvable->getAttribute('name'))) {
            return (string) $approvable->getAttribute('name');
        }

        return class_basename($approvable).' #'.$approvable->getKey();
    }
}
