<?php

namespace App\Filament\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema as DbSchema;
use Illuminate\Support\Facades\Storage;
use Webkul\DocumentArchive\Filament\Concerns\ManagesDocumentRecords;
use Webkul\DocumentArchive\Filament\Resources\DocFileResource;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Models\DocFolder;

class ProjectDocumentsRelationManager extends RelationManager
{
    use ManagesDocumentRecords;

    protected static string $relationship = 'docFiles';

    public static function getTitle($ownerRecord = null, ?string $pageClass = null): string
    {
        return __('projects-extensions::relations.documents');
    }

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        return DbSchema::hasTable('doc_files')
            && DbSchema::hasColumn('doc_files', 'project_id');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return DocFileResource::form($schema);
    }

    protected function finalizeDocumentCreate(DocFile $record): void
    {
        $rawState = $this->getMountedActionSchema()?->getRawState() ?? [];

        $this->handleDocumentPassword($record, $rawState);
        $this->handleDocumentUpload($record, $rawState, true);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')
                    ->label(__('projects-extensions::columns.reference'))
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('projects-extensions::columns.filename'))
                    ->wrap()
                    ->searchable(),
                TextColumn::make('creator.name')
                    ->label(__('projects-extensions::columns.created_by'))
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label(__('projects-extensions::columns.date'))
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('projects-extensions::actions.add_document'))
                    ->icon('heroicon-o-plus-circle')
                    ->fillForm(fn (): array => [
                        'project_id' => $this->getOwnerRecord()->id,
                        'folder_id'  => DocFolder::query()->value('id'),
                        'company_id' => $this->getOwnerRecord()->company_id ?? $this->defaultCompanyId(),
                    ])
                    ->mutateDataUsing(function (array $data): array {
                        $data['project_id'] = $this->getOwnerRecord()->id;

                        if (empty($data['company_id'])) {
                            $data['company_id'] = $this->getOwnerRecord()->company_id ?? $this->defaultCompanyId();
                        }

                        return $this->mutateDocumentFormData($data);
                    })
                    ->after(fn (DocFile $record): mixed => $this->finalizeDocumentCreate($record))
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('projects-extensions::notifications.document_created.title'))
                            ->body(__('projects-extensions::notifications.document_created.body')),
                    ),
            ])
            ->emptyStateHeading(__('projects-extensions::empty.documents.heading'))
            ->emptyStateDescription(__('projects-extensions::empty.documents.description'))
            ->recordActions([
                Action::make('download')
                    ->label(__('document-archive::document-archive.actions.download'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn (DocFile $record): bool => filled($record->file_path))
                    ->url(fn (DocFile $record): string => Storage::disk('private')->temporaryUrl(
                        $record->file_path,
                        now()->addMinutes(60),
                    ))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
