<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Telegram\Bot\Api;
use function env;
use function GuzzleHttp\json_encode;
use function response;
use function str_starts_with;


class TelegramBotController extends Controller
{
    public function webhook(Request $request){
        
        app(\App\Services\Telegram\TelegramHandler::class)
            ->handle($request->all());
        
        return response()->json(['ok' => true]);
    }
    
  public function webhook2(Request $request){
    $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
    $update = $request->all();

    /*
    |--------------------------------------------------------------------------
    | HANDLE NORMAL MESSAGE
    |--------------------------------------------------------------------------
    */
    if (isset($update['message'])) {

        $chatId = $update['message']['chat']['id'];
        $text   = trim($update['message']['text'] ?? '');

        // START
       if (str_starts_with($text, '/start')) {

            $parts = explode(' ', $text);
            $refCode = $parts[1] ?? null;

            $telegramId = $update['message']['from']['id'];
            $username = $update['message']['from']['username'] ?? null;
            $firstName = $update['message']['from']['first_name'] ?? null;

            // Check if user already exists
            $existingUser = DB::table('telegram_users')
                ->where('telegram_id', $telegramId)
                ->first();

            if (!$existingUser) {

                DB::table('telegram_users')->insert([
                    'telegram_id' => $telegramId,
                    'username' => $username,
                    'first_name' => $firstName,
                    'referred_by' => $refCode,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Increase referrer count
                if ($refCode && $refCode != $telegramId) {
                    DB::table('telegram_users')
                        ->where('telegram_id', $refCode)
                        ->increment('referrals_count');
                }
            }
            $referralLink = "https://t.me/Theholy_bible_bot?start={$telegramId}";

            $shareText = urlencode(
                "📖 Join this powerful KJV Bible Bot and grow spiritually!\n\n{$referralLink}"
            );

            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "📖 *Your Referral Link*\n\n{$referralLink}\n\nShare and invite friends!",
                'parse_mode' => 'Markdown',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [
                            [
                                'text' => '📤 Share on WhatsApp',
                                'url'  => "https://wa.me/?text={$shareText}"
                            ]
                        ],
                        [
                            [
                                'text' => '📨 Share on Telegram',
                                'url'  => "https://t.me/share/url?url={$referralLink}&text=Join this Bible Bot!"
                            ]
                        ]
                    ]
                ])
            ]);

            return response()->json(['ok' => true]);
        }


        // show my referrers 
        if ($text === '/myref') {

            $telegramId = $update['message']['from']['id'];

            $user = DB::table('telegram_users')
                ->where('telegram_id', $telegramId)
                ->first();

            $count = $user->referrals_count ?? 0;

            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "👥 You have {$count} referrals."
            ]);

            return response()->json(['ok' => true]);
        }


        // VERSE SEARCH
        if (preg_match('/^([1-3]?\s?[A-Za-z]+)\s+(\d+):?(\d+)?$/i', $text, $matches)) {

            $bookInput = trim($matches[1]);
            $chapter   = (int) $matches[2];
            $verse     = isset($matches[3]) ? (int) $matches[3] : 1;

            $book = DB::table('kjv_books')
                ->where('name', 'like', $bookInput.'%')
                ->first();

            if (!$book) {
                return response()->json(['ok' => true]);
            }

            $verseData = DB::table('kjv_verses')
                ->where('book_id', $book->id)
                ->where('chapter', $chapter)
                ->where('verse', $verse)
                ->first();

            if (!$verseData) {
                return response()->json(['ok' => true]);
            }

            $message = "📖 {$book->name} {$chapter}:{$verse}\n\n{$verseData->text}";

            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [
                            [
                                'text' => '⬅️ Prev',
                                'callback_data' => "prev_{$book->id}_{$chapter}_{$verse}"
                            ],
                            [
                                'text' => '➡️ Next',
                                'callback_data' => "next_{$book->id}_{$chapter}_{$verse}"
                            ]
                        ]
                    ]
                ])
            ]);

            return response()->json(['ok' => true]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | HANDLE CALLBACK BUTTON
    |--------------------------------------------------------------------------
    */
    if (isset($update['callback_query'])) {

        $callback = $update['callback_query']['data'];
        $chatId   = $update['callback_query']['message']['chat']['id'];
        $messageId = $update['callback_query']['message']['message_id'];

        $telegram->answerCallbackQuery([
            'callback_query_id' => $update['callback_query']['id']
        ]);

        if (str_starts_with($callback, 'next_') || str_starts_with($callback, 'prev_')) {

            [$action, $bookId, $chapter, $verse] = explode('_', $callback);

            $verse = (int) $verse;

            if ($action === 'next') $verse++;
            if ($action === 'prev' && $verse > 1) $verse--;

            $verseData = DB::table('kjv_verses')
                ->where('book_id', $bookId)
                ->where('chapter', $chapter)
                ->where('verse', $verse)
                ->first();

            if (!$verseData) {
                return response()->json(['ok' => true]);
            }

            $book = DB::table('kjv_books')->where('id', $bookId)->first();

            $newText = "📖 {$book->name} {$chapter}:{$verse}\n\n{$verseData->text}";

            $telegram->editMessageText([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $newText,
                'reply_markup' => json_encode([
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
                ])
            ]);
        }
    }

    return response()->json(['ok' => true]);
}

}

//
// https://autogenously-frostiest-chaim.ngrok-free.dev
//     https://api.telegram.org/bot8338808361:AAGS7ARH6FBZUk0QoCo51TtwcjkxwCOAT5k/setWebhook?url=https://autogenously-frostiest-chaim.ngrok-free.dev/bible
