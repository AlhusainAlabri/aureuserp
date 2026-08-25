<?php

namespace Webkul\Support\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmailLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'email_template_id' => $this->email_template_id,
            'recipient_email'   => $this->recipient_email,
            'recipient_name'    => $this->recipient_name,
            'subject'           => $this->subject,
            'variables'         => $this->variables,
            'sender_user_id'    => $this->sender_user_id,
            'mail_class'        => $this->mail_class,
            'status'            => $this->status,
            'error_message'     => $this->error_message,
            'sent_at'           => $this->sent_at,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
