<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'occasion',
        'event_date',
        'share_code',
        'is_public',
    ];

    protected $casts = [
        'event_date' => 'date',
        'is_public' => 'boolean',
        'suspended_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(GiftItem::class);
    }

    public function getProgressPercentageAttribute(): int
    {
        $total = $this->items->count();
        if ($total === 0) {
            return 0;
        }

        $reserved = $this->items->where('status', 'reserved')->count();

        return (int) round(($reserved / $total) * 100);
    }

    public function getReservedCountAttribute(): int
    {
        return $this->items->where('status', 'reserved')->count();
    }

    public function getTotalCountAttribute(): int
    {
        return $this->items->count();
    }

    public function getOccasionIconAttribute(): string
    {
        return match ($this->occasion) {
            'christmas' => '🎄',
            'birthday' => '🎂',
            'wedding' => '💍',
            'anniversary' => '🥂',
            default => '🎁',
        };
    }

    public function getOccasionLabelAttribute(): string
    {
        return match ($this->occasion) {
            'christmas' => 'Vánoce',
            'birthday' => 'Narozeniny',
            'wedding' => 'Svatba',
            'anniversary' => 'Výročí',
            default => 'Příležitost',
        };
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }
}
