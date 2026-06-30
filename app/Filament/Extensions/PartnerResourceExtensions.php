<?php

namespace App\Filament\Extensions;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Webkul\Partner\Enums\AccountType;

class PartnerResourceExtensions
{
    public static function localizeInfolist(Schema $schema): Schema
    {
        static::walkComponents(
            $schema->getComponents(withHidden: true),
            function (Component $component): void {
                static::localizeHardcodedGroupHeading($component, 'infolist');

                if ($component instanceof TextEntry) {
                    $label = static::partnerInfolistFieldLabel($component->getName());

                    if ($label !== null) {
                        $component->label($label);
                    }
                }

                if ($component instanceof ImageEntry && $component->getName() === 'avatar') {
                    $component->label(__('partners::filament/resources/partner.infolist.sections.general.fields.avatar'));
                }
            },
        );

        return $schema;
    }

    public static function localizeAddressForm(Schema $schema): Schema
    {
        static::walkComponents(
            $schema->getComponents(withHidden: true),
            function (Component $component): void {
                if ($component instanceof Radio && $component->getName() === 'sub_type') {
                    $component->label(__('partners::filament/resources/address.form.type'));
                }
            },
        );

        return $schema;
    }

    public static function localizeForm(Schema $schema): Schema
    {
        static::walkComponents(
            $schema->getComponents(withHidden: true),
            function (Component $component): void {
                static::localizeHardcodedGroupHeading($component, 'form');
                static::localizeHardcodedTabLabel($component, 'form');

                if ($component instanceof Radio && $component->getName() === 'account_type') {
                    $component->label(__('partners::filament/resources/partner.form.sections.general.fields.account-type'));
                }

                if ($component instanceof TextInput && $component->getName() === 'name') {
                    $component->label(__('partners::filament/resources/partner.form.sections.general.fields.name'));
                    $component->placeholder(fn (Get $get): string => $get('account_type') === AccountType::COMPANY->value
                        ? __('contacts-extensions::placeholders.name-company')
                        : __('contacts-extensions::placeholders.name-individual'));
                }

                if ($component instanceof TextInput && $component->getName() === 'tax_id') {
                    $component->placeholder(__('contacts-extensions::placeholders.tax-id'));
                }

                if ($component instanceof TextInput && $component->getName() === 'job_title') {
                    $component->placeholder(__('contacts-extensions::placeholders.job-title'));
                }

                if ($component instanceof FileUpload && $component->getName() === 'avatar') {
                    $component->label(__('partners::filament/resources/partner.form.sections.general.fields.avatar'));
                }

                if ($component instanceof Field) {
                    $label = static::partnerFormFieldLabel($component->getName());

                    if ($label !== null) {
                        $component->label($label);
                    }
                }
            },
        );

        return $schema;
    }

    public static function localizeTable(Table $table): Table
    {
        $columns = $table->getColumns();

        static::walkTableColumns($columns, function (Column $column): void {
            if (! $column instanceof TextColumn) {
                return;
            }

            $label = static::partnerTableColumnLabel($column->getName());

            if ($label !== null) {
                $column->label($label);
            }
        });

        return $table;
    }

    protected static function partnerInfolistFieldLabel(?string $name): ?string
    {
        return match ($name) {
            'account_type'     => __('partners::filament/resources/partner.infolist.sections.general.fields.account-type'),
            'name'             => __('partners::filament/resources/partner.infolist.sections.general.fields.name'),
            'tax_id'           => __('partners::filament/resources/partner.infolist.sections.general.fields.tax-id'),
            'job_title'        => __('partners::filament/resources/partner.infolist.sections.general.fields.job-title'),
            'phone'            => __('partners::filament/resources/partner.infolist.sections.general.fields.phone'),
            'mobile'           => __('partners::filament/resources/partner.infolist.sections.general.fields.mobile'),
            'email'            => __('partners::filament/resources/partner.infolist.sections.general.fields.email'),
            'website'          => __('partners::filament/resources/partner.infolist.sections.general.fields.website'),
            'title.name'       => __('partners::filament/resources/partner.infolist.sections.general.fields.title'),
            'tags.name'        => __('partners::filament/resources/partner.infolist.sections.general.fields.tags'),
            'street1'          => __('partners::filament/resources/partner.infolist.sections.general.address.fields.street1'),
            'street2'          => __('partners::filament/resources/partner.infolist.sections.general.address.fields.street2'),
            'city'             => __('partners::filament/resources/partner.infolist.sections.general.address.fields.city'),
            'zip'              => __('partners::filament/resources/partner.infolist.sections.general.address.fields.zip'),
            'country.name'     => __('partners::filament/resources/partner.infolist.sections.general.address.fields.country'),
            'state.name'       => __('partners::filament/resources/partner.infolist.sections.general.address.fields.state'),
            'user.name'        => __('partners::filament/resources/partner.infolist.tabs.sales-purchase.fields.responsible'),
            'company_registry' => __('partners::filament/resources/partner.infolist.tabs.sales-purchase.fields.company-id'),
            'reference'        => __('partners::filament/resources/partner.infolist.tabs.sales-purchase.fields.reference'),
            'industry.name'    => __('partners::filament/resources/partner.infolist.tabs.sales-purchase.fields.industry'),
            default            => null,
        };
    }

