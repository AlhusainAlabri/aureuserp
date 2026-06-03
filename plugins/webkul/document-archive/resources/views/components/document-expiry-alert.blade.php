@props(['record'])

@php
    $record = $record ?? (isset($getRecord) ? $getRecord() : null);
@endphp

@if ($record)
    @php
        $status = $record->getExpiryStatus();
        $days = $record->getDaysUntilExpiry();
    @endphp

@if ($status === 'expired')
    <div class="rounded-xl border border-danger-300 bg-danger-50 px-4 py-3 dark:border-danger-500/40 dark:bg-danger-500/10">
        <div class="flex items-start gap-3">
            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="mt-0.5 h-5 w-5 shrink-0 text-danger-600 dark:text-danger-400" />
            <div>
                <p class="text-sm font-semibold text-danger-800 dark:text-danger-200">
                    {{ __('document-archive::document-archive.expiry.expired_title') }}
                </p>
                <p class="mt-1 text-sm text-danger-700 dark:text-danger-300">
                    {{ __('document-archive::document-archive.expiry.expired_body', [
                        'date' => $record->expiry_date->translatedFormat('d M Y'),
                    ]) }}
                </p>
            </div>
        </div>
    </div>
@elseif ($status === 'expiring_soon')
    <div class="rounded-xl border border-warning-300 bg-warning-50 px-4 py-3 dark:border-warning-500/40 dark:bg-warning-500/10">
        <div class="flex items-start gap-3">
            <x-filament::icon icon="heroicon-o-clock" class="mt-0.5 h-5 w-5 shrink-0 text-warning-600 dark:text-warning-400" />
            <div>
                <p class="text-sm font-semibold text-warning-800 dark:text-warning-200">
                    {{ __('document-archive::document-archive.expiry.expiring_soon_title') }}
                </p>
                <p class="mt-1 text-sm text-warning-700 dark:text-warning-300">
                    {{ __('document-archive::document-archive.expiry.expiring_soon_body', [
                        'date' => $record->expiry_date->translatedFormat('d M Y'),
                        'days' => max($days ?? 0, 0),
                    ]) }}
                </p>
            </div>
        </div>
    </div>
@endif
@endif
