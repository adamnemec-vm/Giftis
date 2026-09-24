<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftItemComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'gift_item_id',
        'author_user_id',
        'author_name',
        'commenter_token',
        'body',
    ];

    public function giftItem()
    {
        return $this->belongsTo(GiftItem::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }
}
