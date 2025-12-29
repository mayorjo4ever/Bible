@extends('layouts.app')

@section('content')

<div class="row">
    <div class="col-md-3">
        <div class="bible-sidebar bg-white">
            <!--<input type="text" id="book-search" class="form-control mb-3" placeholder="Search Book">-->
            <input type="text" id="smart-search" class="form-control mb-3" placeholder="Search Book">
            
            <select id="version-select" class="form-select mb-3">
                <option value="asv">ASV</option>
                <option value="bbe">Bible In Basic English (BBE) </option>
                <option value="kjv" selected>KJV</option>                
            </select>
            
            <select id="book-select" class="form-select mb-3">
                @foreach($books as $b)
                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                @endforeach
            </select>

            <!--<input type="number" id="chapter-input" class="form-control mb-2" placeholder="Chapter" min="1">-->
            <select id="chapter-select" class="form-select mb-2">
                <option value="">Select Chapter</option>
            </select>
            <input type="number" id="verse-input" class="form-control mb-2" placeholder="Verse (optional)" min="1">


            <button id="read-btn" class="btn btn-info w-100 p-2 text-white"><b>Search &nbsp; 📖</b> </button>
        </div>
    </div>

    <div class="col-md-9 main-content" style="background-color: #fff; margin: 0px;">
        <h4 id="verse-title"  style="font-size: 2.2rem; margin: 10px 5px;">Select a book and chapter</h4>
        <div id="search-results" class="mb-3" style="font-size: 1.6rem; "></div>
        <div id="verse-content" class="ml-6"  style="font-size: 1.6rem; padding-left: 10px"></div>
    </div>
</div>

@endsection
