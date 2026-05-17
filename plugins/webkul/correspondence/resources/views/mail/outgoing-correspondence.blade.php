<div dir="rtl" style="font-family: sans-serif; line-height: 1.8;">
    <p>{{ __('correspondence::correspondence.reference_number') }}: {{ $correspondence->reference_number }}</p>
    <p>{{ __('correspondence::correspondence.subject') }}: {{ $correspondence->subject }}</p>
    <hr>
    {!! $correspondence->body !!}
</div>
