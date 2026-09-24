<?php

namespace App\Notifications;

use App\Models\GiftItem;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GiftItemReserved extends Notification
{
    public function __construct(public GiftItem $item) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $wishlist = $this->item->wishlist;

        return (new MailMessage)
            ->subject('Dárek ve vašem seznamu byl rezervován 🎁')
            ->greeting('Dobrý den, '.$notifiable->name.'!')
            ->line("Dárek \"{$this->item->title}\" ve vašem seznamu \"{$wishlist->title}\" byl právě rezervován.")
            ->line('Kdo dárek zamluvil, zůstává tajemstvím — ať vám překvapení nezkazíme.')
            ->action('Zobrazit seznam', route('wishlists.show', $wishlist));
    }
}
