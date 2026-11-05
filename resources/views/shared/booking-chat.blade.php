@extends($userType === 'customer' ? 'customer.layout.layout' : 'professional.layout.layout')

@section('title', 'Booking Chat | Yuktione')

@section('content')
@php
    $otherParticipant = $userType === 'customer'
        ? ($booking->professional->name ?? 'Professional')
        : ($booking->user->name ?? 'Customer');
    $serviceName = $booking->service_name ?? ($booking->service->name ?? null);
    $subServiceName = $booking->sub_service_name ?? ($booking->subservice->name ?? null);
@endphp

@if($userType === 'professional')
<div class="chat-page booking-chat-page mx-auto w-full max-w-7xl space-y-5 text-text-primary">
    <section class="flex flex-col gap-4 rounded-panel border border-border bg-gradient-to-br from-white to-brand-50 p-5 shadow-card sm:flex-row sm:items-start sm:justify-between sm:p-6">
        <div class="hero-meta min-w-0">
            <nav aria-label="Breadcrumb" class="mb-2 text-xs font-medium text-text-muted">
                <ol class="flex flex-wrap items-center gap-2">
                    <li><a class="text-brand-primary hover:text-brand-800" href="{{ route('professional.dashboard') }}">Dashboard</a></li>
                    <li aria-hidden="true" class="text-border">/</li>
                    <li><a class="text-brand-primary hover:text-brand-800" href="{{ route('professional.booking.index') }}">Bookings</a></li>
                    <li aria-hidden="true" class="text-border">/</li>
                    <li aria-current="page" class="text-text-secondary">Chat</li>
                </ol>
            </nav>
            <span class="hero-eyebrow inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-brand-700"><i class="ri-chat-3-line" aria-hidden="true"></i>Booking chat</span>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-brand-950 sm:text-[1.75rem]">Message {{ $otherParticipant }}</h1>
            <p class="mt-1 text-sm text-text-secondary">Booking #{{ $booking->id }}@if($serviceName) · {{ $serviceName }}@endif</p>
        </div>
    </section>
@endif

