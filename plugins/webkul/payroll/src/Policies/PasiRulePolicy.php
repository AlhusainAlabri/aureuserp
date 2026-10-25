<?php

namespace Webkul\Payroll\Policies;

use Webkul\Payroll\Models\PasiRule;
use Webkul\Security\Models\User;

/**
 * PASI rules are history: they can be added and corrected but never deleted.
 */
class PasiRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_payroll_pasi::rule');
    }

    public function view(User $user, PasiRule $pasiRule): bool
    {
        return $user->can('view_payroll_pasi::rule');
    }

    public function create(User $user): bool
    {
        return $user->can('create_payroll_pasi::rule');
    }

    public function update(User $user, PasiRule $pasiRule): bool
    {
        return $user->can('update_payroll_pasi::rule');
    }
}
