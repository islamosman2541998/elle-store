@extends('site.layouts.app')

@section('title', app()->getLocale() === 'ar' ? 'تفاصيل الطلب' : 'Order Details')

@section('content')
    @livewire('site.order-show', ['orderNumber' => $orderNumber])
@endsection