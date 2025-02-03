<?php
namespace App\Observers;

use App\Models\EqNotification;
use App\Events\NewNotification;

class EqNotificationObserver
{
    /**
     * Handle the EqNotification "created" event.
     *
     * @param  \App\Models\EqNotification  $eqNotification
     * @return void
     */
    public function created(EqNotification $eqNotification)
    {
        // Load the byUser relationship if not already loaded
        if (!$eqNotification->relationLoaded('user')) {
            $eqNotification->load('user');
        }

        // Prepare notification data
        $notificationData = [
            'id' => $eqNotification->id, // Use notification ID instead of foreign_id
            'foreign_id' => $eqNotification->foreign_id,
            'recipient_id' => $eqNotification->user_id,
            'type' => $eqNotification->notif_type,
            'is_read' => $eqNotification->is_read,
            'sender' => [
                'id' => $eqNotification->by_user,
                'name' => $eqNotification->user->name,
                'profile_image' => $eqNotification->user->profile_image
            ],
            'message' => $eqNotification->content,
            'created_at' => $eqNotification->created_at
        ];

        // Broadcast the notification event
        broadcast(new NewNotification($notificationData))->toOthers();
    }
}