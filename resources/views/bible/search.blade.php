@extends('layouts.app')

@section('content')

<h4>Search Results</h4>

@foreach($results as $v)
    <p>
        <strong>{{ $v->book->name }} {{ $v->chapter }}:{{ $v->verse }}</strong><br>
        {{ $v->text }}
    </p>
@endforeach

@endsection
