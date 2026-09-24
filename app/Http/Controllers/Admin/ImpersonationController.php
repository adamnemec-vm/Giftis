<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function start(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'Nemůžete se přihlásit sami za sebe.');
        }

        if ($user->is_admin) {
            return redirect()->back()->with('error', 'Nelze se přihlásit za jiného administrátora.');
        }

        session(['impersonator_id' => auth()->id()]);

        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', "Jste přihlášeni jako {$user->name}. Pro návrat použijte tlačítko v horní liště.");
    }

    public function stop()
    {
        $impersonatorId = session('impersonator_id');

        if (! $impersonatorId) {
            return redirect()->route('dashboard');
        }

        $admin = User::find($impersonatorId);
        session()->forget('impersonator_id');

        if (! $admin || ! $admin->is_admin) {
            Auth::logout();

            return redirect()->route('login');
        }

        Auth::login($admin);

        return redirect()->route('admin.users.index')->with('success', 'Vrátili jste se do svého administrátorského účtu.');
    }
}
