<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Sent synchronously: do not serialize the raw invitation token into a database queue.
class StaffInvited extends Notification
{
    public function __construct(public readonly string $acceptUrl) {}
    public function via(object $notifiable): array { return ['mail']; }
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Je uitnodiging voor Limenora')
            ->greeting('Welkom bij Limenora')
            ->line('Je bent uitgenodigd als medewerker. Maak een account aan of log in met dit e-mailadres.')
            ->line('Daarna kun je jouw profiel aanvullen en programma’s voorbereiden. De link is zeven dagen geldig en eenmalig te gebruiken.')
            ->action('Uitnodiging bekijken', $this->acceptUrl)
            ->line('Deze uitnodiging geeft geen behandelkwalificatie. Verwachtte je dit bericht niet? Dan kun je het negeren.');
    }
}
