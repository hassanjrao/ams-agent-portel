<?php

namespace App\Notifications;

use App\Models\Agency;

// To AMS staff: an agency signed up and is waiting for approval.
class NewAgencyRegistered extends PortalMessage
{
    public function __construct(public Agency $agency) {}

    protected function subject(): string
    {
        return "New agency sign-up: {$this->agency->name}";
    }

    protected function lines(): array
    {
        return [
            "{$this->agency->name} ({$this->agency->city}) has signed up for the agent portal.",
            "Contact: {$this->agency->contact_person}, {$this->agency->phone}, {$this->agency->email}.",
            'They can browse groups but cannot book until you approve the agency.',
        ];
    }

    protected function action(): array
    {
        return ['Review agency', route('admin.agencies.show', $this->agency)];
    }
}
