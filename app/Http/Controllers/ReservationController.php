<?php

namespace App\Http\Controllers;

class ReservationController extends Controller
{
    public function index()
    {
        $reservations = auth()->user()->reservations()
            ->with('wishlist.user')
            ->latest('reserved_at')
            ->get();

        $contributions = auth()->user()->contributions()
            ->with('giftItem.wishlist.user')
            ->latest()
            ->get();

        return view('reservations.index', compact('reservations', 'contributions'));
    }
}
