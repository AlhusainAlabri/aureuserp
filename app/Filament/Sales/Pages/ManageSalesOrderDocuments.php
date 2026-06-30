<?php

namespace App\Filament\Sales\Pages;

use App\Models\Sales\SalesOrderAttachment;
use App\Support\Media\PrivateMediaUrl;
use App\Support\Media\PrivateUploadMetadata;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Number;
use Livewire\Livewire;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource;
use Webkul\Support\Traits\HasRecordNavigationTabs;

class ManageSalesOrderDocuments extends ManageRelatedRecords
{
    use HasRecordNavigationTabs;

    protected static string $resource = QuotationResource::class;

    protected static string $relationship = 'documents';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-paper-clip';

    public static function getNavigationLabel(): string
    {
        return __('sales-extensions::documents.navigation.title');
    }

    public function getTitle(): string|Htmlable
    {
        return __('sales-extensions::documents.page.title', [
            'reference' => $this->getRecord()->name,
        ]);
    }

    public function getBreadcrumb(): string
    {
        return __('sales-extensions::documents.navigation.title');
    }

    public static function getNavigationBadge($parameters = []): ?string
    {
        $count = Livewire::current()->getRecord()->documents()->count();

        return $count > 0 ? (string) $count : null;
    }

    public function form(Schema $schema): Schema
    {
        $maxMegabytes = (int) config('document-archive.max_file_size_mb', 50);

        return $schema
            ->components([
                TextInput::make('title')
                    ->label(__('sales-extensions::documents.fields.title'))
                    ->maxLength(255)
                    ->columnSpanFull(),
                FileUpload::make('file_path')
                    ->label(__('sales-extensions::documents.fields.file'))
                    ->disk('private')
                    ->directory(fn (): string => 'sales/'.now()->year.'/'.($this->getRecord()->name ?? 'draft'))
                    ->acceptedFileTypes(self::acceptedMimeTypes())
                    ->maxSize($maxMegabytes * 1024)
                    ->helperText(__('sales-extensions::documents.form.upload_hint', ['max' => $maxMegabytes]))
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('notes')
                    ->label(__('sales-extensions::documents.fields.notes'))
                    ->rows(3)
                    ->columnSpanFull(),
                Hidden::make('file_name')->default('file'),
                Hidden::make('file_size')->default(0),
                Hidden::make('mime_type')->default('application/octet-stream'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('sales-extensions::documents.fields.title'))
                    ->placeholder(fn (SalesOrderAttachment $record): string => $record->file_name)
                    ->searchable(),
                TextColumn::make('file_name')
                    ->label(__('sales-extensions::documents.fields.file_name'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('file_size')
                    ->label(__('sales-extensions::documents.fields.file_size'))
                    ->formatStateUsing(fn (?int $state): string => $state ? Number::fileSize($state) : '-'),
                TextColumn::make('mime_type')
                    ->label(__('sales-extensions::documents.fields.mime_type'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('creator.name')
                    ->label(__('sales-extensions::documents.fields.creator')),
                TextColumn::make('created_at')
                    ->label(__('sales-extensions::documents.fields.uploaded_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label(__('sales-extensions::documents.actions.upload'))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->modalHeading(__('sales-extensions::documents.actions.upload'))
                    ->modalDescription(__('sales-extensions::documents.form.upload_description'))
                    ->visible(fn (): bool => $this->canManageDocuments())
                    ->authorize(fn (): bool => $this->canManageDocuments())
                    ->mutateFormDataUsing(fn (array $data): array => self::enrichFileMetadata($data)),
            ])
            ->recordActions([
                Action::make('view')
                    ->label(__('sales-extensions::documents.actions.view'))
                    ->icon('heroicon-o-eye')
                    ->visible(fn (SalesOrderAttachment $record): bool => $record->isPreviewable())
                    ->url(fn (SalesOrderAttachment $record): ?string => PrivateMediaUrl::inlineUrl($record->file_path))
                    ->openUrlInNewTab(),
                Action::make('download')
                    ->label(__('sales-extensions::documents.actions.download'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (SalesOrderAttachment $record): ?string => PrivateMediaUrl::downloadUrl($record->file_path))
                    ->openUrlInNewTab(),
                DeleteAction::make()
                    ->visible(fn (): bool => $this->canManageDocuments())
                    ->authorize(fn (): bool => $this->canManageDocuments()),
            ])
            ->emptyStateHeading(__('sales-extensions::documents.empty.heading'))
            ->emptyStateDescription(__('sales-extensions::documents.empty.description'))
            ->emptyStateIcon('heroicon-o-document-plus');
    }

    protected function canManageDocuments(): bool
    {
        $user = auth()->user();
        $record = $this->getRecord();

        if (! $user || ! $record) {
            return false;
        }

        return $user->can('update', $record);
    }

    /**
     * @return array<int, string>
     */
    protected static function acceptedMimeTypes(): array
    {
        return [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'text/plain',
            'text/csv',
            'application/zip',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function enrichFileMetadata(array $data): array
    {
        return PrivateUploadMetadata::enrich($data);
    }
}
