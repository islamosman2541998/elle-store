@extends('site.layouts.app')

@section('title', app()->getLocale() === 'ar' ? 'طلباتي' : 'My Orders')

@section('content')
    @livewire('site.customer-orders')
@endsection