<div class="mx-auto flex w-full max-w-6xl justify-center {{ $userType === 'professional' ? 'pb-2' : '' }}">
    <section class="flex h-[min(640px,calc(100dvh-190px))] min-h-[420px] w-full max-w-[440px] flex-col overflow-hidden rounded-panel border border-border bg-surface shadow-popover max-sm:h-[calc(100dvh-150px)] max-sm:min-h-[390px] {{ $userType === 'professional' ? 'max-sm:h-[calc(100dvh-220px)]' : '' }}" aria-label="Chat with {{ $otherParticipant }}">
        <header class="flex shrink-0 items-center gap-3 bg-brand-primary px-4 py-3 text-white">
            @if($userType === 'customer')
                <a href="{{ route('user.all-appointment.index') }}" class="inline-flex size-10 shrink-0 items-center justify-center rounded-full text-white/90 transition-colors hover:bg-white/15 hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white" aria-label="Back to appointments">
            @else
                <a href="{{ route('professional.booking.index') }}" class="inline-flex size-10 shrink-0 items-center justify-center rounded-full text-white/90 transition-colors hover:bg-white/15 hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white" aria-label="Back to bookings">
            @endif
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.25 18.75-6.75-6.75 6.75-6.75M7.5 12h13.5" />
                </svg>
            </a>

            <div class="flex size-10 shrink-0 items-center justify-center rounded-full border border-white/25 bg-white/15 text-sm font-semibold text-white" aria-hidden="true">
                {{ Str::upper(Str::substr($otherParticipant, 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <h1 class="truncate text-sm font-semibold leading-5">{{ $otherParticipant }}</h1>
                <p class="truncate text-xs text-white/80">Booking #{{ $booking->id }}@if($serviceName) · {{ $serviceName }}@endif</p>
            </div>
            <a href="{{ route('home') }}" class="inline-flex size-9 shrink-0 items-center justify-center rounded-full text-white/90 transition-colors hover:bg-white/15 hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white" aria-label="Yuktione home">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 10.5 12 3.75l8.25 6.75v9a.75.75 0 0 1-.75.75h-5.25v-6h-4.5v6H4.5a.75.75 0 0 1-.75-.75v-9Z" />
                </svg>
            </a>
        </header>

        <div id="bookingContext" class="flex shrink-0 items-start gap-2 border-b border-brand-200 bg-brand-50 px-4 py-2.5 text-xs leading-5 text-brand-secondary">
            <svg class="mt-0.5 size-4 shrink-0 text-brand-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12m-12 5.25h12m-12 5.25h12M3.75 6.75h.008v.008H3.75V6.75Zm0 5.25h.008v.008H3.75V12Zm0 5.25h.008v.008H3.75v-.008Z" />
            </svg>
            <p class="min-w-0 flex-1">
                <span class="font-semibold">Booking #{{ $booking->id }}</span>
                @if($serviceName)<span> · {{ $serviceName }}</span>@endif
                @if($subServiceName)<span> / {{ $subServiceName }}</span>@endif
            </p>
            <button type="button" id="dismissBookingContext" class="-mr-1 inline-flex size-6 shrink-0 items-center justify-center rounded text-brand-secondary/70 hover:bg-brand-100 hover:text-brand-secondary focus-visible:outline-2 focus-visible:outline-focus-ring" aria-label="Dismiss booking information">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 6 12 12M18 6 6 18" />
                </svg>
            </button>
        </div>

        <div id="chatMessages" class="min-h-0 flex-1 space-y-3 overflow-y-auto bg-background px-3 py-4 sm:px-4" role="log" aria-label="Conversation messages" aria-live="polite" aria-relevant="additions text">
            @php $previousMessageDate = null; @endphp
            @forelse($messages as $message)
                @php
                    $messageDate = $message->created_at->format('Y-m-d');
                    $isSent = $userType === $message->sender_type;
                    $senderName = $message->sender_type === 'customer'
                        ? ($booking->user->name ?? 'Customer')
                        : ($booking->professional->name ?? 'Professional');
                @endphp
                @if($messageDate !== $previousMessageDate)
                    <div class="flex justify-center py-1" data-date-separator="{{ $messageDate }}">
                        <span class="rounded-full border border-border bg-surface px-3 py-1 text-[11px] font-medium text-text-muted">{{ $message->created_at->format('M j, Y') }}</span>
                    </div>
                    @php $previousMessageDate = $messageDate; @endphp
                @endif

                @if($message->is_system_message)
                    <div class="mx-auto max-w-[92%] rounded-card border border-brand-200 bg-brand-50 px-3 py-2 text-center text-xs leading-5 text-brand-secondary" data-message-id="{{ $message->id }}" data-system-message="true">
                        {{ $message->message }}
                    </div>
                @else
                    <article class="flex {{ $isSent ? 'justify-end' : 'justify-start' }}" data-message-id="{{ $message->id }}" data-sender-type="{{ $message->sender_type }}" data-message-date="{{ $messageDate }}">
                        <div class="max-w-[85%] min-w-0">
                            @unless($isSent)
                                <p class="mb-1 ml-1 text-[11px] font-semibold text-text-muted">{{ $senderName }}</p>
                            @endunless
                            <div class="rounded-2xl px-3.5 py-2.5 shadow-card {{ $isSent ? 'rounded-br-sm bg-brand-primary text-white' : 'rounded-bl-sm border border-border bg-surface text-text-primary' }}">
                                @if($message->message)
                                    <p class="whitespace-pre-wrap break-words text-sm leading-5">{{ $message->message }}</p>
                                @endif
                                @if($message->attachment)
                                    @php
                                        $attachmentName = basename($message->attachment);
                                        $attachmentExtension = strtolower(pathinfo($attachmentName, PATHINFO_EXTENSION));
                                    @endphp
                                    <a href="{{ asset('storage/' . $message->attachment) }}" target="_blank" rel="noopener noreferrer" class="mt-2 flex min-w-0 items-center gap-2 rounded-control border border-current/15 bg-black/5 px-2.5 py-2 text-xs font-medium hover:bg-black/10">
                                        <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            @if(in_array($attachmentExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif']))
                                                <rect x="3.75" y="4.5" width="16.5" height="15" rx="1.5" />
                                                <circle cx="9" cy="9.75" r="1.5" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 17.25 4.5-4.5 3 3 2.25-2.25 5.25 5.25" />
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 3.75h7.5l4.5 4.5v12A1.5 1.5 0 0 1 16.5 21.75h-10A1.5 1.5 0 0 1 5 20.25V5.25A1.5 1.5 0 0 1 6.5 3.75ZM13.5 3.75v4.5H18M8.25 13.5h7.5m-7.5 3h7.5" />
                                            @endif
                                        </svg>
                                        <span class="min-w-0 break-all">{{ $attachmentName }}</span>
                                    </a>
                                @endif
                                <div class="mt-1 flex items-center justify-end gap-1.5 text-[10px] {{ $isSent ? 'text-white/75' : 'text-text-muted' }}">
                                    <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('h:i A') }}</time>
                                    @if($isSent)
                                        <span class="read-receipt {{ $message->is_read ? 'text-cyan-200' : 'text-white/60' }}" data-read-receipt aria-label="{{ $message->is_read ? 'Read' : 'Sent' }}">✓✓</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </article>
                @endif
            @empty
                <div id="emptyConversation" class="flex h-full min-h-48 flex-col items-center justify-center px-5 text-center">
                    <span class="inline-flex size-11 items-center justify-center rounded-full bg-brand-50 text-brand-primary">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3.75h5.25M4.5 6.75A2.25 2.25 0 0 1 6.75 4.5h10.5A2.25 2.25 0 0 1 19.5 6.75v7.5a2.25 2.25 0 0 1-2.25 2.25H9l-4.5 3V6.75Z" />
                        </svg>
                    </span>
                    <h2 class="mt-3 text-sm font-semibold text-brand-secondary">Start your conversation</h2>
                    <p class="mt-1 max-w-xs text-xs leading-5 text-text-secondary">Send a message to discuss your booking with {{ $otherParticipant }}.</p>
                </div>
            @endforelse
        </div>

        <div class="shrink-0 border-t border-divider bg-surface px-3 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] sm:px-4">
            <div id="chatError" class="mb-2 hidden rounded-control border border-error bg-error-surface px-3 py-2 text-xs leading-5 text-error" role="alert" aria-live="assertive"></div>
            <div id="attachmentPreview" class="mb-2 hidden items-center gap-2 rounded-control border border-brand-200 bg-brand-50 px-3 py-2 text-xs text-brand-secondary">
                <svg class="size-4 shrink-0 text-brand-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 3.75h7.5l4.5 4.5v12A1.5 1.5 0 0 1 16.5 21.75h-10A1.5 1.5 0 0 1 5 20.25V5.25A1.5 1.5 0 0 1 6.5 3.75ZM13.5 3.75v4.5H18" />
                </svg>
                <span id="attachmentName" class="min-w-0 flex-1 truncate"></span>
                <button type="button" id="removeAttachment" class="inline-flex size-7 shrink-0 items-center justify-center rounded text-text-secondary hover:bg-white hover:text-error focus-visible:outline-2 focus-visible:outline-focus-ring" aria-label="Remove attachment">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m6 6 12 12M18 6 6 18" />
                    </svg>
                </button>
            </div>

            <form id="chatForm" class="flex items-end gap-2" enctype="multipart/form-data">
                <input type="file" id="attachmentInput" name="attachment" class="sr-only" accept="image/*,.pdf,.doc,.docx">
                <label for="attachmentInput" class="inline-flex size-10 shrink-0 cursor-pointer items-center justify-center rounded-full border border-border bg-surface text-text-secondary transition-colors hover:border-brand-200 hover:bg-brand-50 hover:text-brand-primary focus-within:outline-2 focus-within:outline-focus-ring" aria-label="Attach a file">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 12.75 6.9-6.9a3.18 3.18 0 0 1 4.5 4.5l-8.84 8.84a5.12 5.12 0 0 1-7.24-7.24l8.49-8.49" />
                    </svg>
                </label>
                <label for="messageInput" class="sr-only">Message</label>
                <textarea id="messageInput" name="message" rows="1" maxlength="5000" placeholder="Write a message…" class="max-h-28 min-h-10 min-w-0 flex-1 resize-none rounded-2xl border border-border bg-surface-muted px-4 py-2.5 text-sm leading-5 text-text-primary placeholder:text-text-muted focus-visible:border-brand-primary" aria-describedby="messageHint"></textarea>
                <button type="submit" id="btnSend" class="inline-flex size-10 shrink-0 items-center justify-center rounded-full border border-brand-primary bg-brand-primary text-white transition-colors hover:border-brand-800 hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring disabled:cursor-wait disabled:opacity-60" aria-label="Send message">
                    <svg id="sendIcon" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 3-7.5 18-3.75-7.5L2.25 9.75 21 3Zm0 0L9.75 13.5" />
                    </svg>
                    <svg id="sendSpinner" class="hidden size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
                    </svg>
                </button>
            </form>
            <p id="messageHint" class="mt-1.5 pl-12 text-[10px] leading-4 text-text-muted">Enter to send · Shift + Enter for a new line</p>
        </div>
    </section>
</div>
@if($userType === 'professional')
</div>
@endif
@endsection

@section('scripts')
<script>
(() => {
    const bookingId = @json($booking->id);
    const userType = @json($userType);
    const currentUserName = @json($userType === 'customer' ? ($booking->user->name ?? 'Customer') : ($booking->professional->name ?? 'Professional'));
    const messagesUrl = @json($userType === 'customer' ? route('user.chat.messages', $booking->id) : route('professional.chat.messages', $booking->id));
    const sendUrl = @json($userType === 'customer' ? route('user.chat.send', $booking->id) : route('professional.chat.send', $booking->id));
    const storageUrl = @json(asset('storage'));
    const messages = document.getElementById('chatMessages');
    const form = document.getElementById('chatForm');
    const input = document.getElementById('messageInput');
    const fileInput = document.getElementById('attachmentInput');
    const preview = document.getElementById('attachmentPreview');
    const previewName = document.getElementById('attachmentName');
    const errorBox = document.getElementById('chatError');
    const sendButton = document.getElementById('btnSend');
    const sendIcon = document.getElementById('sendIcon');
    const sendSpinner = document.getElementById('sendSpinner');
    let sending = false;
    let selectedFile = null;

    const scrollToBottom = () => {
        messages.scrollTop = messages.scrollHeight;
    };

    const dateLabel = (date) => {
        const parsed = new Date(`${date}T00:00:00`);
        return Number.isNaN(parsed.getTime())
            ? date
            : parsed.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
    };

    const formatMessageTime = (message) => {
        if (message.formatted_time) return message.formatted_time;
        if (!message.created_at) return '';
        const parsed = new Date(message.created_at.replace(' ', 'T'));
        return Number.isNaN(parsed.getTime()) ? '' : parsed.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
    };

    const ensureDateSeparator = (date) => {
        if (messages.querySelector(`[data-date-separator="${CSS.escape(date)}"]`)) return;
        const separator = document.createElement('div');
        separator.className = 'flex justify-center py-1';
        separator.dataset.dateSeparator = date;
        const label = document.createElement('span');
        label.className = 'rounded-full border border-border bg-surface px-3 py-1 text-[11px] font-medium text-text-muted';
        label.textContent = dateLabel(date);
        separator.append(label);
        messages.append(separator);
    };

    const attachmentUrl = (path) => `${storageUrl.replace(/\/$/, '')}/${String(path).split('/').map(encodeURIComponent).join('/')}`;
    const isImage = (fileName) => /\.(avif|gif|jpe?g|png|webp)$/i.test(fileName);

    const appendMessage = (message, senderName = message.sender_name || currentUserName) => {
        if (messages.querySelector(`[data-message-id="${CSS.escape(String(message.id))}"]`)) return;
        const date = message.formatted_date
            ? new Date(message.formatted_date).toString() !== 'Invalid Date'
                ? new Date(message.formatted_date).toISOString().slice(0, 10)
                : message.created_at?.slice(0, 10)
            : message.created_at?.slice(0, 10);
        if (date) ensureDateSeparator(date);

        if (message.is_system_message) {
            const systemMessage = document.createElement('div');
            systemMessage.className = 'mx-auto max-w-[92%] rounded-card border border-brand-200 bg-brand-50 px-3 py-2 text-center text-xs leading-5 text-brand-secondary';
            systemMessage.dataset.messageId = String(message.id);
            systemMessage.dataset.systemMessage = 'true';
            systemMessage.textContent = message.message || '';
            messages.append(systemMessage);
            return;
        }

        const ownMessage = message.sender_type === userType;
        const article = document.createElement('article');
        article.className = `flex ${ownMessage ? 'justify-end' : 'justify-start'}`;
        article.dataset.messageId = String(message.id);
        article.dataset.senderType = message.sender_type;
        if (date) article.dataset.messageDate = date;

        const wrapper = document.createElement('div');
        wrapper.className = 'max-w-[85%] min-w-0';
        if (!ownMessage) {
            const sender = document.createElement('p');
            sender.className = 'mb-1 ml-1 text-[11px] font-semibold text-text-muted';
            sender.textContent = senderName;
            wrapper.append(sender);
        }

        const bubble = document.createElement('div');
        bubble.className = `rounded-2xl px-3.5 py-2.5 shadow-card ${ownMessage ? 'rounded-br-sm bg-brand-primary text-white' : 'rounded-bl-sm border border-border bg-surface text-text-primary'}`;
        if (message.message) {
            const text = document.createElement('p');
            text.className = 'whitespace-pre-wrap break-words text-sm leading-5';
            text.textContent = message.message;
            bubble.append(text);
        }
        if (message.attachment) {
            const name = String(message.attachment).split('/').pop() || 'Attachment';
            const attachment = document.createElement('a');
            attachment.href = attachmentUrl(message.attachment);
            attachment.target = '_blank';
            attachment.rel = 'noopener noreferrer';
            attachment.className = 'mt-2 flex min-w-0 items-center gap-2 rounded-control border border-current/15 bg-black/5 px-2.5 py-2 text-xs font-medium hover:bg-black/10';
            const icon = document.createElement('span');
            icon.className = 'size-4 shrink-0';
            icon.textContent = isImage(name) ? '▧' : '▤';
            const fileName = document.createElement('span');
            fileName.className = 'min-w-0 break-all';
            fileName.textContent = name;
            attachment.append(icon, fileName);
            bubble.append(attachment);
        }
        const footer = document.createElement('div');
        footer.className = `mt-1 flex items-center justify-end gap-1.5 text-[10px] ${ownMessage ? 'text-white/75' : 'text-text-muted'}`;
        const time = document.createElement('time');
        time.textContent = formatMessageTime(message);
        footer.append(time);
        if (ownMessage) {
            const receipt = document.createElement('span');
            receipt.className = `read-receipt ${message.is_read ? 'text-cyan-200' : 'text-white/60'}`;
            receipt.dataset.readReceipt = '';
            receipt.setAttribute('aria-label', message.is_read ? 'Read' : 'Sent');
            receipt.textContent = '✓✓';
            footer.append(receipt);
        }
        bubble.append(footer);
        wrapper.append(bubble);
        article.append(wrapper);
        messages.append(article);
    };

    const updateReadReceipts = (items) => {
        items.forEach((message) => {
            if (message.sender_type !== userType || !message.is_read) return;
            const receipt = messages.querySelector(`[data-message-id="${CSS.escape(String(message.id))}"] [data-read-receipt]`);
            if (receipt) {
                receipt.classList.remove('text-white/60');
                receipt.classList.add('text-cyan-200');
                receipt.setAttribute('aria-label', 'Read');
            }
        });
    };

    const loadMessages = async () => {
        try {
            const response = await fetch(messagesUrl, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) throw new Error('Unable to refresh messages.');
            const payload = await response.json();
            if (!payload.success || !Array.isArray(payload.messages)) throw new Error('The message response was invalid.');
            const wasNearBottom = messages.scrollHeight - messages.scrollTop <= messages.clientHeight + 80;
            payload.messages.forEach((message) => appendMessage(message));
            updateReadReceipts(payload.messages);
            if (wasNearBottom) scrollToBottom();
        } catch (requestError) {
            if (!document.hidden) {
                errorBox.textContent = requestError.message || 'Unable to refresh messages.';
                errorBox.classList.remove('hidden');
            }
        }
    };

    document.getElementById('dismissBookingContext').addEventListener('click', () => {
        document.getElementById('bookingContext').remove();
    });

    fileInput.addEventListener('change', () => {
        selectedFile = fileInput.files?.[0] || null;
        previewName.textContent = selectedFile ? selectedFile.name : '';
        preview.classList.toggle('hidden', !selectedFile);
        preview.classList.toggle('flex', Boolean(selectedFile));
        errorBox.classList.add('hidden');
    });
    document.getElementById('removeAttachment').addEventListener('click', () => {
        selectedFile = null;
        fileInput.value = '';
        previewName.textContent = '';
        preview.classList.add('hidden');
        preview.classList.remove('flex');
    });

    input.addEventListener('input', () => {
        input.style.height = 'auto';
        input.style.height = `${Math.min(input.scrollHeight, 112)}px`;
        errorBox.classList.add('hidden');
    });
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (sending) return;

        const text = input.value.trim();
        if (!text && !selectedFile) return;

        sending = true;
        sendButton.disabled = true;
        sendIcon.classList.add('hidden');
        sendSpinner.classList.remove('hidden');
        errorBox.classList.add('hidden');
        const formData = new FormData();
        if (text) formData.append('message', text);
        if (selectedFile) formData.append('attachment', selectedFile);

        try {
            const response = await fetch(sendUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });
            const payload = await response.json();
            if (!response.ok || !payload.success) {
                const validation = payload.errors ? Object.values(payload.errors).flat()[0] : null;
                throw new Error(validation || payload.message || payload.error || 'Unable to send this message. Please try again.');
            }

            appendMessage({
                ...payload.message,
                sender_type: payload.message.sender_type || userType,
                sender_name: payload.sender_name,
                formatted_time: payload.formatted_time,
            }, payload.sender_name);
            input.value = '';
            input.style.height = 'auto';
            selectedFile = null;
            fileInput.value = '';
            previewName.textContent = '';
            preview.classList.add('hidden');
            preview.classList.remove('flex');
            scrollToBottom();
            input.focus();
        } catch (sendError) {
            errorBox.textContent = sendError.message || 'Unable to send this message. Please try again.';
            errorBox.classList.remove('hidden');
        } finally {
            sending = false;
            sendButton.disabled = false;
            sendIcon.classList.remove('hidden');
            sendSpinner.classList.add('hidden');
        }
    });

    const pollTimer = window.setInterval(loadMessages, 5000);
    window.addEventListener('pagehide', () => window.clearInterval(pollTimer), { once: true });
    scrollToBottom();
})();
</script>
@endsection
