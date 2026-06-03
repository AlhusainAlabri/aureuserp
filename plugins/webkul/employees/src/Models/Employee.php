<?php

namespace Webkul\Employee\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;
use Webkul\Employee\Database\Factories\EmployeeFactory;
use Webkul\Employee\Enums\DistanceUnit;
use Webkul\Field\Traits\HasCustomFields;
use Webkul\Meetings\Models\MeetingAttendee;
use Webkul\Partner\Models\BankAccount;
use Webkul\Partner\Models\Partner;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Country;
use Webkul\Support\Models\State;

class Employee extends Model
{
    use HasChatter, HasCustomFields, HasFactory, HasLogActivity, SoftDeletes;

    public const ACTIVITY_PLAN_PLUGIN = 'employees';

    protected $table = 'employees_employees';

    protected $fillable = [
        'company_id',
        'user_id',
        'creator_id',
        'calendar_id',
        'department_id',
        'job_id',
        'employment_type_id',
        'attendance_manager_id',
        'partner_id',
        'work_location_id',
        'parent_id',
        'coach_id',
        'country_id',
        'state_id',
        'country_of_birth',
        'bank_account_id',
        'departure_reason_id',
        'name',
        'job_title',
        'work_phone',
        'mobile_phone',
        'color',
        'work_email',
        'children',
        'distance_home_work',
        'km_home_work',
        'distance_home_work_unit',
        'private_phone',
        'private_email',
        'private_street1',
        'private_street2',
        'private_city',
        'private_zip',
        'private_state_id',
        'private_country_id',
        'private_car_plate',
        'lang',
        'gender',
        'birthday',
        'marital',
        'spouse_complete_name',
        'spouse_birthdate',
        'place_of_birth',
        'ssnid',
        'sinid',
        'identification_id',
        'passport_id',
        'permit_no',
        'visa_no',
        'certificate',
        'study_field',
        'study_school',
        'emergency_contact',
        'emergency_phone',
        'employee_type',
        'barcode',
        'pin',
        'address_id',
        'time_zone',
        'work_permit',
        'leave_manager_id',
        'visa_expire',
        'work_permit_expiration_date',
        'departure_date',
        'departure_description',
        'additional_note',
        'notes',
        'membership_type',
        'civil_id',
        'civil_id_expiry',
        'is_active',
        'is_flexible',
        'is_fully_flexible',
        'work_permit_scheduled_activity',
    ];

    protected $casts = [
        'is_active'                      => 'boolean',
        'is_flexible'                    => 'boolean',
        'is_fully_flexible'              => 'boolean',
        'work_permit_scheduled_activity' => 'boolean',
        'civil_id_expiry'                => 'date',
        'membership_type'                => 'string',
    ];

    public function getModelTitle(): string
    {
        return __('employees::models/employee.title');
    }

    public function privateState(): BelongsTo
    {
        return $this->belongsTo(State::class, 'private_state_id');
    }

    public function privateCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'private_country_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function meetingAttendances(): HasMany
    {
        return $this->hasMany(MeetingAttendee::class, 'user_id', 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(Calendar::class, 'calendar_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(EmployeeJobPosition::class, 'job_id');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    public function workLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class, 'work_location_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(self::class, 'coach_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function countryOfBirth(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_of_birth');
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function departureReason(): BelongsTo
    {
        return $this->belongsTo(DepartureReason::class, 'departure_reason_id');
    }

    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentType::class, 'employment_type_id');
    }

    public function categories()
    {
        return $this->belongsToMany(EmployeeCategory::class, 'employees_employee_categories', 'employee_id', 'category_id');
    }

    public function skills(): HasMany
    {
        return $this->hasMany(EmployeeSkill::class, 'employee_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class, 'employee_id');
    }

    public function warnings(): HasMany
    {
        return $this->hasMany(EmployeeWarning::class, 'employee_id');
    }

    public function resumes()
    {
        return $this->hasMany(EmployeeResume::class, 'employee_id');
    }

    protected static function newFactory(): EmployeeFactory
    {
        return EmployeeFactory::new();
    }

    public function leaveManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leave_manager_id');
    }

    public function attendanceManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attendance_manager_id');
    }

    public function companyAddress()
    {
        return $this->belongsTo(Partner::class, 'address_id');
    }

    /**
     * @param  array<int, string>  $attributes
     */
    public function hasAnyFilledAttributes(array $attributes): bool
    {
        foreach ($attributes as $attribute) {
            if (filled($this->getAttribute($attribute))) {
                return true;
            }
        }

        return false;
    }

    public function hasFormattedHomeDistance(): bool
    {
        return filled($this->distance_home_work) && (float) $this->distance_home_work > 0;
    }

    public function getFormattedHomeDistanceAttribute(): ?string
    {
        if (! $this->hasFormattedHomeDistance()) {
            return null;
        }

        $suffix = $this->distance_home_work_unit === DistanceUnit::METER->value ? 'm' : 'km';

        return trim(number_format((float) $this->distance_home_work, 0).' '.$suffix);
    }

    public function getCivilIdExpiryColor(): ?string
    {
        if (! $this->civil_id_expiry) {
            return null;
        }

        $days = (int) now()->diffInDays($this->civil_id_expiry, false);

        if ($days < 0) {
            return 'danger';
        }

        if ($days <= 30) {
            return 'warning';
        }

        return 'success';
    }

