@extends('site.layouts.app')


@section('content')
    @livewire('site.product-show', ['slug' => $slug])
@endsection