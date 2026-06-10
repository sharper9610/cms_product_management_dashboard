<?php

namespace App\Services\OrderProcessing;

use Illuminate\Notifications\Notifiable;

class OrderAlertReceiver
{
    use Notifiable;

    public function routeNotificationForMail()
    {
        return config('mail.order_alert_emails');
    }

    public function ccEmails(): array
    {
        return config('mail.order_alert_cc_emails');
    }
}
