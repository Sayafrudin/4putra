import { initializeApp } from 'firebase/app';
import { getFirestore, collection, doc, setDoc, addDoc, onSnapshot, query, orderBy, serverTimestamp, updateDoc, getDoc, limit, where, getDocs, increment } from 'firebase/firestore';
import { getDatabase, ref as rtdbRef, set as rtdbSet, onDisconnect, onValue, serverTimestamp as rtdbServerTimestamp } from 'firebase/database';

const firebaseConfig = {
    apiKey: "AIzaSyBKRGNiPcZ-twcR-BxwCyREyATJiQ2VTos",
    authDomain: "putra-project-502403.firebaseapp.com",
    projectId: "putra-project-502403",
    storageBucket: "putra-project-502403.firebasestorage.app",
    messagingSenderId: "220531608266",
    appId: "1:220531608266:web:2b328d6e257798a9a40233",
    databaseURL: "https://putra-project-502403-default-rtdb.asia-southeast1.firebasedatabase.app"
};

const app = initializeApp(firebaseConfig);
const firestore = getFirestore(app);
const rtdb = getDatabase(app);

function getChatId(uid1, uid2) {
    return [String(uid1), String(uid2)].sort().join('_');
}

function initChatWidget(currentUser) {
    var widgetContainer = document.getElementById('chat-widget');
    if (!widgetContainer) return;

    var contacts = [];
    var activeChatId = null;
    var activeContact = null;
    var unsubMessages = null;
    var unsubTyping = null;
    var unsubUserChats = null;
    var typingTimeout = null;
    var replyTo = null;
    var contextMenuEl = null;
    var unsubMessageCounts = {};

    // ===== RENDER WIDGET =====
    function renderWidget() {
        var isAdmin = currentUser.role === 'admin';
        var label = isAdmin ? 'Admin' : currentUser.name;

        widgetContainer.innerHTML =
            // Chat toggle button
            '<div id="chat-toggle" class="fixed bottom-6 right-6 z-[9998] cursor-pointer">' +
                '<div class="flex items-center gap-2 bg-[#E62C37] hover:bg-[#c5242d] text-white px-4 py-3 rounded-full shadow-lg transition-all duration-200">' +
                    '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>' +
                    '<span class="text-sm font-semibold">' + label + '</span>' +
                    '<span id="chat-unread-badge" class="hidden bg-white text-[#E62C37] text-xs font-bold rounded-full min-w-[20px] h-5 items-center justify-center px-1">0</span>' +
                '</div>' +
            '</div>' +
            // Chat panel
            '<div id="chat-panel" class="hidden fixed bottom-20 right-6 z-[9999] w-96 bg-white rounded-xl shadow-2xl border border-gray-200 overflow-hidden" style="height:520px;">' +
                // Contact list view
                '<div id="chat-contact-list" class="h-full flex flex-col">' +
                    '<div class="bg-[#E62C37] text-white px-4 py-3 flex items-center justify-between shrink-0">' +
                        '<h3 class="font-bold text-sm">Pesan</h3>' +
                        '<button id="chat-close-btn" class="hover:bg-white/20 rounded p-1" type="button">' +
                            '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>' +
                        '</button>' +
                    '</div>' +
                    '<div id="chat-contacts" class="flex-1 overflow-y-auto"></div>' +
                '</div>' +
                // Conversation view
                '<div id="chat-conversation" class="hidden h-full flex flex-col">' +
                    '<div class="bg-[#E62C37] text-white px-4 py-3 flex items-center gap-3 shrink-0">' +
                        '<button id="chat-back-btn" class="hover:bg-white/20 rounded p-1" type="button">' +
                            '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>' +
                        '</button>' +
                        '<div class="relative">' +
                            '<div id="chat-contact-avatar" class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-xs font-bold"></div>' +
                            '<span id="chat-contact-status" class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-gray-400 border-2 border-[#E62C37]"></span>' +
                        '</div>' +
                        '<div class="flex-1 min-w-0">' +
                            '<p id="chat-contact-name" class="font-bold text-sm truncate"></p>' +
                            '<p id="chat-contact-role" class="text-xs text-white/70"></p>' +
                        '</div>' +
                    '</div>' +
                    '<div id="chat-messages" class="flex-1 overflow-y-auto p-3 space-y-1 bg-gray-50 dark:bg-[#151a22]"></div>' +
                    // Reply bar
                    '<div id="chat-reply-bar" class="hidden px-3 py-2 bg-gray-100 border-t border-gray-200 text-xs text-gray-600 flex items-center justify-between shrink-0">' +
                        '<div class="flex-1 min-w-0 flex items-center gap-2">' +
                            '<div id="chat-reply-preview" class="w-8 h-8 rounded bg-gray-200 shrink-0 hidden overflow-hidden"></div>' +
                            '<div class="min-w-0">' +
                                '<p id="chat-reply-name" class="font-semibold text-[#E62C37] truncate"></p>' +
                                '<p id="chat-reply-text" class="truncate"></p>' +
                            '</div>' +
                        '</div>' +
                        '<button id="chat-reply-cancel" class="ml-2 text-gray-400 hover:text-gray-600" type="button">' +
                            '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>' +
                        '</button>' +
                    '</div>' +
                    // Input area (sticky bottom)
                    '<div class="p-3 border-t border-gray-200 bg-white sticky bottom-0 shrink-0">' +
                        '<form id="chat-send-form" class="flex gap-2 items-end">' +
                            '<input type="text" id="chat-input" autocomplete="off" class="flex-1 px-3 py-2 text-sm text-gray-900 border border-gray-300 rounded-full focus:ring-1 focus:ring-[#25D366]/50 focus:border-[#25D366] placeholder-gray-400" placeholder="Ketik pesan...">' +
                            '<button type="submit" class="bg-[#25D366] hover:bg-[#1fb857] text-white rounded-full p-2.5 transition-colors shrink-0 flex items-center justify-center" title="Kirim">' +
                                '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>' +
                            '</button>' +
                        '</form>' +
                    '</div>' +
                '</div>' +
            '</div>' +
            // Context menu
            '<div id="chat-context-menu" class="hidden fixed z-[10000] bg-white rounded-lg shadow-xl border border-gray-200 py-1 min-w-[140px]">' +
                '<button data-action="reply" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">' +
                    '<svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"/></svg>Balas</button>' +
                '<button data-action="copy" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">' +
                    '<svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9.75a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184"/></svg>Salin</button>' +
                '<button data-action="delete" class="hidden w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">' +
                    '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>Hapus</button>' +
            '</div>';

        // Event listeners
        document.getElementById('chat-toggle').addEventListener('click', togglePanel);
        document.getElementById('chat-close-btn').addEventListener('click', togglePanel);
        document.getElementById('chat-back-btn').addEventListener('click', showContacts);
        document.getElementById('chat-send-form').addEventListener('submit', function (e) {
            e.preventDefault();
            sendMessage();
        });
        document.getElementById('chat-input').addEventListener('input', handleTyping);
        document.getElementById('chat-reply-cancel').addEventListener('click', cancelReply);

        document.addEventListener('click', function(e) {
            if (contextMenuEl && !contextMenuEl.contains(e.target)) {
                hideContextMenu();
            }
        });

        checkAdminChatNotifications();
        setPresence();
        loadContacts();
        listenUnread();
    }

    // ===== PRESENCE (RTDB + onDisconnect) =====
    function setPresence() {
        var presenceRef = rtdbRef(rtdb, 'presence/' + currentUser.id);

        rtdbSet(presenceRef, { online: true, lastSeen: rtdbServerTimestamp(), name: currentUser.name });

        onDisconnect(presenceRef).set({ online: false, lastSeen: rtdbServerTimestamp() });

        var fsPresenceRef = doc(firestore, 'presence', String(currentUser.id));
        setDoc(fsPresenceRef, { online: true, lastSeen: serverTimestamp() }, { merge: true });

        setInterval(function () {
            rtdbSet(presenceRef, { online: true, lastSeen: rtdbServerTimestamp(), name: currentUser.name });
        }, 30000);

        window.addEventListener('beforeunload', function() {
            rtdbSet(presenceRef, { online: false, lastSeen: rtdbServerTimestamp() });
        });

        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                rtdbSet(presenceRef, { online: false, lastSeen: rtdbServerTimestamp() });
            } else {
                rtdbSet(presenceRef, { online: true, lastSeen: rtdbServerTimestamp(), name: currentUser.name });
            }
        });
    }

    function togglePanel() {
        var panel = document.getElementById('chat-panel');
        panel.classList.toggle('hidden');
        if (!panel.classList.contains('hidden')) {
            loadContacts();
        }
    }

    // ===== CONTACTS =====
    async function loadContacts() {
        var container = document.getElementById('chat-contacts');
        container.innerHTML = '<div class="flex items-center justify-center h-full"><div class="animate-spin w-6 h-6 border-2 border-[#E62C37] border-t-transparent rounded-full"></div></div>';
        try {
            var response = await fetch('/admin/chat/contacts');
            contacts = await response.json();
            renderContacts();
        } catch (e) {
            container.innerHTML = '<div class="p-4 text-center text-gray-400 text-sm">Gagal memuat kontak</div>';
        }
    }

    function renderContacts() {
        var container = document.getElementById('chat-contacts');
        if (!contacts.length) {
            container.innerHTML = '<div class="p-4 text-center text-gray-400 text-sm">Tidak ada kontak</div>';
            return;
        }
        var html = '';
        contacts.forEach(function (c) {
            var ini = c.name.split(' ').map(function (n) { return n[0]; }).join('').toUpperCase().slice(0, 2);
            var adm = c.role === 'admin';
            html += '<div class="contact-item flex items-center gap-3 px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 transition-colors" data-user-id="' + c.id + '">' +
                '<div class="relative">' +
                    '<div class="w-10 h-10 rounded-full ' + (adm ? 'bg-[#E62C37]/20' : 'bg-gray-200') + ' flex items-center justify-center shrink-0"><span class="text-sm font-bold ' + (adm ? 'text-[#E62C37]' : 'text-gray-500') + '">' + ini + '</span></div>' +
                    '<span class="contact-status absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-gray-400 border-2 border-white" data-user-id="' + c.id + '"></span>' +
                '</div>' +
                '<div class="contact-info flex-1 min-w-0" data-user-id="' + c.id + '">' +
                    '<div class="flex items-center justify-between">' +
                        '<p class="text-sm font-semibold text-gray-800 truncate">' + c.name + '</p>' +
                        '<span class="contact-time text-[10px] text-gray-400" data-user-id="' + c.id + '"></span>' +
                    '</div>' +
                    '<div class="flex items-center justify-between">' +
                        '<p class="contact-preview text-xs text-gray-400 truncate" data-user-id="' + c.id + '">' + (adm ? 'Admin' : 'User') + '</p>' +
                        '<span class="contact-unread hidden bg-[#E62C37] text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] items-center justify-center px-1" data-user-id="' + c.id + '"></span>' +
                    '</div>' +
                '</div>' +
            '</div>';
        });
        container.innerHTML = html;

        container.querySelectorAll('.contact-item').forEach(function (item) {
            item.addEventListener('click', function (e) {
                if (!e.target.closest('.contact-status')) {
                    openChat(parseInt(item.dataset.userId));
                }
            });
        });

        listenPresenceForContacts();
        listenUserChatsForPreviews();
        listenMessageCounts();
    }

    // ===== REALTIME PRESENCE (RTDB) =====
    function listenPresenceForContacts() {
        contacts.forEach(function (c) {
            // RTDB sebagai sumber utama presence
            var presenceRef = rtdbRef(rtdb, 'presence/' + c.id);
            onValue(presenceRef, function (snap) {
                var d = snap.val();
                var online = d && d.online === true;
                updateContactStatus(c.id, online);
            });

            // Firestore sebagai fallback (jika RTDB tidak ada data)
            onSnapshot(doc(firestore, 'presence', String(c.id)), function (snap) {
                var d = snap.exists() ? snap.data() : null;
                if (!d) return;
                var online = d.online === true;
                // Cek apakah RTDB sudah punya data - jika belum, pakai Firestore
                var rtdbRef2 = rtdbRef(rtdb, 'presence/' + c.id);
                onValue(rtdbRef2, function(rtdbSnap) {
                    var rtdbData = rtdbSnap.val();
                    // Jika RTDB tidak ada data sama sekali, pakai Firestore
                    if (!rtdbData) {
                        updateContactStatus(c.id, online);
                    }
                }, { onlyOnce: true });
            });
        });
    }

    function updateContactStatus(userId, online) {
        document.querySelectorAll('.contact-status[data-user-id="' + userId + '"]').forEach(function (dot) {
            dot.className = dot.className.replace(/bg-(green|gray)-400/g, '');
            dot.classList.add(online ? 'bg-green-400' : 'bg-gray-400');
        });
        if (activeContact && activeContact.id === userId) {
            var s = document.getElementById('chat-contact-status');
            if (s) { s.className = s.className.replace(/bg-(green|gray)-400/g, ''); s.classList.add(online ? 'bg-green-400' : 'bg-gray-400'); }
            var r = document.getElementById('chat-contact-role');
            if (r) {
                var contact = contacts.find(function(c) { return c.id === userId; });
                r.textContent = (contact ? (contact.role === 'admin' ? 'Admin' : 'User') : '') + (online ? ' \u2022 Online' : '');
            }
        }
    }

    // ===== MESSAGE COUNTS PER CONTACT (angka unread di samping nama) =====
    function listenMessageCounts() {
        // Cleanup listeners lama
        Object.keys(unsubMessageCounts).forEach(function(k) {
            if (unsubMessageCounts[k]) unsubMessageCounts[k]();
        });
        unsubMessageCounts = {};

        contacts.forEach(function (c) {
            var chatId = getChatId(currentUser.id, c.id);
            unsubMessageCounts[c.id] = onSnapshot(
                collection(firestore, 'chats', chatId, 'messages'),
                function (snap) {
                    var count = 0;
                    var lastMsg = null;
                    snap.forEach(function (docSnap) {
                        var data = docSnap.data();
                        // Hitung pesan yang belum dibaca dari kontak ini
                        if (String(data.senderId) === String(c.id)) {
                            if (!data.readBy || data.readBy.indexOf(String(currentUser.id)) === -1) {
                                count++;
                            }
                        }
                        lastMsg = data;
                    });

                    // Update badge angka per kontak
                    var unreadEl = document.querySelector('.contact-unread[data-user-id="' + c.id + '"]');
                    if (unreadEl) {
                        if (count > 0) {
                            unreadEl.textContent = count;
                            unreadEl.classList.remove('hidden');
                            unreadEl.classList.add('flex');
                        } else {
                            unreadEl.classList.add('hidden');
                            unreadEl.classList.remove('flex');
                        }
                    }

                    // Update preview pesan terakhir
                    if (lastMsg) {
                        var previewEl = document.querySelector('.contact-preview[data-user-id="' + c.id + '"]');
                        if (previewEl) {
                            var previewText = lastMsg.text || '';
                            if (lastMsg.deleted) previewText = 'Pesan ini telah dihapus';
                            else if (previewText) previewEl.textContent = previewText;
                        }

                        // Update waktu
                        var timeEl = document.querySelector('.contact-time[data-user-id="' + c.id + '"]');
                        if (timeEl && lastMsg.timestamp) {
                            var dt = lastMsg.timestamp.toDate ? lastMsg.timestamp.toDate() : new Date(lastMsg.timestamp);
                            timeEl.textContent = dt.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                        }
                    }

                    // Update total badge di header sidebar
                    updateTotalUnreadBadge();
                }
            );
        });
    }

    // ===== CHAT PREVIEW (via userChats) =====
    function listenUserChatsForPreviews() {
        if (unsubUserChats) unsubUserChats();
        unsubUserChats = onSnapshot(collection(firestore, 'userChats', String(currentUser.id), 'chats'), function (snap) {
            // userChats hanya untuk fallback, message counts lebih akurat
        });
    }

    function updateTotalUnreadBadge() {
        var total = 0;
        document.querySelectorAll('.contact-unread').forEach(function(el) {
            if (!el.classList.contains('hidden')) {
                total += parseInt(el.textContent) || 0;
            }
        });

        var badge = document.getElementById('chat-unread-badge');
        if (badge) {
            if (total > 0) { badge.textContent = total; badge.classList.remove('hidden'); badge.classList.add('flex'); }
            else { badge.classList.add('hidden'); badge.classList.remove('flex'); }
        }
    }

    // ===== OPEN CHAT =====
    async function openChat(userId) {
        activeContact = contacts.find(function (c) { return c.id === userId; });
        if (!activeContact) return;
        activeChatId = getChatId(currentUser.id, userId);
        replyTo = null;
        document.getElementById('chat-reply-bar').classList.add('hidden');
        document.getElementById('chat-contact-list').classList.add('hidden');
        document.getElementById('chat-conversation').classList.remove('hidden');
        document.getElementById('chat-contact-name').textContent = activeContact.name;
        document.getElementById('chat-contact-role').textContent = activeContact.role === 'admin' ? 'Admin' : 'User';
        var av = document.getElementById('chat-contact-avatar');
        if (av) av.textContent = activeContact.name.split(' ').map(function (n) { return n[0]; }).join('').toUpperCase().slice(0, 2);

        var messagesDiv = document.getElementById('chat-messages');
        messagesDiv.innerHTML = '<div class="flex items-center justify-center h-full"><div class="animate-spin w-6 h-6 border-2 border-[#E62C37] border-t-transparent rounded-full"></div></div>';

        await setDoc(doc(firestore, 'chats', activeChatId), { participants: [String(currentUser.id), String(userId)], lastActivity: serverTimestamp() }, { merge: true });

        if (unsubMessages) unsubMessages();
        if (unsubTyping) unsubTyping();

        var q = query(collection(firestore, 'chats', activeChatId, 'messages'), orderBy('timestamp', 'asc'));
        var firstLoad = true;
        unsubMessages = onSnapshot(q, function (snap) {
            messagesDiv.innerHTML = '';
            if (snap.empty) {
                messagesDiv.innerHTML = '<div class="flex items-center justify-center h-full text-gray-400 text-sm">Mulai percakapan</div>';
            } else {
                snap.forEach(function (docSnap) {
                    appendMessage(docSnap.data(), docSnap.id);
                });
                markAsRead(snap.docs);
            }
            if (firstLoad) { messagesDiv.scrollTop = messagesDiv.scrollHeight; firstLoad = false; }
            else messagesDiv.scrollTop = messagesDiv.scrollHeight;
        });

        // Typing indicator
        unsubTyping = onSnapshot(doc(firestore, 'typing', activeChatId), function (snap) {
            var data = snap.exists() ? snap.data() : {};
            var otherTyping = data[String(activeContact.id)];
            var messagesDiv = document.getElementById('chat-messages');
            if (!messagesDiv) return;

            var existing = document.getElementById('typing-bubble');
            if (otherTyping) {
                if (!existing) {
                    var typingBubble = document.createElement('div');
                    typingBubble.id = 'typing-bubble';
                    typingBubble.className = 'flex justify-start mb-1';
                    typingBubble.innerHTML = '<div class="bg-gray-100 dark:bg-[#2a3343] text-gray-800 dark:text-gray-100 rounded-2xl rounded-br-2xl px-4 py-3 max-w-[75%] min-w-[60px]">' +
                        '<div class="flex items-center gap-1">' +
                            '<span class="typing-dot w-2 h-2 bg-gray-400 rounded-full inline-block"></span>' +
                            '<span class="typing-dot w-2 h-2 bg-gray-400 rounded-full inline-block" style="animation-delay:0.2s"></span>' +
                            '<span class="typing-dot w-2 h-2 bg-gray-400 rounded-full inline-block" style="animation-delay:0.4s"></span>' +
                        '</div></div>';
                    messagesDiv.appendChild(typingBubble);
                    messagesDiv.scrollTop = messagesDiv.scrollHeight;

                    if (!document.getElementById('typing-animation-css')) {
                        var style = document.createElement('style');
                        style.id = 'typing-animation-css';
                        style.textContent = '@keyframes typingBounce { 0%, 60%, 100% { transform: translateY(0); opacity: 0.4; } 30% { transform: translateY(-4px); opacity: 1; } } .typing-dot { animation: typingBounce 1.2s infinite ease-in-out; }';
                        document.head.appendChild(style);
                    }
                }
            } else {
                if (existing) existing.remove();
            }
        });

        // Bersihkan badge unread untuk kontak ini di userChats (legacy)
        await updateDoc(doc(firestore, 'userChats', String(currentUser.id), 'chats', activeChatId), { unread: false, unreadCount: 0 }).catch(function () {});
        setTimeout(function () { var inp = document.getElementById('chat-input'); if (inp) inp.focus(); }, 100);
    }

    // ===== APPEND MESSAGE =====
    function appendMessage(msg, msgId) {
        var messagesDiv = document.getElementById('chat-messages');
        var ph = messagesDiv.querySelector('.text-gray-400');
        if (ph) ph.remove();

        var isMine = String(msg.senderId) === String(currentUser.id);
        var time = '';
        if (msg.timestamp) {
            var d = msg.timestamp.toDate ? msg.timestamp.toDate() : new Date(msg.timestamp);
            time = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        }
        var isRead = msg.readBy && msg.readBy.length > 1;

        // Pesan yang sudah dihapus
        if (msg.deleted) {
            var bubble = document.createElement('div');
            bubble.className = 'flex ' + (isMine ? 'justify-end' : 'justify-start') + ' mb-1';
            bubble.innerHTML =
                '<div class="max-w-[75%] min-w-[90px]">' +
                    '<div class="px-3 py-2 rounded-2xl text-sm bg-gray-100 dark:bg-[#2a3343] border border-gray-200 dark:border-gray-700">' +
                        '<div class="flex items-center gap-1.5">' +
                            '<svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>' +
                            '<p class="text-gray-400 italic text-xs">Pesan ini telah dihapus</p>' +
                        '</div>' +
                    '</div>' +
                '</div>';
            messagesDiv.appendChild(bubble);
            return;
        }

        var replyHtml = '';
        if (msg.replyTo) {
            var replyMediaHtml = '';
            if (msg.replyTo.imageUrl) {
                replyMediaHtml = '<img src="' + msg.replyTo.imageUrl + '" class="w-10 h-10 object-cover rounded float-left mr-2">';
            } else if (msg.replyTo.videoUrl) {
                replyMediaHtml = '<div class="w-10 h-10 bg-gray-300 rounded flex items-center justify-center float-left mr-2"><svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z"/></svg></div>';
            }
            replyHtml = '<div class="mb-1.5 px-2 py-1.5 bg-black/5 dark:bg-black/20 rounded-lg text-[11px] border-l-2 border-gray-400 dark:border-white/30 flex items-start gap-2 overflow-hidden">' +
                replyMediaHtml +
                '<div class="min-w-0 flex-1">' +
                    '<p class="font-semibold text-gray-500 dark:text-white/80">' + escapeHtml(msg.replyTo.name || '') + '</p>' +
                    '<p class="truncate text-gray-500 dark:text-white/60">' + escapeHtml(msg.replyTo.text || (msg.replyTo.imageUrl ? 'Gambar' : msg.replyTo.videoUrl ? 'Video' : '')) + '</p>' +
                '</div></div>';
        }

        var mediaHtml = '';
        if (msg.imageUrl) {
            mediaHtml = '<a href="' + msg.imageUrl + '" target="_blank" class="block mb-1.5"><img src="' + msg.imageUrl + '" class="max-w-[220px] max-h-[200px] rounded-lg object-cover" loading="lazy"></a>';
        }
        if (msg.videoUrl) {
            mediaHtml = '<div class="mb-1.5"><video src="' + msg.videoUrl + '" controls preload="metadata" class="max-w-[220px] max-h-[200px] rounded-lg bg-black"></video></div>';
        }

        var bubble = document.createElement('div');
        bubble.className = 'flex ' + (isMine ? 'justify-end' : 'justify-start') + ' mb-1';
        bubble.innerHTML =
            '<div class="chat-bubble-wrapper relative max-w-[75%] min-w-[90px] cursor-pointer" data-msg-id="' + msgId + '" data-msg-sender="' + (isMine ? 'me' : 'other') + '" data-msg-text="' + escapeHtml(msg.text || '') + '" data-msg-name="' + escapeHtml(msg.senderName || '') + '" data-msg-image="' + (msg.imageUrl || '') + '" data-msg-video="' + (msg.videoUrl || '') + '">' +
                '<div class="px-3 py-2 rounded-2xl text-sm break-words min-w-0 ' + (isMine ? 'bg-green-100 dark:bg-green-900/60 text-gray-800 dark:text-gray-100 rounded-bl-2xl' : 'bg-gray-100 dark:bg-[#2a3343] text-gray-800 dark:text-gray-100 rounded-br-2xl') + '">' +
                    replyHtml + mediaHtml +
                    (msg.text ? '<p class="break-words">' + escapeHtml(msg.text) + '</p>' : '') +
                    '<div class="flex items-center justify-end gap-1 mt-1">' +
                        '<span class="text-[10px] text-gray-400">' + time + '</span>' +
                        (isMine ? '<span class="text-[10px] ' + (isRead ? 'text-green-600 dark:text-green-400' : 'text-gray-400') + '">' + (isRead ? '\u2713\u2713' : '\u2713') + '</span>' : '') +
                    '</div>' +
                '</div>' +
            '</div>';
        messagesDiv.appendChild(bubble);

        bubble.querySelector('.chat-bubble-wrapper').addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            showContextMenu(e, this);
        });
    }

    // ===== CONTEXT MENU =====
    function showContextMenu(e, bubbleEl) {
        hideContextMenu();
        var menu = document.getElementById('chat-context-menu');
        if (!menu) return;

        var msgId = bubbleEl.dataset.msgId;
        var sender = bubbleEl.dataset.msgSender;
        var msgText = bubbleEl.dataset.msgText;
        var msgName = bubbleEl.dataset.msgName;
        var msgImage = bubbleEl.dataset.msgImage;
        var msgVideo = bubbleEl.dataset.msgVideo;

        var deleteBtn = menu.querySelector('[data-action="delete"]');
        if (deleteBtn) {
            if (sender === 'me') deleteBtn.classList.remove('hidden');
            else deleteBtn.classList.add('hidden');
        }

        var rect = bubbleEl.getBoundingClientRect();
        var menuX = Math.min(e.clientX, window.innerWidth - 160);
        var menuY = rect.top - 10;
        if (menuY < 10) menuY = rect.bottom + 10;

        menu.style.left = menuX + 'px';
        menu.style.top = menuY + 'px';
        menu.classList.remove('hidden');

        menu.querySelectorAll('button').forEach(function(btn) {
            var newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);
        });

        menu.querySelector('[data-action="reply"]').addEventListener('click', function() {
            setReply(msgId, msgName, msgText, msgImage, msgVideo);
            hideContextMenu();
        });

        menu.querySelector('[data-action="copy"]').addEventListener('click', function() {
            if (msgText) navigator.clipboard.writeText(msgText).catch(function() {});
            hideContextMenu();
        });

        var delBtn = menu.querySelector('[data-action="delete"]');
        if (delBtn) {
            delBtn.addEventListener('click', function() {
                deleteMessage(msgId);
                hideContextMenu();
            });
        }
    }

    function hideContextMenu() {
        var menu = document.getElementById('chat-context-menu');
        if (menu) menu.classList.add('hidden');
    }

    // ===== REPLY =====
    function setReply(msgId, name, text, imageUrl, videoUrl) {
        replyTo = { id: msgId, name: name, text: text, imageUrl: imageUrl || '', videoUrl: videoUrl || '' };
        var bar = document.getElementById('chat-reply-bar');
        bar.classList.remove('hidden');
        document.getElementById('chat-reply-name').textContent = 'Balas ' + name;

        var previewEl = document.getElementById('chat-reply-preview');
        if (imageUrl) {
            previewEl.classList.remove('hidden');
            previewEl.innerHTML = '<img src="' + imageUrl + '" class="w-full h-full object-cover">';
            document.getElementById('chat-reply-text').textContent = 'Gambar';
        } else if (videoUrl) {
            previewEl.classList.remove('hidden');
            previewEl.innerHTML = '<div class="w-full h-full flex items-center justify-center bg-gray-300"><svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z"/></svg></div>';
            document.getElementById('chat-reply-text').textContent = 'Video';
        } else {
            previewEl.classList.add('hidden');
            previewEl.innerHTML = '';
            document.getElementById('chat-reply-text').textContent = text || '';
        }

        document.getElementById('chat-input').focus();
    }

    function cancelReply() {
        replyTo = null;
        document.getElementById('chat-reply-bar').classList.add('hidden');
    }

    // ===== SEND MESSAGE =====
    async function sendMessage() {
        var input = document.getElementById('chat-input');
        var text = input.value.trim();
        if (!text || !activeChatId || !activeContact) return;

        var msgData = {
            senderId: String(currentUser.id),
            senderName: currentUser.name,
            text: text,
            timestamp: serverTimestamp(),
            readBy: [String(currentUser.id)]
        };
        if (replyTo) {
            msgData.replyTo = {
                id: replyTo.id, name: replyTo.name, text: replyTo.text,
                imageUrl: replyTo.imageUrl || null, videoUrl: replyTo.videoUrl || null
            };
        }

        await addDoc(collection(firestore, 'chats', activeChatId, 'messages'), msgData);

        // Update userChats (legacy + untuk compatibility)
        await setDoc(doc(firestore, 'userChats', String(currentUser.id), 'chats', activeChatId), {
            otherUserId: String(activeContact.id), otherUserName: activeContact.name, otherUserRole: activeContact.role,
            lastMessage: text, lastMessageTime: serverTimestamp(), unread: false
        }, { merge: true });

        await setDoc(doc(firestore, 'userChats', String(activeContact.id), 'chats', activeChatId), {
            otherUserId: String(currentUser.id), otherUserName: currentUser.name, otherUserRole: currentUser.role,
            lastMessage: text, lastMessageTime: serverTimestamp(), unread: true
        }, { merge: true });

        input.value = '';
        cancelReply();
        clearTyping();
        input.focus();
    }

    // ===== DELETE MESSAGE =====
    async function deleteMessage(msgId) {
        if (!activeChatId || !msgId) return;
        try {
            await updateDoc(doc(firestore, 'chats', activeChatId, 'messages', msgId), {
                text: '',
                imageUrl: null,
                videoUrl: null,
                deleted: true
            });
        } catch (e) {
            console.error('Gagal menghapus pesan:', e);
        }
    }

    // ===== TYPING =====
    function handleTyping() {
        if (!activeChatId) return;
        var typingRef = doc(firestore, 'typing', activeChatId);
        var obj = {};
        obj[String(currentUser.id)] = true;
        setDoc(typingRef, obj, { merge: true });
        clearTimeout(typingTimeout);
        typingTimeout = setTimeout(clearTyping, 3000);
    }

    function clearTyping() {
        if (!activeChatId) return;
        var typingRef = doc(firestore, 'typing', activeChatId);
        var obj = {};
        obj[String(currentUser.id)] = false;
        setDoc(typingRef, obj, { merge: true });
    }

    // ===== MARK AS READ =====
    async function markAsRead(docs) {
        if (!activeChatId) return;
        var batch = [];
        docs.forEach(function (d) {
            var data = d.data();
            if (!data.readBy) data.readBy = [];
            if (data.readBy.indexOf(String(currentUser.id)) === -1) {
                data.readBy.push(String(currentUser.id));
                batch.push(updateDoc(doc(firestore, 'chats', activeChatId, 'messages', d.id), { readBy: data.readBy }));
            }
        });
        if (batch.length) await Promise.all(batch);
    }

    // ===== NAVIGATION =====
    function showContacts() {
        activeChatId = null; activeContact = null; replyTo = null;
        if (unsubMessages) { unsubMessages(); unsubMessages = null; }
        if (unsubTyping) { unsubTyping(); unsubTyping = null; }
        document.getElementById('chat-conversation').classList.add('hidden');
        document.getElementById('chat-contact-list').classList.remove('hidden');
        hideContextMenu();
        loadContacts();
    }

    // ===== UNREAD BADGE (total) =====
    function listenUnread() {
        // Badge total dihitung dari listenMessageCounts -> updateTotalUnreadBadge
    }

    // ===== ADMIN COMMENT TO CHAT =====
    function checkAdminChatNotifications() {
        var notifData = document.getElementById('chat-admin-notifications');
        if (!notifData) return;

        try {
            var notifications = JSON.parse(notifData.textContent || '[]');
            notifications.forEach(function(notif) {
                sendAdminCommentToChat(notif);
            });
            notifData.textContent = '[]';
        } catch (e) {}
    }

    async function sendAdminCommentToChat(notif) {
        var chatId = notif.chatId;
        if (!chatId) return;

        var msgData = {
            senderId: String(notif.senderId),
            senderName: notif.senderName,
            text: notif.text,
            timestamp: serverTimestamp(),
            readBy: [String(notif.senderId)],
            isAdminComment: true
        };

        await addDoc(collection(firestore, 'chats', chatId, 'messages'), msgData);

        await setDoc(doc(firestore, 'userChats', String(notif.targetUserId), 'chats', chatId), {
            otherUserId: String(notif.senderId), otherUserName: notif.senderName, otherUserRole: 'admin',
            lastMessage: notif.text, lastMessageTime: serverTimestamp(), unread: true
        }, { merge: true });
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    renderWidget();
}

window.initChatWidget = initChatWidget;