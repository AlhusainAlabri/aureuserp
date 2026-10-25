<?php

namespace Webkul\Attendance\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;
use Webkul\Attendance\Rules\IpAddressOrCidr;
use Webkul\Attendance\Settings\AttendanceSettings;

class ManageAttendanceSettings extends SettingsPage
{
    use HasPageShield;

    protected static string $settings = AttendanceSettings::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'attendances/settings';

    public static function getNavigationLabel(): string
    {
        return __('attendances::attendances.settings.navigation');
    }

    public static function getNavigationGroup(): string
    {
        return __('attendances::attendances.navigation.group');
    }

    public function getTitle(): string
    {
        return __('attendances::attendances.settings.title');
    }

    public function getBreadcrumbs(): array
    {
        return [__('attendances::attendances.settings.title')];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('attendances::attendances.settings.sections.rules'))
                ->schema([
                    TextInput::make('grace_minutes')
                        ->label(__('attendances::attendances.settings.fields.grace_minutes'))
                        ->helperText(__('attendances::attendances.settings.fields.grace_minutes_hint'))
                        ->numeric()
                        ->integer()
                        ->required()
                        ->minValue(0)
                        ->maxValue(120),
                    TextInput::make('minimum_overtime_minutes')
                        ->label(__('attendances::attendances.settings.fields.minimum_overtime_minutes'))
                        ->helperText(__('attendances::attendances.settings.fields.minimum_overtime_hint'))
                        ->numeric()
                        ->integer()
                        ->required()
                        ->minValue(0)
                        ->maxValue(480),
                    $this->multiplierField('overtime_multiplier_normal'),
                    $this->multiplierField('overtime_multiplier_rest'),
                    $this->multiplierField('overtime_multiplier_holiday'),
                    TextInput::make('workday_close_time')
                        ->label(__('attendances::attendances.settings.fields.workday_close_time'))
                        ->helperText(__('attendances::attendances.settings.fields.workday_close_time_hint'))
                        ->type('time')
                        ->required()
                        ->rule('date_format:H:i'),
                ])
                ->columns(2),

            Section::make(__('attendances::attendances.settings.sections.self_checkin'))
                ->schema([
                    Toggle::make('allow_self_checkin')
                        ->label(__('attendances::attendances.settings.fields.allow_self_checkin')),
                    TagsInput::make('allowed_ip_ranges')
                        ->label(__('attendances::attendances.settings.fields.allowed_ip_ranges'))
                        ->helperText(__('attendances::attendances.settings.fields.allowed_ip_ranges_hint'))
                        ->nestedRecursiveRules([new IpAddressOrCidr])
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make(__('attendances::attendances.settings.sections.geofence'))
                ->schema([
                    Toggle::make('geofence_enabled')
                        ->label(__('attendances::attendances.settings.fields.geofence_enabled'))
                        ->helperText(__('attendances::attendances.settings.fields.geofence_hint'))
                        ->live()
                        ->columnSpanFull(),
                    TextInput::make('geofence_latitude')
                        ->label(__('attendances::attendances.settings.fields.latitude'))
                        ->numeric()
                        ->required()
                        ->minValue(-90)
                        ->maxValue(90)
                        ->visible(fn (Get $get): bool => (bool) $get('geofence_enabled')),
                    TextInput::make('geofence_longitude')
                        ->label(__('attendances::attendances.settings.fields.longitude'))
                        ->numeric()
                        ->required()
                        ->minValue(-180)
                        ->maxValue(180)
                        ->visible(fn (Get $get): bool => (bool) $get('geofence_enabled')),
                    TextInput::make('geofence_radius')
                        ->label(__('attendances::attendances.settings.fields.radius_metres'))
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(50000)
                        ->visible(fn (Get $get): bool => (bool) $get('geofence_enabled')),
                ])
                ->columns(3),
        ]);
    }

    /**
     * The settings hold the geofence as one nullable array; the form edits it as a switch and three fields.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $geofence = $data['geofence'] ?? null;
        unset($data['geofence']);

        $data['geofence_enabled'] = ! empty($geofence);
        $data['geofence_latitude'] = $geofence['latitude'] ?? null;
        $data['geofence_longitude'] = $geofence['longitude'] ?? null;
        $data['geofence_radius'] = $geofence['radius_metres'] ?? null;

        return $data;
    }

    /**
     * Turns the switch and the three fields back into the nullable geofence array: all three keys when the
     * switch is on, null when it is off. The self check-in service reads every key, so a partial array must
     * never reach the settings.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $enabled = (bool) ($data['geofence_enabled'] ?? false);
        $latitude = $data['geofence_latitude'] ?? null;
        $longitude = $data['geofence_longitude'] ?? null;
        $radius = $data['geofence_radius'] ?? null;

        unset($data['geofence_enabled'], $data['geofence_latitude'], $data['geofence_longitude'], $data['geofence_radius']);

        if ($enabled && ($latitude === null || $longitude === null || $radius === null)) {
            throw ValidationException::withMessages([
                'data.geofence_latitude' => __('validation.required', ['attribute' => __('attendances::attendances.settings.fields.latitude')]),
            ]);
        }

        $data['geofence'] = $enabled
            ? [
                'latitude'      => (float) $latitude,
                'longitude'     => (float) $longitude,
                'radius_metres' => (float) $radius,
            ]
            : null;

        $data['grace_minutes'] = (int) $data['grace_minutes'];
        $data['minimum_overtime_minutes'] = (int) $data['minimum_overtime_minutes'];

        foreach (['overtime_multiplier_normal', 'overtime_multiplier_rest', 'overtime_multiplier_holiday'] as $multiplier) {
            $data[$multiplier] = round((float) $data[$multiplier], 2);
        }

        $data['allowed_ip_ranges'] = collect($data['allowed_ip_ranges'] ?? [])
            ->map(fn (mixed $entry): string => trim((string) $entry))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $data;
    }

    protected function multiplierField(string $name): TextInput
    {
        return TextInput::make($name)
            ->label(__('attendances::attendances.settings.fields.'.$name))
            ->helperText(__('attendances::attendances.settings.fields.multiplier_hint'))
            ->numeric()
            ->required()
            ->minValue(1)
            ->maxValue(5)
            ->step(0.01)
            ->rule('decimal:0,2');
    }
}
