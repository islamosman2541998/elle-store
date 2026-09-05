<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'email_verified_at',
        'phone_verified_at',
        'password',
        'birth_date',
        'gender',
        'is_active',
        'accepts_marketing',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'birth_date' => 'date',
        'is_active' => 'boolean',
        'accepts_marketing' => 'boolean',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function defaultAddress()
    {
        return $this->hasOne(CustomerAddress::class)
            ->where('is_default', true);
    }
    public function wishlistItems()
{
    return $this->hasMany(WishlistItem::class);
}

public function carts()
{
    return $this->hasMany(Cart::class);
}

public function activeCart()
{
    return $this->hasOne(Cart::class)
        ->where('status', 'active');
}
public function orders()
{
    return $this->hasMany(Order::class);
}
public function invoices()
{
    return $this->hasMany(Invoice::class);
}
public function returnRequests()
{
    return $this->hasMany(ReturnRequest::class);
}
}