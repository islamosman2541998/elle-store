@extends('site.layouts.app')

@section('title', app()->getLocale() === 'ar' ? 'إنشاء حساب' : 'Register')

@section('content')
    @livewire('site.customer-register')
@endsection