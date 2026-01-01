<?php

namespace App\Http\Controllers;

use App\Models\BibleBook;
use App\Models\BibleVerse;
use App\Models\VerseRead;
use Carbon\Carbon;
use Illuminate\Http\Request;
use function auth;
use function response;
use function today;
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
        # log verses read
        foreach($verses as $v):
        VerseRead::firstOrCreate(
          [
              'version'=>$version,
              'book_id'=>$v->book_id,
              'chapter'=>$v->chapter,
              'verse'=>$v->verse,
              'read_date'=>Carbon::today()
            ]      
        );
        endforeach;
        
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
        if (!$book): return response()->json(['error' => 'Book not found']); endif;

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
    
    // for searchingscriptures
    public function searchScripture(Request $request){
        $q = trim($request->q);
        if (!$q || strlen($q) < 3) {
            return response()->json([]);
        }

        $mode = $request->mode ?? 'phrase';
        $version = strtoupper($request->version ?? 'KJV');

        $bookModel  = (new BibleBook)->setTableByVersion($version);
        $verseModel = (new BibleVerse)->setTableByVersion($version);

        if ($mode === 'phrase') {
            // Exact phrase match
            $results = $verseModel
                ->whereRaw("MATCH(text) AGAINST(? IN BOOLEAN MODE)", ['"' . $q . '"'])
                ->limit(100)
                ->get();
        } else {
            // All words must exist (exact terms)
            $terms = array_filter(explode(' ', $q));
            $boolean = implode(' ', array_map(fn($w) => '+' . $w, $terms));

            $results = $verseModel
                ->whereRaw("MATCH(text) AGAINST(? IN BOOLEAN MODE)", [$boolean])
                ->limit(100)
                ->get();
        }

        $books = $bookModel->pluck('name', 'id');

        return response()->json(
            $results->map(fn($v) => [
                'book_id' => $v->book_id,
                'book'    => $books[$v->book_id] ?? '',
                'chapter' => $v->chapter,
                'verse'   => $v->verse,
                'text'    => $v->text
            ])
        );
    }
    
    // get daily reading
    public function todayRead($date="") {
        return VerseRead::where('user_id',auth()->id())
                ->whereDate('read_date',$date)
                ->get();
    }
    
    public function dailyReads(Request $request)
        {
            $date    = $request->date
                ? Carbon::parse($request->date)
                : today();

            $version = strtoupper($request->version ?? 'KJV');

            $reads = VerseRead::whereDate('read_date', $date)
                ->where('version', $version)
                ->when(auth()->check(), fn($q) => $q->where('user_id', auth()->id()))
                ->orderBy('book_id')
                ->orderBy('chapter')
                ->orderBy('verse')
                ->get();

            if ($reads->isEmpty()) {
                return response()->json([]);
            }

            $bookModel = (new BibleBook)->setTableByVersion($version);
            $books = $bookModel->pluck('name', 'id');

            // Group nicely
            $grouped = $reads->groupBy(['book_id', 'chapter']);

            $output = [];

            foreach ($grouped as $bookId => $chapters) {
                foreach ($chapters as $chapter => $verses) {
                    $verseNumbers = $verses->pluck('verse')->sort()->values();

                    $output[] = [
                        'book_id' => $bookId,
                        'book'    => $books[$bookId] ?? '',
                        'chapter' => $chapter,
                        'verses'  => $verseNumbers,
                        'range'   => $this->formatVerseRange($verseNumbers),
                    ];
                }
            }

            return response()->json($output);
        }

        private function formatVerseRange($verses)
        {
            $ranges = [];
            $start = null;
            $prev  = null;

            foreach ($verses as $v) {
                if ($start === null) {
                    $start = $prev = $v;
                } elseif ($v == $prev + 1) {
                    $prev = $v;
                } else {
                    $ranges[] = ($start == $prev)
                        ? (string) $start
                        : $start . '-' . $prev;

                    $start = $prev = $v;
                }
            }

            if ($start !== null) {
                $ranges[] = ($start == $prev)
                    ? (string) $start
                    : $start . '-' . $prev;
            }

            return implode(', ', $ranges);
        }


}
