<?php

namespace Webkul\Attendance\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * An IPv4 or IPv6 address, or a CIDR range (address/mask with a mask of 0-32 for IPv4 and 0-128 for IPv6).
 */
class IpAddressOrCidr implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::isValid($value)) {
            $fail(__('attendances::attendances.settings.errors.invalid_ip', [
                'value' => is_scalar($value) ? (string) $value : '',
            ]));
        }
    }

    public static function isValid(string $entry): bool
    {
        $entry = trim($entry);

        if ($entry === '') {
            return false;
        }

        $parts = explode('/', $entry);

        if (count($parts) > 2) {
            return false;
        }

        $address = $parts[0];
        $isIpv6 = str_contains($address, ':');

        if (filter_var($address, FILTER_VALIDATE_IP, $isIpv6 ? FILTER_FLAG_IPV6 : FILTER_FLAG_IPV4) === false) {
            return false;
        }

        if (count($parts) === 1) {
            return true;
        }

        $mask = $parts[1];

        return $mask !== ''
            && strlen($mask) <= 3
            && ctype_digit($mask)
            && (int) $mask <= ($isIpv6 ? 128 : 32);
    }
}
