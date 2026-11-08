<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Payment;
use App\Services\TelegramNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendTelegramNotificationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param string $type 'order' or 'payment'
     * @param int $entityId ID of the Order or Payment
     */
    public function __construct(
        public string $type,
        public int $entityId
    ) {
        $this->afterCommit = true;
    }

    /**
     * Execute the job.
     */
    public function handle(TelegramNotifier $notifier): void
    {
        try {
            if ($this->type === 'order') {
                if (!config('services.telegram.notify_orders', false)) {
                    Log::debug('Telegram order notification skipped: channel is reserved for owner (orders excluded).');
                    return;
                }
                $order = Order::with('items.product')->find($this->entityId);
                if ($order) {
                    $notifier->notifyNewOrder($order);
                }
            } elseif ($this->type === 'payment') {
                $payment = Payment::with('order')->find($this->entityId);
                if ($payment) {
                    $notifier->notifyPaymentReceived($payment);
                }
            }
        } catch (\Throwable $e) {
            Log::warning("SendTelegramNotificationJob failed: " . $e->getMessage());
        }
    }
}
