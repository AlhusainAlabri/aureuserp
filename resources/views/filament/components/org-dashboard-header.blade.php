@php
    use Filament\Support\Enums\Alignment;
@endphp

<header class="fi-org-dashboard-header relative mb-6 flex w-full flex-col gap-4">
    <div class="fi-org-dashboard-header__brand flex w-full justify-center px-2">
        @include('filament.components.org-dashboard-heading')
    </div>

    <div class="fi-org-dashboard-header__intro flex w-full flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        @include('filament.components.org-dashboard-subheading', [
            'greeting' => $greeting,
            'overview' => $overview,
        ])

        @if (filled($actions))
            <div class="fi-org-dashboard-header__actions shrink-0">
                <x-filament::actions
                    :actions="$actions"
                    :alignment="Alignment::End"
                />
            </div>
        @endif
    </div>
</header>
