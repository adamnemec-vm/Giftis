<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftContribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'gift_item_id',
        'contributor_name',
        'contributor_user_id',
        'amount',
        'contribution_token',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function giftItem()
    {
        return $this->belongsTo(GiftItem::class);
    }

    public function contributor()
    {
        return $this->belongsTo(User::class, 'contributor_user_id');
    }
}
