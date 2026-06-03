@php
    use Illuminate\Support\Facades\Schema;
    use Webkul\PluginManager\Package;
@endphp

@if (Schema::hasTable('notes') && Package::isPluginInstalled('my-notes') && filament()->auth()->check())
    @livewire(\Webkul\MyNotes\Livewire\QuickNoteTopbar::class)
@endif
