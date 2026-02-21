<?php

namespace App\Services\Telegram;

class TelegramHandler
{
    public function handle(array $update)
    {
        if (isset($update['message'])) {
            app(CommandHandler::class)->handle($update);
        }
       
        if (isset($update['message']['photo'])) {

            $photos = $update['message']['photo'];

            $largestPhoto = end($photos);

            \Log::info('WELCOME_IMAGE_FILE_ID: ' . $largestPhoto['file_id']);
        }

        if (isset($update['callback_query'])) {
            app(BibleService::class)->handleCallback($update);
        }
    }
}