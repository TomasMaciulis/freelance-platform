@extends('layout/app')
@section('navbar')
    @include('navbar')
@endsection
@section('layout')
    <div class="flex justify-center">
        <div class="w-11/12 md:w-3/4 lg:w-2/3 mt-5 max-w-4xl">
            @yield('content')
        </div>
    </div>
@endsection
