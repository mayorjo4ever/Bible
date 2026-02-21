<?php

namespace App\Services\Telegram;
use Telegram\Bot\FileUpload\InputFile;
use Illuminate\Support\Facades\DB;

class ReferralService
{
    protected TelegramService $telegram;

    public function __construct(TelegramService $telegram)
    {
        $this->telegram = $telegram;
    }

   public function start($update)
    {
        $chatId = $update['message']['chat']['id'];
        $telegramId = $update['message']['from']['id'];
        $firstName = $update['message']['from']['first_name'] ?? '';

        $referralLink = "https://t.me/Theholy_bible_bot?start={$telegramId}";

        $caption = "📖 *Welcome {$firstName}!*

        ✨ * Holy Bible KJV & Hymns *

        You can:
        • Search any Bible verse
        • Read full chapters
        • Search by keyword
        • Read verse ranges
        • Navigate verses easily

        📌 *How To Use:* 

        Type references like:
        • John 3:16
        • James 3 6
        • 1 Cor 13:4-7
        • Psalm 23
        • search love

        🎯 * Search Only Beieve Hymns:*
        • Hymn 100
        • Hymn 25
        • Hymn 1
        
        
        📖 *Continue Your Study*
        
        ";

        $keyboard = app(\App\Services\Telegram\KeyboardService::class)
            ->mainMenu();

        app(\App\Services\Telegram\TelegramService::class)
            ->sendPhoto([
                'chat_id' => $chatId,
                'chat_id' => $chatId,
                'photo' => InputFile::create(public_path('images/bible.png')),    
                'caption' => $caption,
                'parse_mode' => 'Markdown',
                'reply_markup' => $keyboard
            ]);
        }   

   public function myRef($update){
    $chatId = $update['message']['chat']['id'];
    $telegramId = $update['message']['from']['id'];

    $referrals = DB::table('telegram_users')
        ->where('referred_by', $telegramId)
        ->orderBy('created_at', 'desc')
        ->get();

    if ($referrals->isEmpty()) {
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "👥 You have no referrals yet."
        ]);
        return;
    }

    $message = "👥 *Your Referrals (" . $referrals->count() . ")*\n\n";

    foreach ($referrals as $index => $user) {

        $name = $user->first_name ?? 'Unknown';
        $username = $user->username ? "@{$user->username}" : '';

        $message .= ($index + 1) . ". {$name} {$username}\n";
    }

    foreach (str_split($message, 3500) as $chunk) {
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $chunk,
            'parse_mode' => 'Markdown'
        ]);
    }
}

    public function invite($update)
    {
        $chatId = $update['message']['chat']['id'];
        $telegramId = $update['message']['from']['id'];

        $referralLink = "https://t.me/Theholy_bible_bot?start={$telegramId}";

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "📤 Invite friends:\n{$referralLink}"
        ]);
    }
}