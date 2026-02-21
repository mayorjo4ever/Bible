<?php

namespace App\Services\Telegram;

class KeyboardService
{
    public function mainMenu()
    {
        return json_encode([
            'keyboard' => [
                [['text' => '📖 Search Verse']],
                [['text' => '👥 My Referrals'], ['text' => '📤 Invite Friends']]
            ],
            'resize_keyboard' => true
        ]);
    }

    public function verseNavigation($bookId, $chapter, $verse)
    {
        return json_encode([
            'inline_keyboard' => [
                [
                    [
                        'text' => '⬅️ Prev',
                        'callback_data' => "prev_{$bookId}_{$chapter}_{$verse}"
                    ],
                    [
                        'text' => '➡️ Next',
                        'callback_data' => "next_{$bookId}_{$chapter}_{$verse}"
                    ]
                ]
            ]
        ]);
    }
}