@extends('customer.layout.layout')

@section('title', 'Chat with Admin')

@section('styles')
<style>
    .customer-support-chat {
        height: min(650px, calc(100dvh - 10rem));
        min-height: min(360px, calc(100dvh - 8rem));
    }
</style>
@endsection

@section('content')
<div class="mx-auto flex max-w-5xl justify-center">
    <section class="customer-support-chat flex w-full max-w-[420px] flex-col overflow-hidden rounded-panel border border-border bg-surface shadow-popover" aria-label="Yuktione Support conversation">
        <header class="flex shrink-0 items-center gap-3 bg-brand-primary px-4 py-3 text-white">
            <div class="inline-flex size-11 shrink-0 items-center justify-center rounded-full border border-white/25 bg-white/15" aria-hidden="true">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75a8.25 8.25 0 0 0-7.1 12.45L4.5 20.25l4.05-.9A8.25 8.25 0 1 0 12 3.75Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 11.25h.008v.008H8.25v-.008Zm3.75 0h.008v.008H12v-.008Zm3.75 0h.008v.008h-.008v-.008Z" />
                </svg>
            </div>
            <div class="min-w-0">
                <h1 class="truncate font-display text-base font-semibold">Yuktione Support</h1>
                <p class="mt-0.5 text-xs text-white/80">Customer support</p>
            </div>
        </header>

        <div id="chatMessages" class="min-h-0 flex-1 space-y-3 overflow-y-auto bg-brand-50/60 px-3 py-4 sm:px-4" aria-live="polite" aria-relevant="additions text">
            <div class="flex min-h-full flex-col items-center justify-center px-4 py-8 text-center">
                <span class="inline-flex size-12 items-center justify-center rounded-full bg-white text-brand-primary shadow-card" aria-hidden="true">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3.75h6m-9.75 7.5v-12A2.25 2.25 0 0 1 6 5.25h12A2.25 2.25 0 0 1 20.25 7.5v9A2.25 2.25 0 0 1 18 18.75H9l-4.5 2.25Z" />
                    </svg>
                </span>
                <h2 class="mt-3 font-display text-sm font-semibold text-brand-secondary">Start a conversation</h2>
                <p class="mt-1 max-w-56 text-xs leading-5 text-text-secondary">Send a message to Yuktione Support and our team will reply here.</p>
            </div>
        </div>

        <footer class="shrink-0 border-t border-divider bg-surface px-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] pt-3 sm:px-4">
            <form id="chatForm" enctype="multipart/form-data">
                <div class="flex items-end gap-2 rounded-control border border-border bg-surface-muted p-1.5 focus-within:border-brand-primary focus-within:ring-2 focus-within:ring-brand-100">
                    <input type="text" id="messageInput" name="message" placeholder="Type a message..." autocomplete="off" required
                        class="min-h-10 min-w-0 flex-1 bg-transparent px-2 py-2 text-base text-text-primary placeholder:text-text-muted focus:outline-none">
                    <input type="file" class="hidden" id="attachmentInput" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt,.zip">
                    <button type="button" class="inline-flex size-10 shrink-0 items-center justify-center rounded-full text-text-secondary transition-colors hover:bg-brand-50 hover:text-brand-primary focus-visible:outline-2 focus-visible:outline-focus-ring" id="attachBtn" title="Attach files" aria-label="Attach files">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 12.75 6.6-6.6a3 3 0 0 1 4.25 4.24l-8.13 8.14a4.5 4.5 0 0 1-6.37-6.37l8.13-8.13" />
                        </svg>
                    </button>
                    <button type="submit" class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-primary text-white transition-colors hover:bg-brand-800 disabled:cursor-not-allowed disabled:opacity-60 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring" id="sendBtn" aria-label="Send message">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 12 13.5-7.5-3.75 15-3.75-6-6-1.5Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 13.5 4.5-4.5" />
                        </svg>
                    </button>
                </div>
                <div id="selectedFiles" class="mt-2 hidden max-h-24 space-y-1 overflow-y-auto rounded-control bg-brand-50 p-2 text-xs text-text-secondary" style="display: none"></div>
            </form>
        </footer>
    </section>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let currentChatId = null;
    let messagePollingInterval = null;
    initializeChat();

    function initializeChat() {
        $.ajax({
            url: '{{ route("user.admin-chat.get-or-create") }}',
            method: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) {
                    currentChatId = response.chat.id;
                    loadMessages();
                    startMessagePolling();
                } else {
                    showError('Failed to initialize chat');
                }
            },
            error: function() { showError('Failed to initialize chat'); }
        });
    }

    function loadMessages() {
        if (!currentChatId) return;
        $.ajax({
            url: '{{ route("user.admin-chat.messages") }}',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    displayMessages(response.messages);
                }
            },
            error: function() { console.error('Failed to load messages'); }
        });
    }

    function displayMessages(messages) {
        const container = $('#chatMessages');
        container.empty();
        if (messages.length === 0) {
            container.html(`
                <div class="flex min-h-full flex-col items-center justify-center px-4 py-8 text-center">
                    <span class="inline-flex size-12 items-center justify-center rounded-full bg-white text-brand-primary shadow-card" aria-hidden="true">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3.75h6m-9.75 7.5v-12A2.25 2.25 0 0 1 6 5.25h12A2.25 2.25 0 0 1 20.25 7.5v9A2.25 2.25 0 0 1 18 18.75H9l-4.5 2.25Z"/></svg>
                    </span>
                    <h2 class="mt-3 font-display text-sm font-semibold text-brand-secondary">Start a conversation</h2>
                    <p class="mt-1 max-w-56 text-xs leading-5 text-text-secondary">Send a message to Yuktione Support and our team will reply here.</p>
                </div>
            `);
            return;
        }

        let previousDate = null;
        messages.forEach(function(message) {
            const isCustomer = message.sender_type === 'App\\Models\\User';
            const messageClass = isCustomer
                ? 'ml-auto rounded-2xl rounded-br-md bg-brand-primary text-white'
                : 'mr-auto rounded-2xl rounded-bl-md border border-divider bg-white text-text-primary';
            const messageDate = new Date(message.created_at);
            const dateKey = `${messageDate.getFullYear()}-${messageDate.getMonth()}-${messageDate.getDate()}`;
            if (dateKey !== previousDate) {
                const dateLabel = messageDate.toLocaleDateString([], { weekday: 'short', month: 'short', day: 'numeric' });
                container.append(`
                    <div class="flex justify-center py-1">
                        <span class="rounded-full border border-divider bg-white/90 px-3 py-1 text-[10px] font-medium text-text-muted">${escapeHtml(dateLabel)}</span>
                    </div>
                `);
                previousDate = dateKey;
            }
            let messageHtml = `
                <div class="flex ${isCustomer ? 'justify-end' : 'justify-start'}">
                    <div class="max-w-[86%] break-words px-3 py-2 text-sm leading-5 shadow-sm ${messageClass}">
                        <div class="whitespace-pre-wrap">${escapeHtml(message.message)}</div>
            `;

            if (message.attachments && message.attachments.length > 0) {
                messageHtml += '<div class="mt-2 grid gap-1">';
                message.attachments.forEach(function(attachment) {
                    const downloadUrl = '{{ route("user.admin-chat.attachment.download", ":id") }}'.replace(':id', attachment.id);
                    messageHtml += `
                        <a href="${downloadUrl}" class="flex min-w-0 items-center gap-2 rounded-control border border-current/15 bg-black/5 px-2 py-1.5 text-xs hover:bg-black/10">
                            <span class="shrink-0" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg></span>
                            <span class="min-w-0 break-all">${escapeHtml(attachment.filename)}</span>
                            <small class="shrink-0 opacity-75">(${escapeHtml(attachment.human_file_size || 'Unknown size')})</small>
                        </a>
                    `;
                });
                messageHtml += '</div>';
            }

            messageHtml += `
                        <div class="mt-1 text-right text-[10px] ${isCustomer ? 'text-white/75' : 'text-text-muted'}">${formatDateTime(message.created_at)}</div>
                    </div>
                </div>
            `;
            container.append(messageHtml);
        });
        container.scrollTop(container[0].scrollHeight);
    }

    // Phone number detection utility
    function detectPersonalInfo(text) {
        // Comprehensive phone number patterns
        const phonePatterns = [
            // Indian numbers with country code: +91, 0091, 91
            /(\+91|0091|91)[\s\-]?[6-9]\d{9}/g,
            // Indian numbers without country code: 10 digits starting with 6-9
            /(?<!\d)[6-9]\d{9}(?!\d)/g,
            // International formats with common separators
            /(\+\d{1,3})?[\s\-\.]?\(?\d{1,4}\)?[\s\-\.]?\d{1,4}[\s\-\.]?\d{1,4}[\s\-\.]?\d{1,9}/g,
            // Numbers with brackets, dashes, dots, spaces (10+ digits)
            /\(?\d{3,4}\)?[\s\-\.]?\d{3,4}[\s\-\.]?\d{3,6}/g,
            // WhatsApp pattern: "whatsapp me" followed by number
            /whatsapp\s*(me|at|on)?\s*[\+\d\s\-\(\)\.]{8,}/gi,
            // Call me pattern
            /call\s*(me\s*)?(?:at|on)?\s*[\+\d\s\-\(\)\.]{8,}/gi,
            // Phone/mobile pattern
            /(phone|mobile|contact|number)\s*[:=]?\s*[\+\d\s\-\(\)\.]{8,}/gi,
        ];

        // Additional personal info patterns
        const emailPattern = /\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/g;
        const socialMediaPatterns = [
            /(?:instagram|insta)\s*[:@]?\s*[A-Za-z0-9._]+/gi,
            /(?:facebook|fb)\s*[:@]?\s*[A-Za-z0-9._]+/gi,
            /(?:twitter|x\.com)\s*[:@]?\s*[A-Za-z0-9._]+/gi,
            /(?:telegram|tg)\s*[:@]?\s*[A-Za-z0-9._]+/gi,
        ];

        // Check for phone numbers
        for (const pattern of phonePatterns) {
            if (pattern.test(text)) {
                return { 
                    detected: true, 
                    type: 'phone number', 
                    message: 'Phone numbers are not allowed in messages. Please use the platform\'s communication features only.' 
                };
            }
        }

        // Check for emails
        if (emailPattern.test(text)) {
            return { 
                detected: true, 
                type: 'email address', 
                message: 'Email addresses are not allowed in messages. Please use the platform\'s communication features only.' 
            };
        }

        // Check for social media
        for (const pattern of socialMediaPatterns) {
            if (pattern.test(text)) {
                return { 
                    detected: true, 
                    type: 'social media handle', 
                    message: 'Social media handles are not allowed in messages. Please use the platform\'s communication features only.' 
                };
            }
        }

        return { detected: false };
    }

    // Show notification for blocked content
    function showPersonalInfoAlert(type, message) {
        // Remove any existing alerts
        $('.personal-info-alert').remove();
        
        const alertHtml = `
            <div class="personal-info-alert">
                <div>
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Personal Information Detected</strong>
                        <p>${message}</p>
                    </div>
                    <button onclick="$(this).closest('.personal-info-alert').fadeOut(300)">&times;</button>
                </div>
            </div>

        `;
        
        $('body').append(alertHtml);
        
        // Auto-remove after 6 seconds
        setTimeout(() => {
            $('.personal-info-alert').fadeOut(300);
        }, 6000);
    }

    $('#chatForm').on('submit', function(e) {
        e.preventDefault();
        const message = $('#messageInput').val().trim();
        if (!message) return;

        // Check for personal information
        const personalInfoCheck = detectPersonalInfo(message);
        if (personalInfoCheck.detected) {
            showPersonalInfoAlert(personalInfoCheck.type, personalInfoCheck.message);
            return; // Block the message from being sent
        }

        const sendBtn = $('#sendBtn');
        const originalContent = sendBtn.html();
        sendBtn.prop('disabled', true).html('<i class="ri-loader-4-line fa-spin"></i>');

        const formData = new FormData();
        formData.append('message', message);
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
        const fileInput = $('#attachmentInput')[0];
        if (fileInput && fileInput.files && fileInput.files.length > 0) {
            for (let i = 0; i < fileInput.files.length; i++) {
                formData.append('attachments[]', fileInput.files[i]);
            }
        }

        $.ajax({
            url: '{{ route("user.admin-chat.send-message") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#messageInput').val('');
                    $('#attachmentInput').val('');
                    $('#selectedFiles').hide().empty();
                    loadMessages();
                } else {
                    showError('Failed to send message');
                }
            },
            error: function(xhr) { 
                // Check if the error is due to personal information detection
                if (xhr.status === 422 && xhr.responseJSON) {
                    const response = xhr.responseJSON;
                    if (response.type && response.message) {
                        showPersonalInfoAlert(response.type, response.message);
                    } else {
                        showError('Failed to send message');
                    }
                } else {
                    showError('Failed to send message');
                }
            },
            complete: function() { sendBtn.prop('disabled', false).html(originalContent); }
        });
    });

    $('#attachBtn').on('click', function() { $('#attachmentInput').click(); });

    $('#attachmentInput').on('change', function() {
        const files = this.files;
        const container = $('#selectedFiles');
        container.empty();
        if (files.length > 0) {
            container.show();
            container.append('<div class="font-semibold">Selected files</div>');
            for (let i = 0; i < files.length; i++) {
                container.append(`
                    <div class="flex min-w-0 items-center gap-2">
                        <span aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg></span>
                        <span class="min-w-0 break-all">${escapeHtml(files[i].name)}</span>
                        <small class="shrink-0">(${formatFileSize(files[i].size)})</small>
                    </div>
                `);
            }
        } else {
            container.hide();
        }
    });

    $('#messageInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#chatForm').submit();
        }
    });

    function startMessagePolling() {
        messagePollingInterval = setInterval(function() { loadMessages(); }, 5000);
    }

    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            if (messagePollingInterval) clearInterval(messagePollingInterval);
        } else {
            startMessagePolling();
        }
    });

    $(window).on('beforeunload', function() {
        if (messagePollingInterval) clearInterval(messagePollingInterval);
    });

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatDateTime(dateTimeString) {
        const date = new Date(dateTimeString);
        const now = new Date();
        const diff = now - date;
        const diffDays = Math.floor(diff / (1000 * 60 * 60 * 24));
        if (diffDays === 0) {
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        } else if (diffDays === 1) {
            return 'Yesterday ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function showError(message) {
        if (typeof toastr !== 'undefined') {
            toastr.error(message);
        } else {
            alert(message);
        }
    }
});
</script>
@endsection