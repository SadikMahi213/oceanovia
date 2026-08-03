<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Mail\OrderConfirmation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmation implements ShouldQueue
{
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;
        $user = $order->user;

        if ($user && $user->email) {
            Mail::to($user)->queue(new OrderConfirmation($order));
        }
    }
}
