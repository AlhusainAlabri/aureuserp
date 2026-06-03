<div class="fi-org-dashboard-subheading min-w-0 flex-1 text-start">
    <h1 class="text-2xl font-bold tracking-tight text-gray-950 sm:text-3xl dark:text-white">
        {{ $greeting }}
    </h1>

    @if (filled($overview))
        <p class="mt-2 max-w-2xl text-base text-gray-600 sm:text-lg dark:text-gray-400">
            {{ $overview }}
        </p>
    @endif
</div>
