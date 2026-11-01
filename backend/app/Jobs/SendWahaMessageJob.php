<?php

namespace App\Jobs;

use App\Services\WahaClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendWahaMessageJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $chatId,
        public string $text
    ) {
        $this->afterCommit = true;
    }

    /**
     * Execute the job.
     */
    public function handle(WahaClient $client): void
    {
        try {
            $client->sendText($this->chatId, $this->text);
        } catch (\Throwable $e) {
            Log::warning('SendWahaMessageJob failed: ' . $e->getMessage());
        }
    }
}

