<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function contacts()
    {
        $currentUser = Auth::user();
        $isAdmin = $currentUser->isAdmin();

        if ($isAdmin) {
            $users = User::where('id', '!=', $currentUser->id)
                ->select('id', 'name', 'email', 'role', 'last_login_at')
                ->orderBy('name')
                ->get();
        } else {
            $users = User::where('role', 'admin')
                ->where('id', '!=', $currentUser->id)
                ->select('id', 'name', 'email', 'role', 'last_login_at')
                ->orderBy('name')
                ->get();
        }

        return response()->json($users);
    }

    public function users()
    {
        $users = User::select('id', 'name', 'role')->orderBy('name')->get();

        return response()->json($users);
    }
}
