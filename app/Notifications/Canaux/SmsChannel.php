<?php

namespace App\Notifications\Canaux;

use App\Notifications\NotificationDag;

class SmsChannel extends CanalMessage
{
    protected function cle(): string
    {
        return 'sms';
    }

    protected function texte(NotificationDag $notification, object $notifiable): ?string
    {
        return $notification->toSms($notifiable);
    }
}
