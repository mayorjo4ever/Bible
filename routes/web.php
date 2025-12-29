<?php

use App\Http\Controllers\BibleUIController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/bible', [BibleUIController::class, 'index'])->name('bible.index');
Route::match(['get','post'], '/bible/read', [BibleUIController::class, 'readAjax'])->name('bible.read.ajax');
Route::get('/bible/search-books', [BibleUIController::class, 'searchBooks'])->name('bible.search.books');
Route::get('/bible/book-info', [BibleUIController::class, 'bookInfo'])->name('bible.book.info');
Route::get('/bible/search-scripture', [BibleUIController::class, 'searchScripture'])
    ->name('bible.search.scripture');

