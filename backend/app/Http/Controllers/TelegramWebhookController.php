<?php

namespace App\Http\Controllers;

use App\Services\TelegramBotHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, TelegramBotHandler $handler): JsonResponse
    {
        $update = $request->all();

        if (!empty($update)) {
            $handler->handleUpdate($update);
        }

        return response()->json(['ok' => true]);
    }
}

