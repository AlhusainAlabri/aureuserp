<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('gratuity.minimum_service_years', 1.0);
        $this->migrator->add('gratuity.eligible_category', 'expat');
        $this->migrator->add('gratuity.leave_payout_divisor', 30);
    }

    public function down(): void
    {
        $this->migrator->delete('gratuity.minimum_service_years');
        $this->migrator->delete('gratuity.eligible_category');
        $this->migrator->delete('gratuity.leave_payout_divisor');
    }
};
