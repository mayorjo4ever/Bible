<?php

namespace App\Services\Telegram;

use Telegram\Bot\Api;

class TelegramService
{
    protected Api $telegram;

    public function __construct()
    {
        $this->telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
    }
    
    public function sendPhoto($data)
    {
        return $this->telegram->sendPhoto($data);
    }

    public function sendMessage($data)
    {
        return $this->telegram->sendMessage($data);
    }

    public function editMessage($data)
    {
        return $this->telegram->editMessageText($data);
    }

    public function answerCallback($data)
    {
        return $this->telegram->answerCallbackQuery($data);
    }
}