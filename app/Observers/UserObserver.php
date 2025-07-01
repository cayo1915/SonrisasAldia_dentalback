<?php

namespace App\Observers;

use App\Models\User;
use App\Models\UserReminderSetting;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        try {
            UserReminderSetting::create([
                'patient_id' => $user->id,
                'type' => 'preset',
                'preset_hours' => json_encode([24]),
            ]);

            UserReminderSetting::create([
                'patient_id' => $user->id,
                'type' => 'personalized',
                'custom_hours_before' => 72,
            ]);
        } catch (\Exception $e) {
            Log::error("Error al crear configuraciones de recordatorio para el usuario {$user->id}: {$e->getMessage()}");
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
