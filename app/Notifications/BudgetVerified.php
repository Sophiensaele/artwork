<?php

namespace App\Notifications;

use App\Enums\NotificationFrequency;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use stdClass;

class BudgetVerified extends Notification
{
    use Queueable;
    protected ?stdClass $notificationData = null;
    protected array $broadcastMessage = [];

    public function __construct($notificationData, $broadcastMessage = [])
    {
        $this->notificationData = $notificationData;
        $this->broadcastMessage = $broadcastMessage;
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'message' => $this->broadcastMessage
        ]);
    }

    public function via($user): array
    {
        $channels = ['database'];

        $typeSettings = $user->notificationSettings()
            ->where('type', $this->notificationData->type)
            ->first();

        if($typeSettings?->enabled_email && $typeSettings?->frequency === NotificationFrequency::IMMEDIATELY) {
            $channels[] = 'mail';
        }

        if($typeSettings?->enabled_push && !empty($this->broadcastMessage)) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject($this->notificationData->title)
            ->markdown('emails.simple-mail', ['notification' => $this->notificationData]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return stdClass
     */
    public function toArray($notifiable)
    {

        return $this->notificationData;
    }
}
