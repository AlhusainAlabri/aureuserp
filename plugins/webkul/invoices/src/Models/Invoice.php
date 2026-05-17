<?php

namespace Webkul\Invoice\Models;

use App\Traits\HasApprovalWorkflow;
use Webkul\Account\Models\Move as BaseMove;

class Invoice extends BaseMove
{
    use HasApprovalWorkflow;

    public function getModelTitle(): string
    {
        return __('invoices::models/invoice.title');
    }
}
