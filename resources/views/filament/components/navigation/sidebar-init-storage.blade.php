<script>
    ;(() => {
        const collapsedGroupsKey = 'collapsedGroups'
        const defaultsVersionKey = 'erpModuleSidebarDefaultsVersion'
        const defaultsVersion = '1'
        const defaultsState = `${defaultsVersion}:${document.documentElement.lang || 'default'}`
        const raw = localStorage.getItem(collapsedGroupsKey)
        const moduleSidebar = document.querySelector(
            '.erp-nav-sidebar--module-mode',
        )
        let hasValidCollapsedGroups = false

        if (raw !== null && raw !== 'null' && raw !== '') {
            try {
                hasValidCollapsedGroups = Array.isArray(JSON.parse(raw))
            } catch (error) {
                hasValidCollapsedGroups = false
            }
        }

        if (
            moduleSidebar &&
            (
                ! hasValidCollapsedGroups ||
                localStorage.getItem(defaultsVersionKey) !== defaultsState
            )
        ) {
            const defaultCollapsedGroups = JSON.parse(
                moduleSidebar.dataset.erpDefaultCollapsedGroups ?? '[]',
            )

            localStorage.setItem(
                collapsedGroupsKey,
                JSON.stringify(
                    Array.isArray(defaultCollapsedGroups)
                        ? defaultCollapsedGroups
                        : [],
                ),
            )
            localStorage.setItem(defaultsVersionKey, defaultsState)
        } else if (! hasValidCollapsedGroups) {
            localStorage.setItem(collapsedGroupsKey, JSON.stringify([]))
        }

        window.__erpSidebarHadPersistedOpenState =
            localStorage.getItem('isOpen') !== null ||
            localStorage.getItem('isOpenDesktop') !== null

        localStorage.removeItem('erpModuleSidebarCollapsedGroups')
    })()
</script>
