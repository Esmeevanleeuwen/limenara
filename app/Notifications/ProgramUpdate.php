<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProgramUpdate extends Notification
{
    public function __construct(public string $kind, public string $path) {}
    public function via(object $notifiable): array { return ['mail']; }
    public function toMail(object $notifiable): MailMessage
    {
        [$subject, $text, $button] = match ($this->kind) {
            'enrolled' => ['Je inschrijving is bevestigd', 'Je kunt beginnen wanneer het jou uitkomt. De onderdelen staan klaar in jouw besloten omgeving.', 'Open je programma'],
            'completed' => ['Je programma is doorlopen', 'Je hebt alle onderdelen als afgerond gemarkeerd. Dit zegt niets over je gezondheid of herstel. Je kunt altijd terugkijken.', 'Bekijk je overzicht'],
            'review' => ['Er staat een concept klaar', 'Een andere maker heeft een programma ter beoordeling aangeboden. Controleer de inhoud voordat je beslist.', 'Open de beoordeling'],
            'published' => ['Je programma is gepubliceerd', 'Er is een nieuwe vaste versie gepubliceerd. Bestaande deelnemers houden hun eerdere versie.', 'Open je werkomgeving'],
            'returned' => ['Je concept vraagt nog aandacht', 'De beoordelaar heeft een toelichting toegevoegd in jouw werkomgeving.', 'Bekijk de toelichting'],
            'shared' => ['Er staat een gedeeld antwoord klaar', 'Een deelnemer heeft bewust een antwoord met jou gedeeld. Er staan geen persoonlijke antwoorden in deze e-mail.', 'Open de inzending'],
            'feedback' => ['Er staat een reactie voor je klaar', 'De maker heeft gereageerd op een antwoord dat jij hebt gedeeld. Lees de reactie in jouw besloten omgeving.', 'Bekijk de reactie'],
            default => ['Er staat iets klaar in Limenora', 'Open jouw omgeving om verder te gaan.', 'Open Limenora'],
        };
        return (new MailMessage)->subject($subject.' — Limenora')->greeting('Hallo,')->line($text)
            ->action($button, url($this->path))->line('Deze ontwikkelversie is geen behandeling of spoedhulp. Gebruik alleen testgegevens.')
            ->salutation('Limenora · ruimte om te begrijpen');
    }
}
