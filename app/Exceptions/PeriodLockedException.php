<?php

namespace App\Exceptions;

use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    /**
     * An API or other JSON request gets a 422 with the message; anything else falls back to the default handling.
     */
    public function render(Request $request): JsonResponse|false
    {
        if (! $request->expectsJson()) {
            return false;
        }

        return new JsonResponse(['message' => $this->getMessage()], 422);
    }
}
