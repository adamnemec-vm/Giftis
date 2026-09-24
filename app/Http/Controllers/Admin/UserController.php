<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->trim()->toString();

        $users = User::withCount('wishlists')
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'search'));
    }

    public function show(User $user)
    {
        $user->load(['wishlists.items', 'reservations.wishlist.user']);

        return view('admin.users.show', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        if ($user->id === auth()->id() && ! $request->boolean('is_admin') && User::where('is_admin', true)->count() <= 1) {
            return redirect()->back()->with('error', 'Nemůžete si odebrat administrátorská práva – jste jediný administrátor.');
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_admin' => $request->boolean('is_admin'),
        ]);

        return redirect()->route('admin.users.show', $user)->with('success', 'Uživatel byl upraven.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'Nemůžete smazat svůj vlastní účet.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Uživatel a všechny jeho seznamy byly smazány.');
    }

    public function resetPassword(User $user)
    {
        $newPassword = Str::password(12);

        $user->update(['password' => $newPassword]);

        DB::table('sessions')->where('user_id', $user->id)->delete();

        return redirect()->route('admin.users.show', $user)
            ->with('generated_password', $newPassword)
            ->with('success', 'Heslo bylo resetováno. Předejte nové heslo uživateli bezpečnou cestou – tady se zobrazí jen jednou.');
    }

    public function verifyEmail(User $user)
    {
        $user->forceFill(['email_verified_at' => now()])->save();

        return redirect()->route('admin.users.show', $user)->with('success', 'E-mail byl ručně označen jako ověřený.');
    }
}
