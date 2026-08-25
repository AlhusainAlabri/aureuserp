<?php

namespace Webkul\Support\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

final class TransactionalMail
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function enrichPayload(array $payload, ?Authenticatable $sender = null): array
    {
        $sender ??= Auth::user();

        $payload['from'] = [
            'address' => config('mail.from.address'),
            'name'    => config('mail.from.name'),
        ];

        if ($sender !== null) {
            $payload['reply_to'] = [
                'address' => $sender->email,
                'name'    => $sender->name,
            ];

            if (method_exists($sender, 'defaultCompany') && $sender->defaultCompany) {
                $payload['from']['company'] = $sender->defaultCompany->toArray();
            }
        }

        return $payload;
    }
}