    protected static function partnerFormFieldLabel(?string $name): ?string
    {
        return match ($name) {
            'tax_id'           => __('partners::filament/resources/partner.form.sections.general.fields.tax-id'),
            'job_title'        => __('partners::filament/resources/partner.form.sections.general.fields.job-title'),
            'phone'            => __('partners::filament/resources/partner.form.sections.general.fields.phone'),
            'mobile'           => __('partners::filament/resources/partner.form.sections.general.fields.mobile'),
            'email'            => __('partners::filament/resources/partner.form.sections.general.fields.email'),
            'website'          => __('partners::filament/resources/partner.form.sections.general.fields.website'),
            'title_id'         => __('partners::filament/resources/partner.form.sections.general.fields.title'),
            'tags'             => __('partners::filament/resources/partner.form.sections.general.fields.tags'),
            'name'             => __('partners::filament/resources/partner.form.sections.general.fields.name'),
            'short_name'       => __('partners::filament/resources/partner.form.sections.general.fields.short-name'),
            'parent_id'        => __('partners::filament/resources/partner.form.sections.general.fields.company'),
            'street1'          => __('partners::filament/resources/partner.form.sections.general.address.fields.street1'),
            'street2'          => __('partners::filament/resources/partner.form.sections.general.address.fields.street2'),
            'city'             => __('partners::filament/resources/partner.form.sections.general.address.fields.city'),
            'zip'              => __('partners::filament/resources/partner.form.sections.general.address.fields.zip'),
            'state_id'         => __('partners::filament/resources/partner.form.sections.general.address.fields.state'),
            'country_id'       => __('partners::filament/resources/partner.form.sections.general.address.fields.country'),
            'industry_id'      => __('partners::filament/resources/partner.form.tabs.sales-purchase.fields.industry'),
            'user_id'          => __('partners::filament/resources/partner.form.tabs.sales-purchase.fields.responsible'),
            'company_registry' => __('partners::filament/resources/partner.form.tabs.sales-purchase.fields.company-id'),
            'reference'        => __('partners::filament/resources/partner.form.tabs.sales-purchase.fields.reference'),
            default            => null,
        };
    }

    protected static function partnerTableColumnLabel(?string $name): ?string
    {
        return match ($name) {
            'name'        => __('partners::filament/resources/partner.form.sections.general.fields.name'),
            'job_title'   => __('partners::filament/resources/partner.form.sections.general.fields.job-title'),
            'email'       => __('partners::filament/resources/partner.form.sections.general.fields.email'),
            'phone'       => __('partners::filament/resources/partner.form.sections.general.fields.phone'),
            'parent.name' => __('partners::filament/resources/partner.table.columns.parent'),
            default       => null,
        };
    }

    /**
     * @param  array<Column>  $columns
     */
    protected static function walkTableColumns(array $columns, Closure $callback): void
    {
        foreach ($columns as $column) {
            $callback($column);

            if (method_exists($column, 'getColumns')) {
                static::walkTableColumns($column->getColumns(), $callback);
            }
        }
    }

    protected static function localizeHardcodedGroupHeading(Component $component, string $context): void
    {
        $heading = match (true) {
            $component instanceof Section  => $component->getHeading(),
            $component instanceof Fieldset => $component->getLabel(),
            default                        => null,
        };

        if (! is_string($heading)) {
            return;
        }

        $translated = match ($heading) {
            'General' => __("partners::filament/resources/partner.{$context}.sections.general.title"),
            'Sales'   => __("partners::filament/resources/partner.{$context}.tabs.sales-purchase.groups.sales"),
            'Others'  => __("partners::filament/resources/partner.{$context}.tabs.sales-purchase.groups.others"),
            'Address' => __('partners::filament/resources/partner.infolist.sections.general.address.title'),
            default   => null,
        };

        if ($translated === null || str_starts_with($translated, 'partners::')) {
            return;
        }

        if ($component instanceof Section) {
            $component->heading($translated);
        } else {
            $component->label($translated);
        }
    }

    protected static function localizeHardcodedTabLabel(Component $component, string $context): void
    {
        if (! $component instanceof Tab) {
            return;
        }

        $label = $component->getLabel();

        if (! is_string($label)) {
            return;
        }

        $translated = match ($label) {
            'Sales and Purchases' => __("partners::filament/resources/partner.{$context}.tabs.sales-purchase.title"),
            default               => null,
        };

        if ($translated === null || str_starts_with($translated, 'partners::')) {
            return;
        }

        $component->label($translated);
    }

    /**
     * @param  array<Component>  $components
     */
    protected static function walkComponents(array $components, Closure $callback): void
    {
        foreach ($components as $component) {
            $callback($component);

            foreach (static::getNestedComponents($component) as $nested) {
                static::walkComponents([$nested], $callback);
            }
        }
    }

    /**
     * @return array<Component>
     */
    protected static function getNestedComponents(Component $component): array
    {
        if (method_exists($component, 'getTabs')) {
            $tabs = $component->getTabs();

            if (is_array($tabs) && $tabs !== []) {
                return $tabs;
            }
        }

        if (! method_exists($component, 'getChildComponents')) {
            return [];
        }

        $children = $component->getChildComponents();

        if ($children !== []) {
            return $children;
        }

        if (! method_exists($component, 'getDefaultChildComponents')) {
            return [];
        }

        $default = $component->getDefaultChildComponents();

        if ($default instanceof Schema) {
            return $default->getComponents(withHidden: true);
        }

        if (is_array($default)) {
            return $default;
        }

        return [];
    }
}
