@extends('site.layouts.app')

@section('title', app()->getLocale() === 'ar' ? 'إتمام الطلب' : 'Checkout')

@section('content')
    @livewire('site.checkout-page')
@endsection