<?php

namespace App\Exceptions;

use Carbon\CarbonInterface;
use RuntimeException;

/**
 * Thrown when a change touches an accounting period that has been locked.
 */
class PeriodLockedException extends RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function forLockDate(CarbonInterface $lockDate): self
    {
        return new self(__('audit.lock.refused', ['date' => $lockDate->toDateString()]));
    }
}
