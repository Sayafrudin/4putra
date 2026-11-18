<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\AdminMenus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminUserController extends Controller
{
    use LogsActivity;

    public function index()
    {
        // Semua akun dalam satu daftar (kolom "Login Via" menandai yang Google-link).
        $users = Cache::remember('admin.users', 120, function () {
            return User::orderBy('created_at', 'desc')->get();
        });

        return view('admin.users.index', [
            'users' => $users,
            'menuList' => AdminMenus::listSemua(),
        ]);
    }

    public function create()
    {
        return view('admin.users.create', [
            'menuKeys' => AdminMenus::keys(),
            'menuList' => AdminMenus::listSemua(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_.-]+$/', 'unique:users'],
            // Email opsional: kosong = login username+password; diisi = bisa "Masuk dengan Google".
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            // Password wajib bila email kosong (satu-satunya jalur login).
            'password' => ['required_without:email', 'nullable', 'confirmed', Password::min(8)],
            'role' => ['required', 'in:admin,user'],
            // Hak akses menu: role admin selalu penuh; role user = daftar menu 'semua'.
            'menu_access' => ['nullable', 'array'],
            'menu_access.*' => ['string', Rule::in(AdminMenus::keys())],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password'] ?? Str::random(32)),
            'role' => $validated['role'],
            'menu_access' => $this->menuAccessTersimpan($validated),
        ]);

        $this->logDataChange(
            $request, 'create',
            'Menambahkan akun user: '.$validated['name'].' ('.$validated['email'].')',
            'Manajemen Akun',
            null,
            ['name' => $validated['name'], 'username' => $validated['username'], 'email' => $validated['email'], 'role' => $validated['role']]
        );

        Cache::forget('admin.users');

        if ($request->expectsJson()) {
            return response()->json(['status' => 'OK', 'message' => 'Akun berhasil ditambahkan!']);
        }

        return redirect()->route('admin.users.index')->with('success', 'Akun berhasil ditambahkan!');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', [
            'user' => $user,
            'menuKeys' => AdminMenus::keys(),
            'menuList' => AdminMenus::listSemua(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_.-]+$/', 'unique:users,username,'.$user->id],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            // Edit: password tetap opsional (akun lama selalu punya hash) — beda dengan create.
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'role' => ['required', 'in:admin,user'],
            'menu_access' => ['nullable', 'array'],
            'menu_access.*' => ['string', Rule::in(AdminMenus::keys())],
        ]);

        $oldValues = $user->getOriginal();

        $user->name = $validated['name'];
        $user->username = $validated['username'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];
        $user->menu_access = $this->menuAccessTersimpan($validated);

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        $this->logDataChange(
            $request, 'update',
            'Memperbarui akun user: '.$user->name,
            'Manajemen Akun',
            $oldValues,
            $user->fresh()->getAttributes()
        );

        Cache::forget('admin.users');

        if ($request->expectsJson()) {
            return response()->json(['status' => 'OK', 'message' => 'Akun berhasil diperbarui!']);
        }

        return redirect()->route('admin.users.index')->with('success', 'Akun berhasil diperbarui!');
    }

    /**
     * Normalisasi hak akses menu dari payload form:
     * role admin → selalu NULL (akses penuh, UI mengunci semua checkbox);
     * role user → daftar kunci menu 'akses: semua' saja (array kosong = hanya dashboard).
     */
    private function menuAccessTersimpan(array $validated): ?array
    {
        $role = $validated['role'] ?? 'user';

        if ($role === 'admin') {
            return null;
        }

        $keysValid = array_column(AdminMenus::listForRole($role), 'key');

        return array_values(array_intersect($keysValid, $validated['menu_access'] ?? []));
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id == auth()->id()) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'ERROR', 'message' => 'Tidak bisa menghapus akun sendiri!'], 422);
            }

            return redirect()->route('admin.users.index')->with('error', 'Tidak bisa menghapus akun sendiri!');
        }

        $userName = $user->name;
        $oldValues = $user->getOriginal();

        $this->logDataChange(
            $request, 'delete',
            'Menghapus akun user: '.$userName,
            'Manajemen Akun',
            $oldValues,
            null
        );

        $user->delete();

        Cache::forget('admin.users');

        if ($request->expectsJson()) {
            return response()->json(['status' => 'OK', 'message' => 'Akun berhasil dihapus!']);
        }

        return redirect()->route('admin.users.index')->with('success', 'Akun berhasil dihapus!');
    }

    public function activity(User $user)
    {
        $activities = $user->activityLogs()->latest()->get();

        return view('admin.users.activity', compact('user', 'activities'));
    }

    public function commentActivity(Request $request, $activityId)
    {
        $request->validate([
            'admin_comment' => ['required', 'string', 'max:1000'],
        ]);

        // Pastikan activityId adalah string untuk menghindari overflow integer
        $activityId = (string) $activityId;

        $activity = ActivityLog::findOrFail($activityId);
        $activity->update(['admin_comment' => $request->admin_comment]);

        $this->logActivity($request, 'comment', 'Memberikan komentar pada aktivitas #'.$activityId, 'Manajemen Akun');

        // Kirim komentar sebagai pesan chat ke user terkait via Firebase
        $this->sendCommentToChat($request, $activity);

        return redirect()->back()->with('success', 'Komentar berhasil ditambahkan!');
    }

    /**
     * Kirim komentar admin sebagai pesan chat Firebase ke user terkait.
     */
    private function sendCommentToChat(Request $request, ActivityLog $activity): void
    {
        $adminUser = $request->user();
        $targetUserId = (string) $activity->user_id;

        $targetUser = User::find($targetUserId);
        if (! $targetUser) {
            return;
        }

        $chatId = collect([(string) $adminUser->id, $targetUserId])->sort()->join('_');
        $commentText = "💬 Komentar Admin (Aktivitas #{$activity->id}): {$request->admin_comment}";

        // Data dikirim ke API endpoint yang akan forward ke Firebase
        $payload = [
            'chatId' => $chatId,
            'senderId' => (string) $adminUser->id,
            'senderName' => $adminUser->name,
            'text' => $commentText,
            'targetUserId' => $targetUserId,
            'targetUserName' => $targetUser->name,
            'targetUserRole' => $targetUser->role,
        ];

        // Simpan data komentar di metadata activity agar bisa di-trigger dari client
        // Pastikan metadata adalah array (bukan string JSON mentah dari PDO)
        $raw = $activity->metadata;
        $metadata = is_array($raw) ? $raw : (is_string($raw) ? (json_decode($raw, true) ?? []) : []);
        $metadata['chat_notification'] = $payload;
        $activity->update(['metadata' => $metadata]);
    }
}
