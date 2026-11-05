@extends('professional.layout.layout')

@section('title', 'Chat with Admin | YuktiOne')

@section('content')
<div class="chat-page mx-auto w-full max-w-7xl space-y-5 text-text-primary">
    <div class="chat-shell mx-auto w-full max-w-2xl space-y-5">
        <section class="chat-hero flex flex-col gap-4 rounded-panel border border-border bg-gradient-to-br from-white to-brand-50 p-5 shadow-card sm:flex-row sm:items-start sm:justify-between sm:p-6">
            <div class="hero-meta min-w-0">
                <nav aria-label="Breadcrumb" class="mb-2 text-xs font-medium text-text-muted">
                    <ol class="flex flex-wrap items-center gap-2">
                        <li><a class="text-brand-primary hover:text-brand-800" href="{{ route('professional.dashboard') }}">Dashboard</a></li>
                        <li aria-hidden="true" class="text-border">/</li>
                        <li aria-current="page" class="text-text-secondary">Chat</li>
                    </ol>
                </nav>
                <span class="hero-eyebrow inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-brand-700"><i class="ri-message-3-line" aria-hidden="true"></i>Support</span>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-brand-950 sm:text-[1.75rem]">Chat with Admin</h1>
                <p class="mt-1 text-sm text-text-secondary">Message the YuktiOne admin team for account or booking support.</p>
            </div>
            <span class="status-pill inline-flex items-center gap-2 rounded-full bg-success-surface px-3 py-1.5 text-xs font-semibold text-success" id="connectionStatus">
                <span class="size-1.5 rounded-full bg-success" aria-hidden="true"></span>
                Connected
            </span>
        </section>

        <section class="chat-card overflow-hidden rounded-panel border border-border bg-white shadow-card">
            <header class="chat-card__head border-b border-divider px-5 py-4">
                <h2 class="flex items-center gap-2 text-base font-semibold"><i class="ri-chat-voice-line" aria-hidden="true"></i>Conversation</h2>
                <p class="mt-1 text-sm opacity-90">Stay connected with the admin team for quick assistance.</p>
            </header>
            <div class="chat-card__body">
                <div class="chat-messages-wrapper min-h-[18rem] max-h-[55vh] overflow-y-auto bg-background p-4 sm:p-5">
                    <div id="chatMessages" class="space-y-3">
                        <div class="loading-spinner flex min-h-64 flex-col items-center justify-center text-brand-700">
                            <div class="h-8 w-8 animate-spin rounded-full border-4 border-brand-200 border-t-brand-700" role="status" aria-label="Loading"></div>
                            <span class="mt-3 text-sm text-text-muted">Loading chat...</span>
                        </div>
                    </div>
                </div>
                <div class="chat-input-area">
                    <form id="chatMessageForm" class="border-t border-divider p-4" enctype="multipart/form-data">
                        @csrf
                        <div class="input-group flex items-center gap-2">
                            <input type="text" class="message-input min-w-0 flex-1 rounded-control border border-border bg-white px-4 py-2.5 text-sm text-text-primary placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200" id="messageInput" name="message" placeholder="Type your message to admin..." autocomplete="off">
                            <input type="file" id="fileInput" name="attachment" class="sr-only" accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.jpg,.jpeg,.png,.gif">
                            <button type="button" class="btn-attach" id="attachBtn" title="Attach File" aria-label="Attach file">
                                <i class="fas fa-paperclip" aria-hidden="true"></i>
                            </button>
                            <button type="submit" class="btn-send" id="sendBtn" aria-label="Send message">
                                <i class="fas fa-paper-plane" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div id="filePreview" class="attachment-preview mt-3 hidden rounded-control border border-brand-200 bg-brand-50 px-3 py-2.5 text-sm text-brand-800">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-2">
                                    <i class="ri-attachment-line shrink-0" aria-hidden="true"></i>
                                    <span id="fileName" class="min-w-0 truncate font-medium"></span>
                                    <span id="fileSize" class="shrink-0 text-xs text-text-muted"></span>
                                </div>
                                <button type="button" class="inline-flex items-center gap-1 rounded-control px-2 py-1 text-xs font-semibold text-error transition hover:bg-error-surface" id="removeFileBtn">
                                    <i class="ri-close-line" aria-hidden="true"></i> Remove
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let currentChatId = null;
    let isLoading = false;
    loadChat();
    function loadChat() {
        if (isLoading) return;
        isLoading = true;
        showLoadingState();
        $.ajax({
            url: '{{ route("professional.admin-chat.messages") }}',
            method: 'GET',
            success: function(response) {
                if (response.success && response.chat) {
                    currentChatId = response.chat.id;
                    displayMessages(response.chat.messages);
                    updateConnectionStatus(true);
                } else {
                    showEmptyState();
                }
            },
            error: function() {
                showErrorState('Failed to load chat. Please refresh the page.');
                updateConnectionStatus(false);
            },
            complete: function() {
                isLoading = false;
            }
        });
    }
    function displayMessages(messages) {
        if (!messages || messages.length === 0) { showEmptyState(); return; }
        messages.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
        const html = messages.map(buildMessageHtml).join('');
        $('#chatMessages').html(html);
        scrollToBottom();
    }
    function buildMessageHtml(message) {
        const isProfessional = message.sender_type === 'App\\Models\\Professional';
        const bubbleClass = isProfessional
            ? 'message-bubble max-w-[85%] rounded-2xl rounded-br-md bg-brand-primary px-4 py-3 text-white shadow-sm sm:max-w-[70%]'
            : 'message-bubble max-w-[85%] rounded-2xl rounded-bl-md border border-border bg-white px-4 py-3 text-text-primary shadow-sm sm:max-w-[70%]';
        const alignClass = isProfessional ? 'text-right' : 'text-left';
        let attachmentHtml = '';
        if (message.attachments && message.attachments.length > 0) {
            message.attachments.forEach(function(attachment) {
                attachmentHtml += `
                    <div class="attachment-card mt-2 rounded-control border ${isProfessional ? 'border-white/30 bg-white/10' : 'border-border bg-surface-muted'} p-2">
                        <a class="inline-flex items-center gap-2 text-sm ${isProfessional ? 'text-white' : 'text-brand-800'}" href="/professional/admin-chat/attachment/${attachment.id}/download" target="_blank" rel="noopener noreferrer">
                            <i class="${attachment.file_icon || 'ri-attachment-line'}" aria-hidden="true"></i>
                            <span class="min-w-0 break-all">${escapeHtml(attachment.original_name || 'Attachment')}</span>
                            <small class="opacity-80">(${escapeHtml(attachment.human_file_size || 'N/A')})</small>
                        </a>
                    </div>`;
            });
        }
        return `
            <div class="message-row flex ${isProfessional ? 'justify-end' : 'justify-start'}">
                <div class="${bubbleClass}">
                    <div class="message-sender mb-1 text-[11px] font-semibold ${isProfessional ? 'text-white/80' : 'text-text-muted'} ${alignClass}">${isProfessional ? 'You' : escapeHtml(message.sender_name || 'Admin')}</div>
                    ${message.message ? `<div class="message-text whitespace-pre-wrap break-words text-sm leading-5">${escapeHtml(message.message)}</div>` : ''}
                    ${attachmentHtml}
                    <div class="message-time mt-2 text-[10px] opacity-75 ${alignClass}">${formatDateTime(message.created_at)}</div>
                </div>
            </div>`;
    }
    $('#chatMessageForm').on('submit', function(e) {
        e.preventDefault();
        const message = $('#messageInput').val().trim();
        const hasFile = $('#fileInput')[0].files.length > 0;
        if (!message && !hasFile) { return; }
        const formData = new FormData(this);
        const sendBtn = $('#sendBtn');
        const originalBtnHtml = sendBtn.html();
        sendBtn.prop('disabled', true).html('<i class="ri-loader-4-line fa-spin" aria-hidden="true"></i>');
        $('#messageInput').prop('disabled', true);
        $.ajax({
            url: '{{ route("professional.admin-chat.send-message") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#messageInput').val('');
                    clearFileSelection();
                    appendMessage(response.message);
                    scrollToBottom();
                    updateConnectionStatus(true);
                } else {
                    toastr.error('Failed to send message');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Failed to send message. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    errorMsg = Object.values(errors).flat().join(', ');
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                toastr.error(errorMsg);
                updateConnectionStatus(false);
            },
            complete: function() {
                sendBtn.prop('disabled', false).html(originalBtnHtml);
                $('#messageInput').prop('disabled', false).focus();
            }
        });
    });
    function appendMessage(message) {
        const html = buildMessageHtml(message);
        if ($('#chatMessages').find('.chat-empty-state').length > 0) {
            $('#chatMessages').html(html);
        } else {
            $('#chatMessages').append(html);
        }
    }
    $('#attachBtn').on('click', function() { $('#fileInput').click(); });
    $('#fileInput').on('change', function() { const file = this.files[0]; if (file) showFilePreview(file); });
    $('#removeFileBtn').on('click', function() { clearFileSelection(); });
    function showFilePreview(file) {
        $('#fileName').text(file.name);
        $('#fileSize').text(`(${formatFileSize(file.size)})`);
        $('#filePreview').removeClass('hidden').addClass('block');
    }
    function clearFileSelection() {
        $('#fileInput').val('');
        $('#filePreview').addClass('hidden').removeClass('block');
        $('#fileName').text('');
        $('#fileSize').text('');
    }
    function scrollToBottom() {
        const container = document.querySelector('.chat-messages-wrapper');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }
    function showLoadingState() {
        $('#chatMessages').html(`
            <div class="loading-spinner flex min-h-64 flex-col items-center justify-center text-brand-700">
                <div class="h-8 w-8 animate-spin rounded-full border-4 border-brand-200 border-t-brand-700" role="status" aria-label="Loading"></div>
                <span class="mt-3 text-sm text-text-muted">Loading chat...</span>
            </div>`);
    }
    function showEmptyState() {
        $('#chatMessages').html(`
            <div class="chat-empty-state flex min-h-64 flex-col items-center justify-center px-4 text-center text-text-secondary">
                <span class="inline-flex size-12 items-center justify-center rounded-full bg-brand-50 text-brand-primary">
                    <i class="ri-message-3-line text-xl" aria-hidden="true"></i>
                </span>
                <h5 class="mt-3 text-base font-semibold text-brand-950">No messages yet</h5>
                <p class="mt-1 max-w-xs text-sm">Start a conversation with the admin team</p>
            </div>`);
    }
    function showErrorState(message) {
        $('#chatMessages').html(`
            <div class="chat-empty-state flex min-h-64 flex-col items-center justify-center px-4 text-center">
                <span class="inline-flex size-12 items-center justify-center rounded-full bg-error-surface text-error">
                    <i class="ri-error-warning-line text-xl" aria-hidden="true"></i>
                </span>
                <h5 class="mt-3 text-base font-semibold text-brand-950">Error</h5>
                <p class="mt-1 max-w-sm text-sm text-error">${escapeHtml(message)}</p>
            </div>`);
    }
    function updateConnectionStatus(connected) {
        const statusEl = $('#connectionStatus');
        if (connected) {
            statusEl
                .attr('class', 'status-pill inline-flex items-center gap-2 rounded-full bg-success-surface px-3 py-1.5 text-xs font-semibold text-success')
                .html('<span class="size-1.5 rounded-full bg-success" aria-hidden="true"></span> Connected');
        } else {
            statusEl
                .attr('class', 'status-pill inline-flex items-center gap-2 rounded-full bg-error-surface px-3 py-1.5 text-xs font-semibold text-error')
                .html('<span class="size-1.5 rounded-full bg-error" aria-hidden="true"></span> Disconnected');
        }
    }
    function formatDateTime(dateTime) {
        const date = new Date(dateTime);
        return date.toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    }
    function formatFileSize(bytes) {
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes;
        let unitIndex = 0;
        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex++;
        }
        return `${Math.round(size * 100) / 100} ${units[unitIndex]}`;
    }
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    $('#messageInput').focus();
    $('#messageInput').on('keypress', function(e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            $('#chatMessageForm').submit();
        }
    });
    setInterval(function() {
        if (!isLoading && currentChatId) {
            loadChat();
        }
    }, 30000);
});
</script>
@endsection
