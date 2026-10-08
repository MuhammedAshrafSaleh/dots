<?php

namespace App\Models;

use App\Enums\CustomerStatus;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Storefront customer — a separate auth guard from the admin (users).
 *
 * @property string|null $remember_token
 */
class Customer extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = ['name', 'mobile', 'email', 'password', 'status'];

    /** @var list<string> */
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'status' => CustomerStatus::class,
            'last_login_at' => 'datetime',
        ];
    }

    /** @return HasMany<CustomerAddress, $this> */
    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** @return HasMany<Cart, $this> */
    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    /** @return BelongsToMany<Product, $this> */
    public function wishlist(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'wishlists')
            ->withPivot('created_at');
    }
}
