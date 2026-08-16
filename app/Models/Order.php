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

    /**
     * Assign an unguessable public tracking token on creation.
     */
    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->public_token)) {
                $order->public_token = (string) Str::uuid();
            }
        });
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'workspace_id',
        'order_number',
        'public_token',
        'table_id',
        'created_by',
        'status',
        'subtotal',
        'total',
        'notes',
        'payment_method',
        'placed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'placed_at' => 'datetime',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