    /**
     * @return array<int, array{label: string, color: string}>
     */
    public function getListComplianceBadges(): array
    {
        $badges = [];

        if (! $this->is_active) {
            $badges[] = [
                'label' => __('employees::filament/resources/employee.table.compliance-badges.inactive'),
                'color' => 'gray',
            ];
        }

        $expiredDocumentsCount = (int) ($this->expired_documents_count ?? 0);

        if ($expiredDocumentsCount > 0) {
            $badges[] = [
                'label' => __('employees::filament/resources/employee.table.compliance-badges.expired-docs', [
                    'count' => $expiredDocumentsCount,
                ]),
                'color' => 'danger',
            ];
        }

        $expiringDocumentsCount = (int) ($this->expiring_documents_count ?? 0);

        if ($expiringDocumentsCount > 0) {
            $badges[] = [
                'label' => __('employees::filament/resources/employee.table.compliance-badges.expiring-docs', [
                    'count' => $expiringDocumentsCount,
                ]),
                'color' => 'warning',
            ];
        }

        $activeWarningsCount = (int) ($this->active_warnings_count ?? 0);

        if ($activeWarningsCount > 0) {
            $badges[] = [
                'label' => __('employees::filament/resources/employee.table.compliance-badges.active-warnings', [
                    'count' => $activeWarningsCount,
                ]),
                'color' => 'danger',
            ];
        }

        if ($this->civil_id_expiry && in_array($this->getCivilIdExpiryColor(), ['danger', 'warning'], true)) {
            $badges[] = [
                'label' => __('employees::filament/resources/employee.table.compliance-badges.civil-id'),
                'color' => $this->getCivilIdExpiryColor(),
            ];
        }

        return $badges;
    }

    public function scopeWithComplianceIssues(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->where('is_active', false)
                ->orWhere(function (Builder $query): void {
                    $query->whereNotNull('civil_id_expiry')
                        ->whereDate('civil_id_expiry', '<=', now()->addDays(30)->endOfDay());
                })
                ->orWhere(function (Builder $query): void {
                    $query->whereNotNull('visa_expire')
                        ->whereDate('visa_expire', '<=', now()->addDays(30)->endOfDay());
                })
                ->orWhere(function (Builder $query): void {
                    $query->whereNotNull('work_permit_expiration_date')
                        ->whereDate('work_permit_expiration_date', '<=', now()->addDays(30)->endOfDay());
                })
                ->orWhereHas('documents', fn (Builder $query): Builder => $query->expiringWithin())
                ->orWhereHas('warnings', fn (Builder $query): Builder => $query->where('is_acknowledged', false));
        });
    }

    public function scopeIncompleteProfile(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->whereNull('department_id')
                ->orWhereNull('parent_id')
                ->orWhereNull('work_email')
                ->orWhereNull('employment_type_id')
                ->orWhereNull('job_title');
        });
    }

    public function syncLegacyDistanceFields(): void
    {
        if (! filled($this->distance_home_work)) {
            $this->km_home_work = 0;

            return;
        }

        $this->km_home_work = match ($this->distance_home_work_unit) {
            DistanceUnit::METER->value => (int) round(((float) $this->distance_home_work) / 1000),
            default                    => (int) round((float) $this->distance_home_work),
        };
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function (self $employee): void {
            if ($employee->isDirty(['distance_home_work', 'distance_home_work_unit'])) {
                $employee->syncLegacyDistanceFields();
            }
        });

        static::saved(function (self $employee) {
            $employee->creator_id ??= Auth::id();

            if (! $employee->partner_id) {
                $employee->handlePartnerCreation($employee);
            } else {
                $employee->handlePartnerUpdation($employee);
            }
        });
    }

    private function handlePartnerCreation(self $employee): void
    {
        $partner = $employee->partner()->create([
            'account_type' => 'individual',
            'sub_type'     => 'employee',
            'creator_id'   => $employee->creator_id ?? Auth::id(),
            'name'         => $employee?->name,
            'email'        => $employee?->work_email ?? $employee?->private_email,
            'job_title'    => $employee?->job_title,
            'phone'        => $employee?->work_phone,
            'mobile'       => $employee?->mobile_phone,
            'color'        => $employee?->color,
            'parent_id'    => $employee?->parent_id,
            'company_id'   => $employee?->company_id,
            'user_id'      => $employee?->user_id,
        ]);

        $employee->partner_id = $partner->id;
        $employee->save();
    }

    private function handlePartnerUpdation(self $employee): void
    {
        $partner = Partner::updateOrCreate(
            ['id' => $employee->partner_id],
            [
                'account_type' => 'individual',
                'sub_type'     => 'employee',
                'creator_id'   => $employee->creator_id ?? Auth::id(),
                'name'         => $employee?->name,
                'email'        => $employee?->work_email ?? $employee?->private_email,
                'job_title'    => $employee?->job_title,
                'phone'        => $employee?->work_phone,
                'mobile'       => $employee?->mobile_phone,
                'color'        => $employee?->color,
                'parent_id'    => $employee?->parent_id,
                'company_id'   => $employee?->company_id,
                'user_id'      => $employee?->user_id,
            ]
        );

        if ($employee->partner_id !== $partner->id) {
            $employee->partner_id = $partner->id;
            $employee->save();
        }
    }
}
