<?php

namespace Webkul\Attendance\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Webkul\Attendance\Exceptions\AttendanceException;
use Webkul\Attendance\Services\AttendanceImporter;
use Webkul\Attendance\Services\ImportPreview;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;

class ImportAttendance extends Page implements HasForms
{
    use HasPageShield, InteractsWithForms;

    /**
     * Where uploaded files wait between the steps; the disk is not served over HTTP.
     */
    protected const DISK = 'local';

    protected const DIRECTORY = 'attendance-imports';

    protected const ALLOWED_EXTENSIONS = ['csv', 'xlsx'];

    protected const PREVIEW_ROWS = 50;

    protected const LISTED_IDS = 200;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'attendances/import';

    protected string $view = 'attendances::filament.pages.import-attendance';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    /**
     * 1 upload, 2 column mapping, 3 preview and confirmation.
     */
    #[Locked]
    public int $step = 1;

    /**
     * Path of the uploaded file on the private disk; it never leaves the server and cannot be set by the browser.
     */
    #[Locked]
    public ?string $storedPath = null;

    #[Locked]
    public string $fileName = '';

    /**
     * @var list<string>
     */
    #[Locked]
    public array $headers = [];

    /**
     * @var array{device_user_id: int, timestamp: int, direction: ?int}
     */
    #[Locked]
    public array $mapping = ['device_user_id' => 0, 'timestamp' => 1, 'direction' => null];

    #[Locked]
    public bool $hasHeader = true;

    /**
     * What the confirmation would do, as plain numbers and lists for the view.
     *
     * @var array{total: int, new_punches: int, duplicates: int, invalid: int, unmatched: list<string>, unmatched_total: int, ambiguous: list<string>, ambiguous_total: int, rows: list<array{employee: string, device_id: string, time: string, direction: string}>}|array{}
     */
    #[Locked]
    public array $summary = [];

    /**
     * The file stored by this request's upload and its client file name. Set only while saving a real
     * upload: the form state is client-controlled and is never used to locate the file.
     */
    protected ?string $uploadedPath = null;

    protected string $uploadedName = '';

    public static function getNavigationLabel(): string
    {
        return __('attendances::attendances.import.navigation');
    }

    public static function getNavigationGroup(): string
    {
        return __('attendances::attendances.navigation.group');
    }

    public function getTitle(): string
    {
        return __('attendances::attendances.import.title');
    }

