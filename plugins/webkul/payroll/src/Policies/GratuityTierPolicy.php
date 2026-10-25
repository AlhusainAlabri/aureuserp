<?php

namespace Webkul\Payroll\Policies;

use Webkul\Payroll\Models\GratuityTier;
use Webkul\Security\Models\User;

/**
 * Gratuity tiers are history: they can be added and corrected but never deleted.
 */
class GratuityTierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_payroll_gratuity::tier');
    }

    public function view(User $user, GratuityTier $tier): bool
    {
        return $user->can('view_payroll_gratuity::tier');
    }

    public function create(User $user): bool
    {
        return $user->can('create_payroll_gratuity::tier');
    }

    public function update(User $user, GratuityTier $tier): bool
    {
        return $user->can('update_payroll_gratuity::tier');
    }
}
