<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Store extends Model
{
    use HasFactory;

    // Status Constants
    const STATUS_PENDING  = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'logo',
        'banner',
        'description',
        'location',
        'contact_info',
        'status',
        'rejection_reason',
    ];

    protected $casts = [
        'contact_info' => 'array', // Phone, business hours, social links, etc.
    ];

    // Automatically generate slug when creating a store
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($store) {
            if (empty($store->slug)) {
                $store->slug = Str::slug($store->name);
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // Helper Scope for public catalog queries
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

        public function reviews()
    {
        return $this->hasManyThrough(Review::class, Product::class);
    }

    public function getAverageRatingAttribute(): float
    {
        return round($this->reviews()->where('reviews.is_approved', true)->avg('rating') ?? 0, 1);
    }
}