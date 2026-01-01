<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('verse_reads', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->string('version', 10);

        $table->unsignedInteger('book_id');
        $table->unsignedInteger('chapter');
        $table->unsignedInteger('verse');

        $table->date('read_date');
        $table->timestamps();

        $table->unique([
            'user_id',
            'version',
            'book_id',
            'chapter',
            'verse',
            'read_date'
        ], 'unique_daily_read');
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verse_reads');
    }
};
