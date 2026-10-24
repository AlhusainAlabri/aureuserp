{{-- Signed-in simple pages (e.g. the profile page) already show the user menu at the end corner, so sit beside it. --}}
<div @class(['absolute top-4 z-50', 'end-4' => ! auth()->check(), 'end-20' => auth()->check()])>
    @include('filament.components.language-switcher')
</div>
