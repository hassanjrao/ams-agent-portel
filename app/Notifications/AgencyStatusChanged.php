<?php

namespace App\Notifications;

use App\Enums\AgencyStatus;
use App\Models\Agency;

// To the agency: AMS approved or suspended it.
class AgencyStatusChanged extends PortalMessage
{
    public function __construct(public Agency $agency) {}

    protected function subject(): string
    {
        return $this->approved()
            ? 'Your agency is approved on the AMS portal'
            : 'Your AMS portal account is suspended';
    }

    protected function lines(): array
    {
        return $this->approved()
            ? ["{$this->agency->name} is approved. You can now hold seats on any published group and send payments through the portal."]
            : ["{$this->agency->name} can no longer make new bookings. Your existing bookings and statement are unchanged.", 'Please contact AMS Global Travel & Tours for details.'];
    }

    protected function action(): array
    {
        return $this->approved()
            ? ['Find groups', route('groups.index')]
            : ['Open the portal', route('dashboard')];
    }

    private function approved(): bool
    {
        return $this->agency->status === AgencyStatus::Active;
    }
}
