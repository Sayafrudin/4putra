@props(['user'])

{{-- Data komentar admin yang perlu dikirim ke chat Firebase --}}
@php
    $chatNotifications = [];
    if ($user->isAdmin()) {
        $pendingNotifications = \App\Models\ActivityLog::where('user_id', '!=', $user->id)
            ->whereNotNull('metadata')
            ->latest()
            ->take(10)
            ->get()
            ->filter(function ($notif) {
                return isset($notif->metadata['chat_notification']);
            });

        foreach ($pendingNotifications as $notif) {
            if (isset($notif->metadata['chat_notification'])) {
                $chatNotifications[] = $notif->metadata['chat_notification'];
                $meta = $notif->metadata;
                unset($meta['chat_notification']);
                $notif->update(['metadata' => $meta]);
            }
        }
    }
@endphp

<div id="chat-widget"></div>
<script id="chat-admin-notifications" type="application/json">{!! json_encode($chatNotifications) !!}</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.initChatWidget) {
            window.initChatWidget({
                id: {{ $user->id }},
                name: '{{ addslashes($user->name) }}',
                email: '{{ addslashes($user->email) }}',
                role: '{{ $user->role }}'
            });
        }
    });
</script>