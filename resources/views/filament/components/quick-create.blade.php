@php
    use Webkul\MyNotes\Filament\Pages\MyNotesPage;

    $isRtl = __('filament-panels::layout.direction') === 'rtl';

    $myNotesQuickItems = collect([
        'text'      => 'heroicon-m-document-text',
        'checklist' => 'heroicon-m-check-circle',
        'reminder'  => 'heroicon-m-bell',
        'voice'     => 'heroicon-m-microphone',
    ])->map(fn (string $icon, string $type): array => [
        'label' => __('my-notes::notes.types.'.$type),
        'url'   => class_exists(MyNotesPage::class)
            ? MyNotesPage::getUrl(['create' => $type])
            : url('/admin/my-notes?create='.$type),
        'icon'  => $icon,
    ])->values()->all();

    $groups = [
        [
            'label' => __('admin.quick_create.groups.contacts'),
            'icon'  => 'icon-contacts',
            'items' => [
                ['label' => __('admin.quick_create.items.contact'), 'url' => url('/admin/contact/contacts/create'), 'icon' => 'heroicon-o-user'],
            ],
        ],
        [
            'label' => __('admin.quick_create.groups.sales'),
            'icon'  => 'icon-sales',
            'items' => [
                ['label' => __('admin.quick_create.items.quotation'), 'url' => url('/admin/sale/orders/quotations/create'), 'icon' => 'heroicon-o-document-text'],
                ['label' => __('admin.quick_create.items.sale_order'), 'url' => url('/admin/sale/orders/orders/create'), 'icon' => 'heroicon-o-shopping-cart'],
                ['label' => __('admin.quick_create.items.customer'), 'url' => url('/admin/sale/orders/customers/create'), 'icon' => 'heroicon-o-user-circle'],
            ],
        ],
        [
            'label' => __('admin.quick_create.groups.purchases'),
            'icon'  => 'icon-purchases',
            'items' => [
                ['label' => __('admin.quick_create.items.rfq'), 'url' => url('/admin/purchase/orders/quotations/create'), 'icon' => 'heroicon-o-document-text'],
                ['label' => __('admin.quick_create.items.purchase_order'), 'url' => url('/admin/purchase/orders/purchase-orders/create'), 'icon' => 'heroicon-o-inbox-arrow-down'],
                ['label' => __('admin.quick_create.items.vendor'), 'url' => url('/admin/purchase/orders/vendors/create'), 'icon' => 'heroicon-o-building-storefront'],
            ],
        ],
        [
            'label' => __('admin.quick_create.groups.manufacturing'),
            'icon'  => 'icon-manufacturing',
            'items' => [
                ['label' => __('admin.quick_create.items.manufacturing_order'), 'url' => url('/admin/manufacturing/operations/manufacturing-orders/create'), 'icon' => 'heroicon-o-cog-6-tooth'],
                ['label' => __('admin.quick_create.items.bill_of_materials'), 'url' => url('/admin/manufacturing/products/bills-of-materials/create'), 'icon' => 'heroicon-o-list-bullet'],
            ],
        ],
        [
            'label' => __('admin.quick_create.groups.inventory'),
            'icon'  => 'icon-inventories',
            'items' => [
                ['label' => __('admin.quick_create.items.product'), 'url' => url('/admin/inventory/products/products/create'), 'icon' => 'heroicon-o-cube'],
                ['label' => __('admin.quick_create.items.receipt'), 'url' => url('/admin/inventory/operations/receipts/create'), 'icon' => 'heroicon-o-arrow-down-tray'],
                ['label' => __('admin.quick_create.items.delivery'), 'url' => url('/admin/inventory/operations/deliveries/create'), 'icon' => 'heroicon-o-truck'],
            ],
        ],
        [
            'label' => __('admin.quick_create.groups.invoices'),
            'icon'  => 'icon-invoices',
            'items' => [
                ['label' => __('admin.quick_create.items.invoice'), 'url' => url('/admin/invoices/customers/invoices/create'), 'icon' => 'heroicon-o-document-text'],
                ['label' => __('admin.quick_create.items.bill'), 'url' => url('/admin/invoices/vendors/bills/create'), 'icon' => 'heroicon-o-document-minus'],
                ['label' => __('admin.quick_create.items.payment'), 'url' => url('/admin/invoices/customers/payments/create'), 'icon' => 'heroicon-o-credit-card'],
            ],
        ],
        [
            'label' => __('admin.quick_create.groups.accounting'),
            'icon'  => 'icon-accounting',
            'items' => [
                ['label' => __('admin.quick_create.items.journal_entry'), 'url' => url('/admin/accounting/accounting/journal-entries/create'), 'icon' => 'heroicon-o-clipboard-document-list'],
                ['label' => __('admin.quick_create.items.invoice'), 'url' => url('/admin/accounting/customers/invoices/create'), 'icon' => 'heroicon-o-document-text'],
                ['label' => __('admin.quick_create.items.bill'), 'url' => url('/admin/accounting/vendors/bills/create'), 'icon' => 'heroicon-o-document-minus'],
            ],
        ],
        [
            'label' => __('admin.quick_create.groups.projects'),
            'icon'  => 'icon-projects',
            'items' => [
                ['label' => __('admin.quick_create.items.project'), 'url' => url('/admin/project/projects/create'), 'icon' => 'heroicon-o-folder-open'],
                ['label' => __('admin.quick_create.items.task'), 'url' => url('/admin/project/tasks/create'), 'icon' => 'heroicon-o-check-circle'],
            ],
        ],
        [
            'label' => __('admin.quick_create.groups.employees'),
            'icon'  => 'icon-employees',
            'items' => [
                ['label' => __('admin.quick_create.items.employee'), 'url' => url('/admin/employees/employees/create'), 'icon' => 'heroicon-o-identification'],
                ['label' => __('admin.quick_create.items.department'), 'url' => url('/admin/employees/departments/create'), 'icon' => 'heroicon-o-building-office-2'],
            ],
        ],
        [
            'label' => __('admin.quick_create.groups.time_off'),
            'icon'  => 'icon-time-offs',
            'items' => [
                ['label' => __('admin.quick_create.items.time_off'), 'url' => url('/admin/time-off/dashboard/my-time-offs/create'), 'icon' => 'heroicon-o-calendar-days'],
                ['label' => __('admin.quick_create.items.allocation'), 'url' => url('/admin/time-off/dashboard/my-allocations/create'), 'icon' => 'heroicon-o-clock'],
            ],
        ],
        [
            'label' => __('admin.quick_create.groups.recruitment'),
            'icon'  => 'icon-recruitments',
            'items' => [
                ['label' => __('admin.quick_create.items.candidate'), 'url' => url('/admin/recruitments/applications/candidates/create'), 'icon' => 'heroicon-o-user-plus'],
            ],
        ],
        [
            'label' => __('admin.quick_create.groups.meetings'),
            'icon'  => 'heroicon-o-clipboard-document-list',
            'items' => [
                ['label' => __('admin.quick_create.items.meeting'), 'url' => url('/admin/meetings/meetings/create'), 'icon' => 'heroicon-o-calendar'],
            ],
        ],
        [
            'label' => __('admin.quick_create.groups.my_notes'),
            'icon'  => 'heroicon-o-document-text',
            'items' => $myNotesQuickItems,
        ],
        [
            'label' => __('admin.quick_create.groups.correspondence'),
            'icon'  => 'heroicon-o-envelope',
            'items' => [
                ['label' => __('admin.quick_create.items.correspondence'), 'url' => url('/admin/correspondence/correspondences/create'), 'icon' => 'heroicon-o-envelope-open'],
            ],
        ],
        [
            'label' => __('admin.quick_create.groups.documents'),
            'icon'  => 'heroicon-o-archive-box',
            'items' => [
                ['label' => __('admin.quick_create.items.document_file'), 'url' => url('/admin/document-archive/files/create'), 'icon' => 'heroicon-o-document-plus'],
                ['label' => __('admin.quick_create.items.document_folder'), 'url' => url('/admin/document-archive/folders/create'), 'icon' => 'heroicon-o-folder-plus'],
            ],
        ],
    ];
