<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GiftItem;
use App\Models\User;
use App\Models\Wishlist;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'users' => User::count(),
            'admins' => User::where('is_admin', true)->count(),
            'wishlists' => Wishlist::count(),
            'gift_items' => GiftItem::count(),
            'reserved_items' => GiftItem::where('status', 'reserved')->count(),
        ];

        $recentUsers = User::withCount('wishlists')->latest()->take(5)->get();

        return view('admin.dashboard', compact('stats', 'recentUsers'));
    }
}
