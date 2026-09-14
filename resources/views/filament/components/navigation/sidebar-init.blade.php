@php
    use App\Support\Navigation\NavigationMode;

    $navigationMode = NavigationMode::resolve();
@endphp

<script>
    ;(() => {
    if (window.__erpNavigationSidebarScriptInitialized) {
        return
    }

    window.__erpNavigationSidebarScriptInitialized = true

    const patchFilamentSidebarStore = () => {
        const sidebarStore = window.Alpine?.store('sidebar')

        if (! sidebarStore) {
            return null
        }

        const ensureCollapsedGroups = () => {
            if (
                sidebarStore.collapsedGroups === null ||
                ! Array.isArray(sidebarStore.collapsedGroups)
            ) {
                sidebarStore.collapsedGroups = []
            }
        }

        if (! sidebarStore.__erpSidebarPatched) {
            ;['groupIsCollapsed', 'collapseGroup', 'toggleCollapsedGroup'].forEach(
                (methodName) => {
                    if (typeof sidebarStore[methodName] !== 'function') {
                        return
                    }

                    const original = sidebarStore[methodName].bind(sidebarStore)

                    sidebarStore[methodName] = (...args) => {
                        ensureCollapsedGroups()

                        return original(...args)
                    }
                },
            )

            sidebarStore.__erpSidebarPatched = true
        }

        ensureCollapsedGroups()

        return sidebarStore
    }

    const clearStaleModuleSidebarStyles = () => {
        document
            .querySelectorAll(
                '.erp-nav-sidebar--module-mode .fi-sidebar-group-items',
            )
            .forEach((items) => items.style.removeProperty('display'))
    }

    const normalizeErpNavigationFilter = (value) =>
        String(value ?? '').trim().toLocaleLowerCase()

    const applyErpNavigationFilter = (value) => {
        const sidebar = document.querySelector('.erp-nav-sidebar--module-mode')

        if (! sidebar) {
            return []
        }

        const query = normalizeErpNavigationFilter(value)
        const matchingGroups = []

        sidebar.querySelectorAll('.fi-sidebar-group').forEach((group) => {
            const items = group.querySelectorAll('.fi-sidebar-item')
            const groupItems = group.querySelector(
                ':scope > .fi-sidebar-group-items',
            )

            group.classList.remove(
                'erp-nav-filter-hidden',
                'erp-nav-filter-match',
            )
            items.forEach((item) =>
                item.classList.remove('erp-nav-filter-hidden'),
            )

            if (query === '') {
                return
            }

            const groupMatches = normalizeErpNavigationFilter(
                group.dataset.groupLabel,
            ).includes(query)
            let hasMatchingItem = false

            items.forEach((item) => {
                const label = item.querySelector('.fi-sidebar-item-label')
                const itemMatches =
                    groupMatches ||
                    normalizeErpNavigationFilter(label?.textContent).includes(
                        query,
                    )

                item.classList.toggle(
                    'erp-nav-filter-hidden',
                    ! itemMatches,
                )
                hasMatchingItem ||= itemMatches
            })

            const hasMatch = groupMatches || hasMatchingItem

            group.classList.toggle('erp-nav-filter-hidden', ! hasMatch)
            group.classList.toggle('erp-nav-filter-match', hasMatch)

            if (hasMatch) {
                matchingGroups.push(group.dataset.groupLabel)
                groupItems?.removeAttribute('hidden')
            }
        })

        return matchingGroups
    }

    const registerErpNavigationFilterStore = () => {
        if (
            ! window.Alpine ||
            window.Alpine.store('erpNavigationFilter')
        ) {
            return
        }

        window.Alpine.store('erpNavigationFilter', {
            query: '',
            collapsedGroupsBeforeFilter: null,

            setQuery(value) {
                this.query = value
                const normalizedQuery = normalizeErpNavigationFilter(value)

                if (normalizedQuery === '') {
                    applyErpNavigationFilter('')
                    syncErpModuleSidebarCollapsedGroups()
                    this.collapsedGroupsBeforeFilter = null

                    return
                }

                const sidebarStore = window.Alpine?.store('sidebar')

                if (
                    this.collapsedGroupsBeforeFilter === null &&
                    Array.isArray(sidebarStore?.collapsedGroups)
                ) {
                    this.collapsedGroupsBeforeFilter = [
                        ...sidebarStore.collapsedGroups,
                    ]
                }

                const matchingGroups = applyErpNavigationFilter(value)

                if (
                    sidebarStore &&
                    Array.isArray(this.collapsedGroupsBeforeFilter)
                ) {
                    sidebarStore.collapsedGroups =
                        this.collapsedGroupsBeforeFilter.filter(
                            (group) => ! matchingGroups.includes(group),
                        )
                }
            },

            clear() {
                this.setQuery('')
            },

            apply() {
                this.setQuery(this.query)
            },
        })
    }

    const syncErpModuleSidebarCollapsedGroups = () => {
        const sidebar = document.querySelector('.erp-nav-sidebar--module-mode')

        if (! sidebar) {
            return
        }

        const sidebarStore = patchFilamentSidebarStore()

        if (! sidebarStore) {
            return
        }

        const activeFilterQuery =
            window.Alpine?.store('erpNavigationFilter')?.query?.trim() ?? ''

        if (activeFilterQuery !== '') {
            return
        }

        const collapsedGroups = []

        sidebar.querySelectorAll('.fi-sidebar-group').forEach((group) => {
            const label = group.dataset.groupLabel

            if (! label || group.classList.contains('fi-active')) {
                return
            }

            collapsedGroups.push(label)
        })

        sidebarStore.collapsedGroups = collapsedGroups
        localStorage.setItem(
            'collapsedGroups',
            JSON.stringify(collapsedGroups),
        )
    }

    const initializeErpModuleSidebarGroups = () => {
        const sidebar = document.querySelector('.erp-nav-sidebar--module-mode')

        if (! sidebar) {
            return
        }

        clearStaleModuleSidebarStyles()

        syncErpModuleSidebarCollapsedGroups()
    }

    const bootErpSidebar = () => {
        patchFilamentSidebarStore()

        @if ($navigationMode->usesModuleSidebar())
            initializeErpModuleSidebarGroups()

            const sidebarStore = window.Alpine?.store('sidebar')

            if (! sidebarStore) {
                return
            }

            const defaultState = @js($navigationMode->sidebarDefaultState->value);
            const breakpoint = 1024
            const hasPersistedPreference =
                window.__erpSidebarHadPersistedOpenState === true

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
        @endif
    }

    const initializeErpNavigation = () => {
        registerErpNavigationFilterStore()
        bootErpSidebar()
        window.Alpine?.store('erpNavigationFilter')?.apply()
    }

    document.addEventListener('alpine:init', initializeErpNavigation)
    document.addEventListener('alpine:initialized', initializeErpNavigation)

    initializeErpNavigation()

    document.addEventListener('livewire:navigate', () => {
        patchFilamentSidebarStore()
    })

    document.addEventListener('livewire:navigated', () => {
        patchFilamentSidebarStore()
        clearStaleModuleSidebarStyles()

        @if ($navigationMode->usesModuleSidebar())
            requestAnimationFrame(() => {
                initializeErpModuleSidebarGroups()
                window.Alpine?.store('erpNavigationFilter')?.apply()
            })

            if (window.innerWidth >= 1024) {
                return
            }

            const sidebarStore = window.Alpine?.store('sidebar')

            if (sidebarStore?.isOpen) {
                sidebarStore.close()
            }
        @endif
    })
    })()
</script>
