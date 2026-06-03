<?php

namespace Webkul\DocumentArchive\Filament\Forms;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Webkul\DocumentArchive\Services\DocumentTagService;

class DocumentTagForm
{
    /**
     * @return array<int, Component>
     */
    public static function schema(bool $includeAdvanced = true): array
    {
        $fields = [
            static::tagNamesSelect()->columnSpanFull(),
        ];

        if ($includeAdvanced) {
            $fields[] = Section::make(__('document-archive::document-archive.tags.advanced'))
                ->description(__('document-archive::document-archive.tags.advanced_help'))
                ->collapsed()
                ->schema([
                    Repeater::make('tags')
                        ->label(__('document-archive::document-archive.tags.custom_colors'))
                        ->schema([
                            TextInput::make('name')
                                ->label(__('document-archive::document-archive.fields.tag_name'))
                                ->required(),
                            ColorPicker::make('color')
                                ->label(__('document-archive::document-archive.fields.tag_color')),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull();
        }

        return $fields;
    }

    /**
     * Compact tag picker for resource metadata panels.
     *
     * @return array<int, Component>
     */
    public static function metadataSchema(): array
    {
        return [
            static::tagNamesSelect(compact: true)->columnSpan(1),
        ];
    }

    public static function tagNamesSelect(bool $compact = false): Select
    {
        return Select::make('tag_names')
            ->label(__('document-archive::document-archive.fields.tags'))
            ->placeholder(__('document-archive::document-archive.tags.placeholder'))
            ->helperText(__($compact
                ? 'document-archive::document-archive.tags.select_help_short'
                : 'document-archive::document-archive.tags.select_help'))
            ->multiple()
            ->searchable()
            ->preload()
            ->options(fn (): array => app(DocumentTagService::class)->optionsForSelect())
            ->getOptionLabelsUsing(function (array $values): array {
                $options = app(DocumentTagService::class)->optionsForSelect();

                return collect($values)
                    ->mapWithKeys(fn (string $value): array => [$value => $options[$value] ?? $value])
                    ->all();
            })
            ->createOptionForm([
                TextInput::make('name')
                    ->label(__('document-archive::document-archive.fields.tag_name'))
                    ->required()
                    ->maxLength(50),
                ColorPicker::make('color')
                    ->label(__('document-archive::document-archive.fields.tag_color'))
                    ->default(DocumentTagService::DEFAULT_TAG_COLOR),
            ])
            ->createOptionUsing(function (array $data): string {
                $name = trim((string) ($data['name'] ?? ''));

                app(DocumentTagService::class)->rememberNewTagColor(
                    $name,
                    $data['color'] ?? DocumentTagService::DEFAULT_TAG_COLOR,
                );

                return $name;
            });
    }
}
