<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\DB;

class BibleService
{
    protected TelegramService $telegram;

    public function __construct(TelegramService $telegram)
    {
        $this->telegram = $telegram;
    }

   public function search($update){
        $chatId = $update['message']['chat']['id'];
        $text   = trim($update['message']['text']);

        if (!preg_match(
            '/^([1-3]?\s?[A-Za-z]+)\s+(\d+)(?:[:\s](\d+)(?:-(\d+))?)?$/i',
            $text,
            $matches
        )) {
            return;
        }

        $bookInput  = trim($matches[1]);
        $chapter    = (int) $matches[2];
        $verseStart = $matches[3] ?? null;
        $verseEnd   = $matches[4] ?? null;

        $book = DB::table('kjv_books')
            ->where('name', 'like', $bookInput.'%')
            ->orWhere('abbreviation', 'like', $bookInput.'%')
            ->first();

        if (!$book) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "❌ Book not found."
            ]);
            return;
        }

        $query = DB::table('kjv_verses')
            ->where('book_id', $book->id)
            ->where('chapter', $chapter);

        if ($verseStart && $verseEnd) {
            $query->whereBetween('verse', [(int)$verseStart, (int)$verseEnd]);
        } elseif ($verseStart) {
            $query->where('verse', (int)$verseStart);
        }

        $verses = $query->orderBy('verse')->get();

        if ($verses->isEmpty()) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "❌ No verses found."
            ]);
            return;
        }

        $title = "{$book->name} {$chapter}";

        if ($verseStart && $verseEnd) {
            $title .= ":{$verseStart}-{$verseEnd}";
        } elseif ($verseStart) {
            $title .= ":{$verseStart}";
        }

        $message = "📖 * {$title} * \n\n";

        foreach ($verses as $v) {
            $message .= "{$v->verse}. {$v->text}\n\n";
        }

        foreach (str_split($message, 3500) as $chunk) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $chunk,
                'parse_mode' => 'Markdown',
            ]);
        }
    }

    public function handleCallback($update){
        $callback = $update['callback_query']['data'];
        $chatId   = $update['callback_query']['message']['chat']['id'];
        $messageId = $update['callback_query']['message']['message_id'];

        app(TelegramService::class)->answerCallback([
            'callback_query_id' => $update['callback_query']['id']
        ]);

        if (!str_starts_with($callback, 'next_') && 
            !str_starts_with($callback, 'prev_')) {
            return;
        }

        [$action, $bookId, $chapter, $verse] = explode('_', $callback);

        $verse = (int) $verse;

        if ($action === 'next') $verse++;
        if ($action === 'prev' && $verse > 1) $verse--;

        $verseData = DB::table('kjv_verses')
            ->where('book_id', $bookId)
            ->where('chapter', $chapter)
            ->where('verse', $verse)
            ->first();

        if (!$verseData) return;

        $book = DB::table('kjv_books')->where('id', $bookId)->first();

        $keyboard = app(KeyboardService::class)
            ->verseNavigation($bookId, $chapter, $verse);

        $newText = "📖 {$book->name} {$chapter}:{$verse}\n\n{$verseData->text}";

        app(TelegramService::class)->editMessage([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $newText,
            'reply_markup' => $keyboard
        ]);
    }
}