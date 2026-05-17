<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $warning->subject }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .detail { margin-bottom: 10px; }
        .label { font-weight: bold; color: #666; }
        .value { color: #333; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{ __('employees::filament/resources/employee/relation-manager/warnings.mail.subject', ['subject' => $warning->subject]) }}</h2>
        </div>

        <div class="detail">
            <span class="label">{{ __('employees::filament/resources/employee/relation-manager/warnings.form.fields.employee') }}:</span>
            <span class="value">{{ $employee->name }}</span>
        </div>

        @if($warningType)
        <div class="detail">
            <span class="label">{{ __('employees::filament/resources/employee/relation-manager/warnings.form.fields.warning-type') }}:</span>
            <span class="value">{{ $warningType->name }}</span>
        </div>
        @endif

        <div class="detail">
            <span class="label">{{ __('employees::filament/resources/employee/relation-manager/warnings.form.fields.subject') }}:</span>
            <span class="value">{{ $warning->subject }}</span>
        </div>

        <div class="detail">
            <span class="label">{{ __('employees::filament/resources/employee/relation-manager/warnings.form.fields.issued-at') }}:</span>
            <span class="value">{{ $warning->issued_at->format('Y-m-d') }}</span>
        </div>

        @if($warning->effective_date)
        <div class="detail">
            <span class="label">{{ __('employees::filament/resources/employee/relation-manager/warnings.form.fields.effective-date') }}:</span>
            <span class="value">{{ $warning->effective_date->format('Y-m-d') }}</span>
        </div>
        @endif

        @if($warning->expiry_date)
        <div class="detail">
            <span class="label">{{ __('employees::filament/resources/employee/relation-manager/warnings.form.fields.expiry-date') }}:</span>
            <span class="value">{{ $warning->expiry_date->format('Y-m-d') }}</span>
        </div>
        @endif

        @if($warning->description)
        <div class="detail">
            <span class="label">{{ __('employees::filament/resources/employee/relation-manager/warnings.form.fields.description') }}:</span>
            <p class="value">{{ $warning->description }}</p>
        </div>
        @endif
    </div>
</body>
</html>