@endphp

<div
    class="relative flex items-center"
    x-data="{ open: false }"
    x-on:keydown.escape.window="open = false"
>
    {{-- Trigger button --}}
    <button
        type="button"
        x-on:click="open = !open"
        x-on:click.outside="open = false"
        class="fi-quick-create-btn inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-semibold transition
               bg-primary-600 text-white hover:bg-primary-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-1
               dark:bg-primary-500 dark:hover:bg-primary-400"
    >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 shrink-0">
            <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
        </svg>
        <span class="hidden sm:inline">{{ __('admin.quick_create.button') }}</span>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
            class="h-3.5 w-3.5 shrink-0 opacity-75 transition-transform duration-150"
            :class="{ 'rotate-180': open }"
        >
            <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
        </svg>
    </button>

    {{-- Dropdown panel --}}
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95 translate-y-1"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-1"
        @class([
            'absolute top-full z-50 mt-2 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl dark:border-white/10 dark:bg-gray-900',
            'right-0 origin-top-right' => ! $isRtl,
            'left-0 origin-top-left' => $isRtl,
        ])
        style="width: min(720px, calc(100vw - 1.5rem));"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-2.5 dark:border-white/10">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">
                {{ __('admin.quick_create.heading') }}
            </p>
            <button
                type="button"
                x-on:click="open = false"
                class="rounded-md p-0.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                    <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                </svg>
            </button>
        </div>

        {{-- Groups grid --}}
        <div
            class="overflow-y-auto p-3"
            style="max-height: min(520px, calc(100vh - 120px));"
        >
            <div class="grid grid-cols-2 gap-x-2 gap-y-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                @foreach ($groups as $group)
                    <div class="flex flex-col">
                        {{-- Group header --}}
                        <div class="mb-1 flex items-center gap-1.5 rounded-md px-2 py-1">
                            <x-filament::icon
                                :icon="$group['icon']"
                                class="h-3.5 w-3.5 shrink-0 text-primary-500 dark:text-primary-400"
                            />
                            <span class="truncate text-xs font-semibold text-gray-500 dark:text-gray-400">
                                {{ $group['label'] }}
                            </span>
                        </div>

                        {{-- Group items --}}
                        @foreach ($group['items'] as $item)
                            <a
                                href="{{ $item['url'] }}"
                                x-on:click="open = false"
                                class="group flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-gray-700 transition
                                       hover:bg-primary-50 hover:text-primary-700
                                       dark:text-gray-300 dark:hover:bg-primary-900/40 dark:hover:text-primary-300"
                            >
                                <x-filament::icon
                                    :icon="$item['icon']"
                                    class="h-3.5 w-3.5 shrink-0 text-gray-400 transition group-hover:text-primary-600 dark:text-gray-500 dark:group-hover:text-primary-400"
                                />
                                <span class="truncate leading-snug">{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
