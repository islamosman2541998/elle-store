<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;

class OrderController extends Controller
{
    public function show(string $orderNumber)
    {
        return view('site.pages.orders.show', [
            'orderNumber' => $orderNumber,
        ]);
    }
}