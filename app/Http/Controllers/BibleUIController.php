<?php

namespace App\Http\Controllers;

use App\Models\BibleBook;
use App\Models\BibleVerse;
use Illuminate\Http\Request;
use function view;

class BibleUIController extends Controller
{
    public function index(Request $request)
    {
        $version = strtoupper($request->version ?? 'KJV');
        $bookModel = (new BibleBook)->setTableByVersion($version);
        $books = $bookModel->orderBy('id')->get();

        return view('bible.index', compact('books', 'version'));
    }

    // AJAX: read verses
    public function readAjax(Request $request)
    {
        $version = strtoupper($request->version ?? 'KJV');

        $bookModel = (new BibleBook)->setTableByVersion($version);
        $verseModel = (new BibleVerse)->setTableByVersion($version);

        $book = $bookModel->find($request->book_id);
        if (!$book) return response()->json(['error' => 'Book not found']);

        $verses = $verseModel
            ->where('book_id', $book->id)
            ->where('chapter', $request->chapter)
            ->when($request->verse, fn($q) => $q->where('verse', $request->verse))
            ->orderBy('verse')
            ->get();

        return response()->json([
            'book' => $book->name,
            'chapter' => $request->chapter,
            'verses' => $verses
        ]);
    }

    // AJAX: search book autocomplete
    public function searchBooks(Request $request)
    {
        $version = strtoupper($request->version ?? 'KJV');
        $bookModel = (new BibleBook)->setTableByVersion($version);

        $query = $request->query('q', '');
        $books = $bookModel
            ->where('name', 'LIKE', "%$query%")
            ->limit(10)
            ->get(['id', 'name']);

        return response()->json($books);
    }
    public function bookInfo(Request $request)
    {
        $version = strtoupper($request->version ?? 'KJV');
        $bookModel = (new BibleBook)->setTableByVersion($version);
        $verseModel = (new BibleVerse)->setTableByVersion($version);

        $book = $bookModel->find($request->book_id);
        if (!$book) return response()->json(['error' => 'Book not found']);

        // Count max chapters
        $maxChapter = $verseModel->where('book_id', $book->id)->max('chapter');

        // Optional: get max verse per chapter
        $chapterVerses = [];
        for ($i = 1; $i <= $maxChapter; $i++) {
            $chapterVerses[$i] = $verseModel
                ->where('book_id', $book->id)
                ->where('chapter', $i)
                ->max('verse');
        }

        return response()->json([
            'book' => $book->name,
            'maxChapter' => $maxChapter,
            'chapterVerses' => $chapterVerses
        ]);
    }

}
