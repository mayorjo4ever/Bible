<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BibleBook extends Model
{
     protected $table = 'kjv_books';
          
        public $timestamps = false;
        
        protected $fillable = ['name'];

        public function setTableByVersion(string $version)
        {
            $this->setTable($version . '_books');
            return $this;
        }

        public function verses()
        {
            return $this->hasMany(BibleVerse::class, 'book_id');
        }
    
}
