@extends('layouts.app')
@section('title', $title.' | Gobiz')
@section('content')
<article class="card"><h2>{{ $title }}</h2>
@yield('body')
</article>
@endsection
