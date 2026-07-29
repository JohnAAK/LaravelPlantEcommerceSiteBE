<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inquiry extends Model
{
    protected $fillable = ['customer_id', 'store_id', 'message', 'parent_id'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    // Self-referencing relationship to handle vendor text replies
    public function replies()
    {
        return $this->hasMany(Inquiry::class, 'parent_id');
    }
}