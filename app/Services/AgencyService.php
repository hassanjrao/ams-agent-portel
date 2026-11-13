<?php

namespace App\Services;

use App\Enums\AgencyStatus;
use App\Models\Agency;
use App\Models\User;

class AgencyService
{
    /**
     * Approve (active) or suspend an agency. The first approval is stamped; a suspended agency keeps
     * its bookings and ledger, it just can't make new bookings.
     */
    public function setStatus(Agency $agency, AgencyStatus $status, User $admin, ?string $notes = null): Agency
    {
        $agency->status = $status;
        $agency->admin_notes = $notes;
        if ($status === AgencyStatus::Active && ! $agency->approved_at) {
            $agency->approved_at = now();
            $agency->approved_by = $admin->id;
        }
        $agency->save();

        return $agency;
    }
}
