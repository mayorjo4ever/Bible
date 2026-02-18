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

    // HANDLE MESSAGE
    if (isset($update['message'])) {
        $chatId = $update['message']['chat']['id'];
        $text   = trim($update['message']['text'] ?? '');

        // START
        if ($text === '/start') {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "📖 *KJV Bible Bot*\n\nJust type a verse reference:\n\nExamples:\n• John 3:16\n• Matt 28 19\n• Ps 23\n• 1 Cor 13:4",
                'parse_mode' => 'Markdown'
            ]);

            return response()->json(['ok' => true]);
        }

        // SMART VERSE SEARCH
        if (preg_match('/^([1-3]?\s?[A-Za-z]+)\s+(\d+)(?::|\s)?(\d+)?$/i', $text, $matches)) {

            $bookInput = trim($matches[1]);
            $chapter   = (int) $matches[2];
            $verse     = isset($matches[3]) ? (int) $matches[3] : null;

            $book = DB::table('kjv_books')
                ->where('name', 'like', $bookInput.'%')
                ->first();


            if (!$book) {
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "❌ Book not found. Try: John 3:16"
                ]);
                return response()->json(['ok' => true]);
            }

            $query = DB::table('kjv_verses')
                ->where('book_id', $book->id)
                ->where('chapter', $chapter);

            if ($verse) {
                $query->where('verse', $verse);
            }

            $verses = $query->orderBy('verse')->get();

            if ($verses->isEmpty()) {
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "❌ No verses found."
                ]);
                return response()->json(['ok' => true]);
            }

            $response = "📖 {$book->name} {$chapter}";
            if ($verse) $response .= ":$verse";
            $response .= "\n\n";

            foreach ($verses as $v) {
                $response .= "{$v->verse}. {$v->text}\n\n";
            }

            foreach (str_split($response, 3500) as $chunk) {
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $chunk
                ]);
            }

            return response()->json(['ok' => true]);
        }
    }

    return response()->json(['ok' => true]);
}
  
}

//
// https://autogenously-frostiest-chaim.ngrok-free.dev
//     https://api.telegram.org/bot8338808361:AAGS7ARH6FBZUk0QoCo51TtwcjkxwCOAT5k/setWebhook?url=https://autogenously-frostiest-chaim.ngrok-free.dev/bible
