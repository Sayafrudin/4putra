@extends('layouts.admin')

@section('content')
    <div class="my-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-gray-800 pb-5">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.users.index') }}" class="text-gray-400 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-white uppercase">Aktivitas User</h2>
                    <p class="text-sm text-gray-400 mt-1">Riwayat aktivitas {{ $user->name }} ({{ $user->email }})</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider {{ $user->isAdmin() ? 'bg-[#E62C37]/20 text-[#E62C37]' : 'bg-gray-700 text-gray-300' }}">
                {{ $user->role }}
            </span>
            @if($user->isOnline())
                <span class="flex items-center gap-1.5 text-xs text-green-400">
                    <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                    Online
                </span>
            @else
                <span class="flex items-center gap-1.5 text-xs text-gray-500">
                    <span class="w-2 h-2 rounded-full bg-gray-600"></span>
                    Offline
                </span>
            @endif
        </div>
    </div>

    {{-- Info User --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-[#1e2530] border border-gray-800 rounded-lg p-4">
            <p class="text-xs text-gray-400 uppercase tracking-wider">Total Aktivitas</p>
            <p class="text-2xl font-bold text-white mt-1">{{ $activities->count() }}</p>
        </div>
        <div class="bg-[#1e2530] border border-gray-800 rounded-lg p-4">
            <p class="text-xs text-gray-400 uppercase tracking-wider">Login Via</p>
            <p class="text-sm font-bold text-white mt-1">{{ $user->google_id ? 'Google' : 'Email/Password' }}</p>
        </div>
        <div class="bg-[#1e2530] border border-gray-800 rounded-lg p-4">
            <p class="text-xs text-gray-400 uppercase tracking-wider">Terakhir Login</p>
            <p class="text-sm font-bold text-white mt-1">{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Belum pernah' }}</p>
        </div>
        <div class="bg-[#1e2530] border border-gray-800 rounded-lg p-4">
            <p class="text-xs text-gray-400 uppercase tracking-wider">Terdaftar</p>
            <p class="text-sm font-bold text-white mt-1">{{ $user->created_at->format('d M Y H:i') }}</p>
        </div>
    </div>

    {{-- Timeline Aktivitas --}}
    <div class="bg-[#1e2530] border border-gray-800 rounded-lg p-6 mb-10">
        <h3 class="text-lg font-bold text-white mb-6">Riwayat Aktivitas</h3>

        @if($activities->isEmpty())
            <div class="text-center py-10">
                <svg class="w-12 h-12 mx-auto text-gray-600 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <p class="text-gray-500">Belum ada aktivitas tercatat.</p>
            </div>
        @else
            <div class="relative">
                <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-700"></div>

                <div class="space-y-6">
                    @foreach($activities as $activity)
                        <div class="relative pl-10">
                            {{-- Dot --}}
                            <div class="absolute left-2.5 top-1 w-3 h-3 rounded-full border-2
                                @if($activity->action === 'create') bg-green-500 border-green-400
                                @elseif($activity->action === 'update') bg-blue-500 border-blue-400
                                @elseif($activity->action === 'delete') bg-red-500 border-red-400
                                @elseif($activity->action === 'comment') bg-yellow-500 border-yellow-400
                                @else bg-gray-500 border-gray-400
                                @endif
                            "></div>

                            <div class="bg-[#262d3a] rounded-lg p-4 border border-gray-700">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                                                @if($activity->action === 'create') bg-green-500/20 text-green-400
                                                @elseif($activity->action === 'update') bg-blue-500/20 text-blue-400
                                                @elseif($activity->action === 'delete') bg-red-500/20 text-red-400
                                                @elseif($activity->action === 'comment') bg-yellow-500/20 text-yellow-400
                                                @else bg-gray-500/20 text-gray-400
                                                @endif
                                            ">
                                                {{ $activity->action }}
                                            </span>
                                            @if($activity->module)
                                                <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-gray-600/30 text-gray-400">{{ $activity->module }}</span>
                                            @endif
                                        </div>
                                        <p class="text-sm text-gray-300">{{ $activity->description }}</p>
                                        <div class="flex items-center gap-3 mt-2 text-xs text-gray-500">
                                            <span>{{ $activity->created_at->format('d M Y H:i:s') }}</span>
                                            @if($activity->ip_address)
                                                <span>IP: {{ $activity->ip_address }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Detail Perubahan Data --}}
                                @if($activity->metadata)
                                    {{-- Preview Gambar --}}
                                    @if(!empty($activity->image_previews))
                                        <div class="mt-3 pt-3 border-t border-gray-700">
                                            <p class="text-xs text-gray-400 mb-2 font-medium">Gambar Terkait:</p>
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($activity->image_previews as $img)
                                                    @php
                                                        $imgUrl = str_starts_with($img, 'http') ? $img : asset($img);
                                                    @endphp
                                                    <a href="{{ $imgUrl }}" target="_blank" class="block">
                                                        <img src="{{ $imgUrl }}" class="w-16 h-16 object-cover rounded-lg border border-gray-600 hover:border-[#E62C37] transition-colors" loading="lazy">
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Detail Old/New Values --}}
                                    @if(!empty($activity->old_values) || !empty($activity->new_values))
                                        <div class="mt-3 pt-3 border-t border-gray-700" x-data="{ showChanges: false }">
                                            <button @click="showChanges = !showChanges" class="flex items-center gap-1.5 text-xs text-gray-400 hover:text-white transition-colors">
                                                <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-90': showChanges }" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                                </svg>
                                                Lihat Detail Perubahan
                                            </button>
                                            <div x-show="showChanges" x-transition class="mt-3 space-y-2">
                                                @php
                                                    $oldVals = $activity->old_values ?? [];
                                                    $newVals = $activity->new_values ?? [];
                                                    $skipFields = ['password', 'updated_at', 'created_at', 'remember_token', 'google_id'];
                                                    $allKeys = array_unique(array_merge(array_keys($oldVals), array_keys($newVals)));
                                                @endphp
                                                @foreach($allKeys as $field)
                                                    @if(!in_array($field, $skipFields) && (isset($oldVals[$field]) || isset($newVals[$field])))
                                                        @php
                                                            $old = $oldVals[$field] ?? '-';
                                                            $new = $newVals[$field] ?? '-';
                                                            $changed = $old !== $new;
                                                        @endphp
                                                        @if($changed)
                                                            <div class="flex items-start gap-2 text-xs">
                                                                <span class="text-gray-500 font-medium w-24 shrink-0 capitalize">{{ str_replace('_', ' ', $field) }}:</span>
                                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                                    @if($activity->action !== 'create' && $old !== '-')
                                                                        <span class="px-1.5 py-0.5 rounded bg-red-500/20 text-red-300 line-through">{{ Str::limit((string) $old, 50) }}</span>
                                                                        <span class="text-gray-500">→</span>
                                                                    @endif
                                                                    @if($activity->action !== 'delete')
                                                                        <span class="px-1.5 py-0.5 rounded bg-green-500/20 text-green-300">{{ Str::limit((string) $new, 50) }}</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endif

                                {{-- Admin Comment --}}
                                @if($activity->admin_comment)
                                    <div class="mt-3 pt-3 border-t border-gray-700">
                                        <div class="flex items-start gap-2">
                                            <div class="w-5 h-5 rounded-full bg-[#E62C37]/20 flex items-center justify-center shrink-0 mt-0.5">
                                                <svg class="w-3 h-3 text-[#E62C37]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-xs text-[#E62C37] font-semibold">Komentar Admin:</p>
                                                <p class="text-sm text-gray-300 mt-0.5">{{ $activity->admin_comment }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    {{-- Form Comment (Admin only) --}}
                                    <div class="mt-3 pt-3 border-t border-gray-700">
                                        <form method="POST" action="{{ route('admin.activity.comment', $activity->id) }}" class="flex gap-2">
                                            @csrf
                                            <input type="text" name="admin_comment" required
                                                class="flex-1 px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white text-xs focus:ring-1 focus:ring-[#E62C37]/50 focus:border-[#E62C37]"
                                                placeholder="Tambahkan komentar...">
                                            <button type="submit"
                                                class="px-3 py-2 bg-[#E62C37] hover:bg-[#c5242d] text-white text-xs rounded-lg transition-colors">
                                                Kirim
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection