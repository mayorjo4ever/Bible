<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerseRead extends Model
{
    protected $fillable = [
        'user_id',
        'version',
        'book_id',
        'chapter',
        'verse',
        'read_date'
    ];
}