    public function mount(): void
    {
        $this->form->fill(['has_header' => true]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('attendances::attendances.import.steps.upload'))
                    ->schema([
                        FileUpload::make('file')
                            ->label(__('attendances::attendances.import.fields.file'))
                            ->helperText(__('attendances::attendances.import.fields.file_help'))
                            ->disk(self::DISK)
                            ->visibility('private')
                            ->acceptedFileTypes([
                                'text/csv',
                                'text/plain',
                                'text/x-csv',
                                'text/comma-separated-values',
                                'application/csv',
                                'application/x-csv',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->rules(['extensions:'.implode(',', self::ALLOWED_EXTENSIONS)])
                            ->maxSize(5120)
                            ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file): ?string => $this->storeUpload($file))
                            ->required(),
                    ])
                    ->visible(fn (): bool => $this->step === 1),
                Section::make(__('attendances::attendances.import.steps.mapping'))
                    ->description(__('attendances::attendances.import.fields.mapping_help'))
                    ->schema([
                        Toggle::make('has_header')
                            ->label(__('attendances::attendances.import.fields.has_header'))
                            ->default(true)
                            ->live(),
                        Select::make('device_user_id')
                            ->label(__('attendances::attendances.import.fields.device_user_id'))
                            ->options(fn (Get $get): array => $this->columnOptions((bool) $get('has_header')))
                            ->required(),
                        Select::make('timestamp')
                            ->label(__('attendances::attendances.import.fields.timestamp'))
                            ->options(fn (Get $get): array => $this->columnOptions((bool) $get('has_header')))
                            ->required(),
                        Select::make('direction')
                            ->label(__('attendances::attendances.import.fields.direction'))
                            ->helperText(__('attendances::attendances.import.fields.direction_help'))
                            ->options(fn (Get $get): array => $this->columnOptions((bool) $get('has_header'))),
                    ])
                    ->columns(2)
                    ->visible(fn (): bool => $this->step === 2),
            ])
            ->statePath('data');
    }

    /**
     * Step 1 to 2: keeps the uploaded file and offers its columns.
     */
    public function uploadFile(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->uploadedPath = null;
        $this->uploadedName = '';

        // The upload state is an array keyed by upload id; anything else is not a browser upload.
        if (isset($this->data['file']) && ! is_array($this->data['file'])) {
            $this->data['file'] = null;
        }

        $this->form->getState();

        // Only a file this request received and stored itself counts; a path sent in the state is ignored.
        $path = $this->ownUploadPath($this->uploadedPath);

        if ($path === null) {
            $this->form->fill(['has_header' => true]);

            Notification::make()->danger()->title(__('attendances::attendances.import.errors.invalid_upload'))->send();

            return;
        }

        try {
            $file = app(AttendanceImporter::class)->read(Storage::disk(self::DISK)->path($path), strtolower(pathinfo($path, PATHINFO_EXTENSION)));
        } catch (AttendanceException $exception) {
            Storage::disk(self::DISK)->delete($path);
            $this->form->fill(['has_header' => true]);

            Notification::make()->danger()->title($exception->getMessage())->send();

            return;
        }

        // A user has one pending upload: starting a new one drops the previous (and any abandoned) file.
        foreach (Storage::disk(self::DISK)->files($this->uploadDirectory()) as $existing) {
            if ($existing !== $path) {
                Storage::disk(self::DISK)->delete($existing);
            }
        }

        $this->storedPath = $path;
        $this->fileName = Str::limit($this->uploadedName, 190, '');
        $this->headers = $file['headers'];
        $this->step = 2;

        $this->form->fill([
            'has_header'     => true,
            'device_user_id' => 0,
            'timestamp'      => count($file['headers']) > 1 ? 1 : null,
            'direction'      => null,
        ]);
    }

    /**
     * Step 2 to 3: computes what the import would do, writing nothing.
     */
    public function showPreview(): void
    {
        abort_unless(static::canAccess() && $this->storedPath !== null, 403);

        $state = $this->form->getState();

        $mapping = [
            'device_user_id' => (int) $state['device_user_id'],
            'timestamp'      => (int) $state['timestamp'],
            'direction'      => filled($state['direction'] ?? null) ? (int) $state['direction'] : null,
        ];

        if ($mapping['timestamp'] === $mapping['device_user_id']) {
            throw ValidationException::withMessages(['data.timestamp' => __('attendances::attendances.import.errors.same_column')]);
        }

        if ($mapping['direction'] !== null && in_array($mapping['direction'], [$mapping['device_user_id'], $mapping['timestamp']], true)) {
            throw ValidationException::withMessages(['data.direction' => __('attendances::attendances.import.errors.same_column')]);
        }

        $hasHeader = (bool) ($state['has_header'] ?? true);

        try {
            $preview = $this->buildPreview($mapping, $hasHeader);
        } catch (AttendanceException $exception) {
            Notification::make()->danger()->title($exception->getMessage())->send();

            return;
        }

        $this->mapping = $mapping;
        $this->hasHeader = $hasHeader;
        $this->summary = $this->summarise($preview);
        $this->step = 3;
    }

    /**
     * Step 3: writes the batch and its punches.
     */
    public function confirm(): void
    {
        abort_unless(static::canAccess(), 403);

        if ($this->step !== 3 || $this->storedPath === null) {
            return;
        }

        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        try {
            $preview = $this->buildPreview($this->mapping, $this->hasHeader);
            $batch = app(AttendanceImporter::class)->commit($preview, $user, $this->fileName);
        } catch (AttendanceException $exception) {
            Notification::make()->danger()->title($exception->getMessage())->send();

            return;
        }

        $this->startOver();

        Notification::make()
            ->success()
            ->title(__('attendances::attendances.import.imported'))
            ->body(__('attendances::attendances.import.imported_body', [
                'created'    => $batch->matched_rows,
                'duplicates' => $batch->duplicate_rows,
                'invalid'    => $batch->invalid_rows,
                'unmatched'  => count($batch->unmatched_device_ids ?? []),
            ]))
            ->send();
    }

    public function back(): void
    {
        if ($this->step === 3) {
            $this->step = 2;

            return;
        }

        $this->startOver();
    }

    public function startOver(): void
    {
        $this->discardFile();

        $this->storedPath = null;
        $this->fileName = '';
        $this->headers = [];
        $this->summary = [];
        $this->step = 1;
        $this->form->fill(['has_header' => true]);
    }

    /**
     * @return array<int, string>
     */
    protected function columnOptions(bool $hasHeader): array
    {
        $options = [];

        foreach ($this->headers as $index => $header) {
            $letter = $this->columnLetter($index);

            $options[$index] = $hasHeader && $header !== ''
                ? "{$header} ({$letter})"
                : __('attendances::attendances.import.fields.column', ['column' => $letter]);
        }

        return $options;
    }

    protected function columnLetter(int $index): string
    {
        $letter = '';

        for ($number = $index; $number >= 0; $number = intdiv($number, 26) - 1) {
            $letter = chr(65 + $number % 26).$letter;
        }

        return $letter;
    }

    /**
     * @param  array{device_user_id: int, timestamp: int, direction: ?int}  $mapping
     *
     * @throws AttendanceException
     */
    protected function buildPreview(array $mapping, bool $hasHeader): ImportPreview
    {
        $importer = app(AttendanceImporter::class);
        $path = $this->ownUploadPath($this->storedPath);

        if ($path === null || ! Storage::disk(self::DISK)->exists($path)) {
            throw new AttendanceException(__('attendances::attendances.import.errors.file_missing'));
        }

        $file = $importer->read(Storage::disk(self::DISK)->path($path), strtolower(pathinfo($path, PATHINFO_EXTENSION)));

        return $importer->preview($file['rows'], $mapping, $hasHeader);
    }

    /**
     * @return array{total: int, new_punches: int, duplicates: int, invalid: int, unmatched: list<string>, unmatched_total: int, ambiguous: list<string>, ambiguous_total: int, rows: list<array{employee: string, device_id: string, time: string, direction: string}>}
     */
    protected function summarise(ImportPreview $preview): array
    {
        $shown = array_slice($preview->matched, 0, self::PREVIEW_ROWS);
        $employees = Employee::query()
            ->whereIn('id', array_unique(array_column($shown, 'employee_id')))
            ->get(['id', 'name', 'time_zone'])
            ->keyBy('id');

        return [
            'total'           => $preview->totalRows,
            'new_punches'     => count($preview->matched),
            'duplicates'      => $preview->duplicates,
            'invalid'         => $preview->invalidRows,
            'unmatched'       => array_slice($preview->unmatchedDeviceIds, 0, self::LISTED_IDS),
            'unmatched_total' => count($preview->unmatchedDeviceIds),
            'ambiguous'       => array_slice($preview->ambiguousDeviceIds, 0, self::LISTED_IDS),
            'ambiguous_total' => count($preview->ambiguousDeviceIds),
            'rows'            => array_map(function (array $row) use ($employees): array {
                $employee = $employees->get($row['employee_id']);

                return [
                    'employee'  => (string) $employee?->name,
                    'device_id' => $row['device_ref'],
                    'time'      => $row['at']->setTimezone($employee?->time_zone ?: config('app.timezone'))->translatedFormat('d M Y H:i'),
                    'direction' => $row['direction']?->getLabel() ?? '-',
                ];
            }, $shown),
        ];
    }

    protected function discardFile(): void
    {
        $path = $this->ownUploadPath($this->storedPath);

        if ($path !== null) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    /**
     * Saves a real browser upload in the signed-in user's own folder under a random name.
     */
    protected function storeUpload(TemporaryUploadedFile $file): ?string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (Auth::id() === null || ! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return null;
        }

        // One upload per request: should the state carry several files, only the last stays.
        if ($this->uploadedPath !== null) {
            Storage::disk(self::DISK)->delete($this->uploadedPath);
        }

        $path = $file->storeAs($this->uploadDirectory(), Str::random(40).'.'.$extension, self::DISK);

        $this->uploadedPath = $path ?: null;
        $this->uploadedName = $file->getClientOriginalName();

        return $this->uploadedPath;
    }

    protected function uploadDirectory(): string
    {
        return self::DIRECTORY.'/'.Auth::id();
    }

    /**
     * The path when it is a file directly inside the signed-in user's upload folder with an allowed
     * extension, otherwise null. Nothing is read or deleted unless this passes.
     */
    protected function ownUploadPath(?string $path): ?string
    {
        if (! is_string($path) || Auth::id() === null) {
            return null;
        }

        if (str_contains($path, '..') || str_contains($path, '\\') || str_contains($path, "\0")) {
            return null;
        }

        $prefix = $this->uploadDirectory().'/';

        if (! str_starts_with($path, $prefix)) {
            return null;
        }

        $name = substr($path, strlen($prefix));

        if ($name === '' || str_contains($name, '/')) {
            return null;
        }

        return in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), self::ALLOWED_EXTENSIONS, true) ? $path : null;
    }
}
