@extends('site.layouts.app')

@section('title', app()->getLocale() === 'ar' ? 'تسجيل الدخول' : 'Login')

@section('content')
    @livewire('site.customer-login')
@endsection