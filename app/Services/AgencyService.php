<?php

namespace App\Services;

use App\Enums\AgencyStatus;
use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\User;
use App\Notifications\AgencyStatusChanged;
use App\Notifications\NewAgencyRegistered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class AgencyService
{
    /**
     * A travel agency signs up: the agency (waiting for approval) and its first login, together.
     *
     * @param  array{agency_name: string, name: string, email: string, password: string, phone: string, city: string, address?: string|null}  $data
     */
    public function register(array $data): User
    {
        $user = DB::transaction(function () use ($data) {
            $agency = new Agency([
                'name' => trim($data['agency_name']),
                'contact_person' => trim($data['name']),
                'phone' => trim($data['phone']),
                'email' => $data['email'],
                'city' => trim($data['city']),
                'address' => isset($data['address']) ? trim($data['address']) ?: null : null,
            ]);
            $agency->status = AgencyStatus::Pending;
            $agency->save();

            $user = new User([
                'name' => trim($data['name']),
                'email' => $data['email'],
                'password' => $data['password'],
                'phone' => trim($data['phone']),
            ]);
            $user->role = UserRole::Agent;
            $user->agency_id = $agency->id;
            $user->save();

            return $user;
        });

        Notification::send(User::query()->admins()->get(), new NewAgencyRegistered($user->agency));

        return $user;
    }

    /**
     * Approve (active) or suspend an agency. The first approval is stamped; a suspended agency keeps
     * its bookings and ledger, it just can't make new bookings.
     */
    public function setStatus(Agency $agency, AgencyStatus $status, User $admin, ?string $notes = null): Agency
    {
        $changed = $agency->status !== $status;

        $agency->status = $status;
        $agency->admin_notes = $notes;
        if ($status === AgencyStatus::Active && ! $agency->approved_at) {
            $agency->approved_at = now();
            $agency->approved_by = $admin->id;
        }
        $agency->save();

        if ($changed) {
            Notification::send($agency->users, new AgencyStatusChanged($agency));
        }

        return $agency;
    }
}
