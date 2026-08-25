<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Order extends Model
{
    public const STATUSES = ['pending', 'confirmed', 'processing', 'completed', 'cancelled'];

    protected $fillable = ['order_number', 'user_id', 'customer_name', 'guest_email', 'phone', 'delivery_address', 'status', 'total'];

    protected function casts(): array
    {
        return ['total' => 'decimal:2'];
    }

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function financialTransaction(): MorphOne
    {
        return $this->morphOne(FinancialTransaction::class, 'source');
    }
}
