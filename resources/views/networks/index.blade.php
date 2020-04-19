@extends('layouts.app')

@section('content')

@foreach($networks as $network)

    <ul>
        <li>
            <a href="/networks/{{$network->id}}">{{ $network->name }}</a>
        </li>
    </ul>
@endforeach

@endsection
