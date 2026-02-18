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
       
    $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
    $update = $request->all();

    /*
    |--------------------------------------------------------------------------
    | 1️⃣ HANDLE NORMAL MESSAGE
    |--------------------------------------------------------------------------
    */
    if (isset($update['message'])) {

        $chatId = $update['message']['chat']['id'];
        $text   = trim($update['message']['text'] ?? '');

        // START
        if ($text === '/start') {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "📖 *KJV Bible Bot*\n\nType: John 3:16",
                'parse_mode' => 'Markdown'
            ]);

            return response()->json(['ok' => true]);
        }

        if (preg_match('/^([1-3]?\s?[A-Za-z]+)\s+(\d+)(?::|\s)?(\d+)?$/i', $text, $matches)) {

            $bookInput = trim($matches[1]);
            $chapter   = (int) $matches[2];
            $verse     = isset($matches[3]) ? (int) $matches[3] : null;

            $book = DB::table('kjv_books')
                ->where('name', 'like', $bookInput.'%')
                ->first();

            if (!$book || !$verse) {
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

            $response = "📖 {$book->name} {$chapter}:{$verse}\n\n{$verseData->text}";

            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $response,
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [
                            [
                                'text' => '⬅️ Prev',
                                'callback_data' => 'prev_'.$book->id.'_'.$chapter.'_'.$verse
                            ],
                            [
                                'text' => '➡️ Next',
                                'callback_data' => 'next_'.$book->id.'_'.$chapter.'_'.$verse
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
    | 2️⃣ HANDLE CALLBACK (MUST BE OUTSIDE MESSAGE BLOCK)
    |--------------------------------------------------------------------------
    */
    if (isset($update['callback_query'])) {

        $callback = $update['callback_query']['data'];
        $chatId = $update['callback_query']['message']['chat']['id'];
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

            if (!$verseData) return response()->json(['ok' => true]);

            $book = DB::table('kjv_books')->where('id', $bookId)->first();

            $text = "📖 {$book->name} {$chapter}:{$verse}\n\n{$verseData->text}";

            $telegram->editMessageText([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $text,
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [
                            [
                                'text' => '⬅️ Prev',
                                'callback_data' => 'prev_'.$bookId.'_'.$chapter.'_'.$verse
                            ],
                            [
                                'text' => '➡️ Next',
                                'callback_data' => 'next_'.$bookId.'_'.$chapter.'_'.$verse
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
