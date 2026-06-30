<?php

it('uses arabic validation messages for required fields', function (): void {
    app()->setLocale('ar');

    $message = __('validation.required', ['attribute' => __('attributes.due_at')]);

    expect($message)->toBe('حقل تاريخ الاستحقاق مطلوب.');
});

it('uses translated admin footer sentence in arabic', function (): void {
    app()->setLocale('ar');

    expect(__('admin.footer.sentence', ['version' => '1.0.1']))
        ->toBe('طُور بواسطة NODHUM TECHNOLOGY · v1.0.1');
});
