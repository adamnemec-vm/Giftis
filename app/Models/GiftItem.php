<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'wishlist_id',
        'title',
        'url',
        'price',
        'currency',
        'image_path',
        'description',
        'priority',
        'status',
        'is_group_gift',
        'reserved_by_name',
        'reserved_by_user_id',
        'reservation_token',
        'reserved_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_group_gift' => 'boolean',
        'reserved_at' => 'datetime',
    ];

    public function wishlist()
    {
        return $this->belongsTo(Wishlist::class);
    }

    public function reservedByUser()
    {
        return $this->belongsTo(User::class, 'reserved_by_user_id');
    }

    public function contributions()
    {
        return $this->hasMany(GiftContribution::class);
    }

    public function isReserved(): bool
    {
        return $this->status === 'reserved';
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    public function getFormattedPriceAttribute(): ?string
    {
        if ($this->price === null) {
            return null;
        }

        return number_format($this->price, 0, ',', ' ').' '.($this->currency ?? 'Kč');
    }

    public function getResolvedImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        if (str_starts_with($this->image_path, 'http')) {
            return $this->image_path;
        }

        if (str_starts_with($this->image_path, '/images/placeholders/')) {
            return asset($this->image_path);
        }

        return asset('storage/'.$this->image_path);
    }

    public function getContributedTotalAttribute(): float
    {
        return (float) $this->contributions->sum('amount');
    }

    public function getContributionPercentageAttribute(): int
    {
        if (! $this->price || (float) $this->price <= 0) {
            return 0;
        }

        return (int) min(100, round(($this->contributed_total / (float) $this->price) * 100));
    }

    public function getRemainingAmountAttribute(): float
    {
        if (! $this->price) {
            return 0;
        }

        return max(0, (float) $this->price - $this->contributed_total);
    }
}
