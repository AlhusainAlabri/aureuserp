- **{{ __('assets::assets.models.asset') }}:** {{ $borrowing->asset?->name ?? '—' }} ({{ $borrowing->asset?->asset_number ?? '—' }})
- **{{ __('assets::assets.fields.employee') }}:** {{ $borrowing->employee?->name ?? '—' }}
- **{{ __('assets::assets.fields.due_at') }}:** {{ $borrowing->due_at?->translatedFormat('Y-m-d H:i') ?? '—' }}

@if (! empty($borrowing->rejection_reason))
- **{{ __('assets-extensions::fields.rejection_reason') }}:** {{ $borrowing->rejection_reason }}
@endif

@if (! empty($borrowing->returned_at))
- **{{ __('assets::assets.fields.returned_at') }}:** {{ $borrowing->returned_at->translatedFormat('Y-m-d H:i') }}
@endif
