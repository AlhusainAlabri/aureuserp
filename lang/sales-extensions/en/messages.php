<?php

return [
    'no-email-address'            => 'No email address',
    'email-service-error'         => 'Email service error: :message',
    'cancel-has-posted-invoice'   => 'The sales order cannot be cancelled because it has a posted invoice. Issue a credit note first.',
    'cancel-has-delivered-goods'  => 'The sales order cannot be cancelled because goods have been delivered. Create a return that updates the delivered quantities first.',
    'credit-note-from-return'     => [
        'label'       => 'Create credit note',
        'heading'     => 'Create a credit note for the return',
        'description' => 'A draft credit note is created for the returned goods only, against the posted invoice. Review it, then confirm. If you already credited these goods by hand, do not create another.',
        'created'     => ':count credit note(s) created (draft)',
        'nothing'     => 'There are no returned quantities waiting for a credit note. The return must be done with "update delivered quantities" on, and the invoice posted.',
        'reference'   => 'Return :return against :invoice',
        'chatter'     => 'This credit note was created from the return :return.',
    ],
];
