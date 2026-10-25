<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Settings\BrandingSettings;
use App\Support\Branding;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Closure;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Webkul\Support\Filament\Clusters\Settings;

class ManageBranding extends SettingsPage
{
    use HasPageShield;

    protected static ?string $cluster = Settings::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static string $settings = BrandingSettings::class;

    protected static ?int $navigationSort = 4;

    /**
     * @var list<string>
     */
    protected array $replacedLogoPaths = [];

    public function getBreadcrumbs(): array
    {
        return [
            __('branding-settings.navigation.breadcrumb'),
        ];
    }

    public function getTitle(): string
    {
        return __('branding-settings.navigation.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('branding-settings.navigation.label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('branding-settings.sections.identity'))
                    ->schema([
                        TextInput::make('display_name')
                            ->label(__('branding-settings.form.display_name.label'))
                            ->helperText(__('branding-settings.form.display_name.helper'))
                            ->maxLength(120)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make(__('branding-settings.sections.colors'))
                    ->schema([
                        ColorPicker::make('primary_color')
                            ->label(__('branding-settings.form.primary_color.label'))
                            ->helperText(__('branding-settings.form.primary_color.helper'))
                            ->rules([
                                fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                    $hex = Branding::normalizeHex($value);

                                    if ($hex === null) {
                                        if (filled($value)) {
                                            $fail(__('branding-settings.form.primary_color.invalid'));
                                        }

                                        return;
                                    }

                                    if (Branding::contrastWithWhite($hex) < Branding::MIN_CONTRAST_WITH_WHITE) {
                                        $fail(__('branding-settings.form.primary_color.too_light'));
                                    }
                                },
                            ]),
                    ])
                    ->columnSpanFull(),
                Section::make(__('branding-settings.sections.logos'))
                    ->schema([
                        Grid::make(2)->schema([
                            $this->logoUpload('logo_full'),
                            $this->logoUpload('logo_full_dark'),
                            $this->logoUpload('logo_compact'),
                            $this->logoUpload('logo_compact_dark'),
                            $this->logoUpload('favicon', favicon: true),
                        ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $slots = self::logoSlots();
        $settings = app(BrandingSettings::class);

        foreach ($slots as $slot) {
            if (! array_key_exists($slot, $data)) {
                continue;
            }

            $data[$slot] = self::normalizeStoredPath($data[$slot]);
        }

        if (array_key_exists('primary_color', $data)) {
            $data['primary_color'] = Branding::normalizeHex($data['primary_color']);
        }

        if (array_key_exists('display_name', $data)) {
            $name = is_string($data['display_name']) ? trim($data['display_name']) : '';
            $data['display_name'] = $name === '' ? null : $name;
        }

        $newPaths = [];

        foreach ($slots as $slot) {
            $newPaths[$slot] = array_key_exists($slot, $data) ? $data[$slot] : $settings->{$slot};
        }

        $this->replacedLogoPaths = [];

        foreach ($slots as $slot) {
            $previous = $settings->{$slot};

            if (! is_string($previous) || blank($previous) || $previous === $newPaths[$slot]) {
                continue;
            }

            if (in_array($previous, $newPaths, true)) {
                continue;
            }

            $this->replacedLogoPaths[] = $previous;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        foreach ($this->replacedLogoPaths as $path) {
            self::deleteStoredLogo($path);
        }

        $this->replacedLogoPaths = [];

        Branding::forgetCache();

        config(['app.name' => brand_name()]);
    }

    protected function logoUpload(string $name, bool $favicon = false): FileUpload
    {
        $acceptedFileTypes = $favicon
            ? ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml', 'image/gif', 'image/x-icon', 'image/vnd.microsoft.icon']
            : ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml', 'image/gif'];

        return FileUpload::make($name)
            ->label(__('branding-settings.form.'.$name.'.label'))
            ->helperText(__('branding-settings.form.'.$name.'.helper'))
            ->disk('public')
            ->directory('branding')
            ->visibility('public')
            ->acceptedFileTypes($acceptedFileTypes)
            ->maxSize(2048)
            ->imagePreviewHeight('4rem')
            ->columnSpan(1);
    }

    /**
     * @return list<string>
     */
    protected static function logoSlots(): array
    {
        return [
            'logo_full',
            'logo_full_dark',
            'logo_compact',
            'logo_compact_dark',
            'favicon',
        ];
    }

    protected static function normalizeStoredPath(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = collect($value)->first(fn (mixed $item): bool => is_string($item) && filled($item));
        }

        if (! is_string($value) || blank($value)) {
            return null;
        }

        $value = str_replace('\\', '/', $value);

        if (str_contains($value, '..') || ! str_starts_with($value, 'branding/')) {
            return null;
        }

        return $value;
    }

    protected static function deleteStoredLogo(string $path): void
    {
        $normalized = self::normalizeStoredPath($path);

        if ($normalized === null) {
            return;
        }

        Storage::disk('public')->delete($normalized);
    }
}
