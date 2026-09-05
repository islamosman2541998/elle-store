@extends('site.layouts.app')

@section('title', app()->getLocale() === 'ar' ? 'السلة' : 'Cart')

@section('content')
    @livewire('site.cart-page')
@endsection