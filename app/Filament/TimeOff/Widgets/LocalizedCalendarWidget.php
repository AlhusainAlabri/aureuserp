<?php

namespace App\Filament\TimeOff\Widgets;

use App\Filament\TimeOff\Concerns\LocalizesLeaveCalendarActions;
use App\Filament\TimeOff\Concerns\RepairsMountedActionFormState;
use App\Filament\TimeOff\Concerns\StabilizesLeaveCalendarForm;
use Webkul\TimeOff\Filament\Widgets\CalendarWidget as BaseCalendarWidget;

class LocalizedCalendarWidget extends BaseCalendarWidget
{
    use LocalizesLeaveCalendarActions;
    use RepairsMountedActionFormState;
    use StabilizesLeaveCalendarForm;

    public static function canView(): bool
    {
        return BaseCalendarWidget::canView();
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
