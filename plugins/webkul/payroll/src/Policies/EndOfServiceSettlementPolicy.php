<?php

namespace Webkul\Payroll\Policies;

use Webkul\Payroll\Enums\SettlementStatus;
use Webkul\Payroll\Models\EndOfServiceSettlement;
use Webkul\Security\Models\User;

/**
 * Settlements are never deleted: a wrong one is cancelled and rebuilt.
 */
class EndOfServiceSettlementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_payroll_end::of::service::settlement');
    }

    public function view(User $user, EndOfServiceSettlement $settlement): bool
    {
        return $user->can('view_payroll_end::of::service::settlement');
    }

    public function create(User $user): bool
    {
        return $user->can('create_payroll_end::of::service::settlement');
    }

    public function update(User $user, EndOfServiceSettlement $settlement): bool
    {
        return $settlement->isEditable() && $user->can('update_payroll_end::of::service::settlement');
    }

    public function cancel(User $user, EndOfServiceSettlement $settlement): bool
    {
        return ! in_array($settlement->status, [SettlementStatus::Paid, SettlementStatus::Cancelled], true)
            && $user->can('update_payroll_end::of::service::settlement');
    }

    public function markPaid(User $user, EndOfServiceSettlement $settlement): bool
    {
        return $settlement->canBePaid() && $user->can('update_payroll_end::of::service::settlement');
    }
}
