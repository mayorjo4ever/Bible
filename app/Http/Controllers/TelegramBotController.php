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
    public function webhook(Request $request)
    {
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));

        $update = $request->all();

        // Handle message
        if (isset($update['message'])) {
            $chatId = $update['message']['chat']['id'];
            $text = trim($update['message']['text'] ?? '');

            if ($text === '/start') {
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "📖 Welcome to the Bible Bot\nType /books to see the list of Bible books"
                ]);
            }

            if ($text === '/books') {
                $books = DB::table('kjv_books')->get();
                $buttons = [];

                foreach ($books as $book) {
                    $buttons[][] = [
                        'text' => $book->name,
                        'callback_data' => 'book_'.$book->id
                    ];
                }

                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => '📘 Select a Bible Book:',
                    'reply_markup' => json_encode([
                        'inline_keyboard' => $buttons
                    ])
                ]);
            }
        }

        // Handle button callback
        if (isset($update['callback_query'])) {
            $callback = $update['callback_query']['data'];
            $chatId = $update['callback_query']['message']['chat']['id'];

            // Book selected → show chapters
            if (str_starts_with($callback, 'book_')) {
                $bookId = str_replace('book_', '', $callback);

                $chapters = DB::table('bible_chapters')
                    ->where('book_id', $bookId)
                    ->get();

                $buttons = [];
                foreach ($chapters as $ch) {
                    $buttons[][] = [
                        'text' => 'Chapter '.$ch->chapter_number,
                        'callback_data' => 'chapter_'.$bookId.'_'.$ch->chapter_number
                    ];
                }

                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => '📖 Select a Chapter:',
                    'reply_markup' => json_encode([
                        'inline_keyboard' => $buttons
                    ])
                ]);
            }

            // Chapter selected → show first 10 verses
            if (str_starts_with($callback, 'chapter_')) {
                [$x, $bookId, $chapter] = explode('_', $callback);

                $verses = DB::table('bible_verses')
                    ->where('book_id', $bookId)
                    ->where('chapter', $chapter)
                    ->limit(10)
                    ->get();

                $text = "";
                foreach ($verses as $v) {
                    $text .= "{$v->verse}. {$v->text}\n\n";
                }

                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $text
                ]);
            }
        }

        return response()->json(['ok' => true]);
    }
}

//
// https://autogenously-frostiest-chaim.ngrok-free.dev
//     https://api.telegram.org/bot8338808361:AAGS7ARH6FBZUk0QoCo51TtwcjkxwCOAT5k/setWebhook?url=https://autogenously-frostiest-chaim.ngrok-free.dev/bible
