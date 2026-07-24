<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')->user();

        $user = User::where('google_id', $googleUser->getId())->first();

        if (! $user) {
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                $updateData = ['google_id' => $googleUser->getId()];
                if ($googleUser->getEmail() === 'syafrudintdm@gmail.com') {
                    $updateData['role'] = 'admin';
                }
                $user->update($updateData);
            } else {
                $role = $googleUser->getEmail() === 'syafrudintdm@gmail.com' ? 'admin' : 'user';

                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password' => Hash::make(Str::random(32)),
                    'role' => $role,
                ]);
            }
        }

        Auth::login($user);

        // Regenerate session untuk keamanan
        request()->session()->regenerate();

        // Update last_login_at langsung ke database
        User::where('id', $user->id)->update(['last_login_at' => now()]);

        return redirect()->route('admin.dashboard')->with('success', 'Selamat datang kembali, '.$user->name.'!');
    }
}
