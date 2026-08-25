@php
    use App\Support\Navigation\NavigationMode;

    $navigationMode = NavigationMode::resolve();
@endphp

@if ($navigationMode->usesModuleSidebar())
    <script>
        document.addEventListener('alpine:init', () => {
            const sidebarStore = window.Alpine.store('sidebar')

            if (! sidebarStore) {
                return
            }

            const defaultState = @js($navigationMode->sidebarDefaultState->value)
            const breakpoint = 1024
            const persistKey = '_x_isOpen'
            const persistDesktopKey = '_x_isOpenDesktop'
            const hasPersistedPreference =
                localStorage.getItem(persistKey) !== null ||
                localStorage.getItem(persistDesktopKey) !== null

            if (! hasPersistedPreference) {
                const resolveOpenState = () => {
                    if (defaultState === 'expanded') {
                        return true
                    }

                    if (defaultState === 'collapsed') {
                        return false
                    }

                    return window.innerWidth >= breakpoint
                }

                const shouldOpen = resolveOpenState()

                sidebarStore.isOpen = shouldOpen
                sidebarStore.isOpenDesktop = shouldOpen
            }
        })

        document.addEventListener('livewire:navigated', () => {
            if (window.innerWidth >= 1024) {
                return
            }

            const sidebarStore = window.Alpine?.store('sidebar')

            if (sidebarStore?.isOpen) {
                sidebarStore.close()
            }
        })
    </script>
@endif
