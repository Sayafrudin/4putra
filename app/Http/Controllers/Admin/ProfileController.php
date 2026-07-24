<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    use LogsActivity;

    public function edit()
    {
        $user = Auth::user();
        $isGoogleUser = ! empty($user->google_id);

        return view('admin.profile.edit', [
            'user' => $user,
            'isGoogleUser' => $isGoogleUser,
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $isGoogleUser = ! empty($user->google_id);

        if ($isGoogleUser) {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
            ]);

            $user->name = $validated['name'];
            $this->logActivity($request, 'update', 'Mengubah nama profil menjadi: '.$validated['name'], 'Profil');
        } else {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
                'password' => ['nullable', 'confirmed', Password::min(8)],
            ]);

            $changes = [];
            if ($user->name !== $validated['name']) {
                $changes[] = 'nama';
            }
            if ($user->email !== $validated['email']) {
                $changes[] = 'email';
            }
            if (! empty($validated['password'])) {
                $changes[] = 'password';
            }

            $user->name = $validated['name'];
            $user->email = $validated['email'];

            if (! empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $this->logActivity($request, 'update', 'Mengubah profil ('.implode(', ', $changes).')', 'Profil');
        }

        $user->save();

        return redirect()->route('admin.profile.edit')->with('success', 'Profil berhasil diperbarui!');
    }
}
