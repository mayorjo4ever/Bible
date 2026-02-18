<?php

use App\Http\Controllers\BibleUIController;
use App\Http\Controllers\TelegramBotController;
use Illuminate\Support\Facades\Route;

 

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::get('/', function () {
    // return view('welcome');
    return redirect('/bible/web');
});

 Route::get('/bible/web', [BibleUIController::class, 'index'])->name('bible.index');
Route::match(['get','post'], '/bible/read', [BibleUIController::class, 'readAjax'])->name('bible.read.ajax');
Route::get('/bible/search-books', [BibleUIController::class, 'searchBooks'])->name('bible.search.books');
Route::get('/bible/book-info', [BibleUIController::class, 'bookInfo'])->name('bible.book.info');
Route::get('/bible/search-scripture', [BibleUIController::class, 'searchScripture'])
    ->name('bible.search.scripture');

# get daily read scriptures 
Route::get('/bible/daily-reads', [BibleUIController::class, 'dailyReads'])
    ->name('bible.daily.reads');

//
//Route::post('/telegram/webhook', [TelegramBotController::class, 'webhook'])->withoutMiddleware(
//        [\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]
//);
//
 Route::post('/bible', [TelegramBotController::class, 'webhook'])->withoutMiddleware(
         [\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]
 );