@extends('layouts.app')

@section('content')

<h4>Search Results</h4>
<?php $k=1;?>
@foreach($results as $v)
    <p>
        <sup class="text-dark font-semibold text-lg">{{$k}}</sup>&nbsp; <strong>{{ $v->book->name }} {{ $v->chapter }}:{{ $v->verse }}</strong><br>
        {{ $v->text }}
    </p>
    <?php $k++; ?>
@endforeach

@endsection
