<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'store_id',
        'parent_id',
        'order_number',
        'subtotal',
        'delivery_fee',
        'total_amount',
        'payment_method',
        'payment_status',
        'status',
        'shipping_name',
        'shipping_phone',
        'shipping_address',
        'city',
        'notes',
    ];

    protected $casts = [
        'subtotal'     => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-' . strtoupper(Str::random(8));
            }
        });
    }

    /**
     * The customer who placed the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The vendor store associated with a child sub-order (null for parent order).
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * The overarching parent order (if this is a vendor sub-order).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'parent_id');
    }

    /**
     * Vendor sub-orders linked to this parent order.
     */
    public function subOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'parent_id');
    }

    /**
     * Line items contained within this order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}