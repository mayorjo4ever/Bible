<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BibleVerse extends Model
{
    protected $table = 'kjv_verses';
    public $timestamps = false;

    protected $fillable = ['book_id', 'chapter', 'verse', 'text'];
    
    public function setTableByVersion(string $version)
    {
        $this->setTable($version . '_verses');
        return $this;
    }
    
     public function book(string $version)
    {
        return $this->belongsTo(
            BibleBook::class,
            'book_id'
        )->from($version . '_books');
    }
}
