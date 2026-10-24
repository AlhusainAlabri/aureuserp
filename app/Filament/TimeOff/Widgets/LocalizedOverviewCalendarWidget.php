<?php

namespace App\Filament\TimeOff\Widgets;

use App\Filament\Concerns\InheritsWidgetShieldPermission;
use App\Filament\TimeOff\Concerns\LocalizesLeaveCalendarActions;
use App\Filament\TimeOff\Concerns\RepairsMountedActionFormState;
use App\Filament\TimeOff\Concerns\StabilizesLeaveCalendarForm;
use Webkul\TimeOff\Filament\Widgets\OverviewCalendarWidget as BaseOverviewCalendarWidget;

class LocalizedOverviewCalendarWidget extends BaseOverviewCalendarWidget
{
    use InheritsWidgetShieldPermission;
    use LocalizesLeaveCalendarActions;
    use RepairsMountedActionFormState;
    use StabilizesLeaveCalendarForm {
        getFormSchema as protected stabilizedLeaveFormSchema;
    }

    /**
     * The overview calendar is used to record leave for any employee.
     * The personal calendar keeps the employee field hidden.
     */
    public function getFormSchema($isVisible = null): array
    {
        return $this->stabilizedLeaveFormSchema($isVisible ?? true);
    }

    public static function canView(): bool
    {
        return BaseOverviewCalendarWidget::canView();
    }

    /**
     * Ensure calendar header/modal actions are cached before Filament resolves
     * mountedActions, otherwise the create modal is cleared on live updates.
     */
    public function bootedInteractsWithActions(): void
    {
        $this->bootMountedCalendarActions();
    }

    protected function headerActions(): array
    {
        return $this->withLocalizedLeaveModelLabel(parent::headerActions());
    }

    public function modalActions(): array
    {
        return $this->withLocalizedLeaveModelLabel(parent::modalActions());
    }
}
