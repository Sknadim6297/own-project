<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Professional Login | Yuktione</title>
    @include('partials.dm-sans')
    @vite(['resources/css/tazen.css', 'resources/css/auth-theme.css'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


    <style>
        .invalid-feedback { display: none; color: #b42318; font-size: .875rem; margin-top: .5rem; }
        body.professional-login-page { min-height: 100vh; background: #eaf8f5; }
        #login { display: grid; grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr); width: min(100% - 2rem, 62rem); min-height: 34rem; margin: 3rem auto; overflow: hidden; border: 1px solid #e0e9f0; border-radius: 1.5rem; background: #fff; box-shadow: 0 18px 55px -28px rgb(38 50 56 / .2); }
        #login > figure { display: flex; align-items: center; justify-content: center; margin: 0; padding: 2.5rem; background: #eaf8f5; }
        #login .logo_sticky { width: min(100%, 17rem); height: auto; object-fit: contain; }
        #login > aside { display: flex; flex-direction: column; justify-content: center; padding: clamp(1.5rem, 5vw, 3.5rem); }
        #login h1 { margin: 0 0 2rem; color: #263238; font-size: clamp(1.6rem, 4vw, 2.1rem); font-weight: 700; }
        #login .form-group { position: relative; margin-bottom: 1.25rem; }
        #login .form-control { display: block; width: 100%; min-height: 3.1rem; padding: .8rem 1rem; border: 1px solid #e0e9f0; border-radius: .65rem; background: #fff; color: #263238; font: inherit; outline: none; transition: border-color .18s, box-shadow .18s; }
        #login .form-control:focus { border-color: #168a80; box-shadow: 0 0 0 4px rgb(22 138 128 / .12); }
        #login .form-control.is-invalid { border-color: #b42318; }
        #login .icon_mail_alt, #login .icon_lock_alt { position: absolute; top: 1rem; right: 1rem; color: #168a80; }
        #login .clearfix { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin: .25rem 0 1.5rem; }
        #login .container_check { display: inline-flex; align-items: center; gap: .5rem; color: #475569; font-size: .88rem; }
        #login .container_check input { accent-color: #168a80; }
        #login a { color: #168a80; font-weight: 600; text-decoration: none; }
        #login a:hover { color: #11776f; text-decoration: underline; }
        #login .text-muted { display: block; margin-top: .5rem; color: #64748b; font-size: .8rem; line-height: 1.5; }
        #login .btn_1 { display: inline-flex; min-height: 3rem; align-items: center; justify-content: center; width: 100%; margin-top: .5rem; padding: .75rem 1.2rem; border: 0; border-radius: .65rem; background: #168a80; color: #fff; font: inherit; font-weight: 650; cursor: pointer; transition: background .18s, transform .18s; }
        #login .btn_1:hover:not(:disabled) { background: #11776f; transform: translateY(-1px); }
        #login .btn_1:disabled { opacity: .6; cursor: wait; }
        #login .add_top_10 { margin-top: 1.25rem; color: #475569; font-size: .9rem; }
        #login .copy { margin-top: 1.5rem; color: #64748b; font-size: .8rem; }
        .yt-modal { position: fixed; z-index: 100; inset: 0; display: none; align-items: center; justify-content: center; padding: 1rem; background: rgb(38 50 56 / .58); }
        .yt-modal.is-open { display: flex; }
        .yt-modal-panel { display: flex; flex-direction: column; width: min(100%, 36rem); max-height: min(90vh, 52rem); overflow: hidden; border: 1px solid #e0e9f0; border-radius: 1rem; background: #fff; box-shadow: 0 24px 70px -24px rgb(38 50 56 / .3); }
        .yt-modal-panel.wide { width: min(100%, 54rem); }
        .yt-modal-header, .yt-modal-footer { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.25rem; border-bottom: 1px solid #e0e9f0; }
        .yt-modal-footer { justify-content: flex-end; border-top: 1px solid #e0e9f0; border-bottom: 0; }
        .yt-modal-header h2 { margin: 0; color: #263238; font-size: 1.2rem; font-weight: 650; }
        .yt-modal-close { display: inline-flex; width: 2.25rem; height: 2.25rem; align-items: center; justify-content: center; border: 0; border-radius: 9999px; background: #f5fbf9; color: #475569; font-size: 1.25rem; cursor: pointer; }
        .yt-modal-body { overflow-y: auto; padding: 1.25rem; color: #475569; }
        .yt-modal-footer button, .yt-modal-footer a { display: inline-flex; min-height: 2.6rem; align-items: center; justify-content: center; padding: .6rem 1rem; border: 1px solid #e0e9f0; border-radius: .6rem; background: #fff; color: #168a80; font: inherit; font-size: .9rem; font-weight: 600; text-decoration: none; cursor: pointer; }
        .yt-modal-footer .primary { border-color: #168a80; background: #168a80; color: #fff; }
        .yt-modal-footer button:disabled { opacity: .5; cursor: not-allowed; }
        @media (max-width: 700px) { #login { display: flex; flex-direction: column; min-height: auto; margin: 1rem auto; } #login > figure { padding: 1.25rem; } #login .logo_sticky { width: 11rem; max-height: 4rem; } }
    </style>
</head>

<body class="yuktione-auth-page professional-login-page font-sans text-text-primary">
    <div id="login">
        <figure>
            <a href="{{ route('home') }}" aria-label="Yuktione home"><img src="{{ asset('frontend/assets/Yuktione_logo.png') }}" alt="Yuktione" class="logo_sticky"></a>
        </figure>
        <aside>
            <h1 class="text-center">Professional Login</h1>
            <form id="loginForm">
                @csrf

                <div class="form-group">
                    <input type="email" class="form-control" name="email" id="email" placeholder="Email" required>
                    <i class="icon_mail_alt"></i>
                    <div class="invalid-feedback" id="email-error"></div>
                </div>

                <div class="form-group">
                    <input type="password" class="form-control" name="password" id="password" placeholder="Password" required>
                    <i class="icon_lock_alt"></i>
                    <div class="invalid-feedback" id="password-error"></div>
                </div>

                <div class="clearfix add_bottom_30">
                    <div class="checkboxes float-start">
                        <label class="container_check">Remember me
                            <input type="checkbox" name="remember">
                            <span class="checkmark"></span>
                        </label>
                    </div>
                    <div class="float-end">
                        <a id="forgot" href="{{ route('professional.forgot.form') }}">Forgot Password?</a>
                    </div>
                </div>

                <!-- Add Terms and Conditions checkbox -->
                <div class="form-group mb-3">
                    <label class="container_check">I accept the <a href="#" onclick="openTermsModal(event)">Terms and Conditions</a>
                        <input type="checkbox" name="terms_accepted" id="terms_accepted" disabled>
                        <span class="checkmark"></span>
                    </label>
                    <div class="invalid-feedback" id="terms-error">
                        You must read the complete Terms and Conditions to continue.
                    </div>
                    <small class="text-muted">Please read the complete terms and conditions to enable the checkbox.</small>
                </div>

                <button type="submit" class="btn_1 full-width">Login-Professionals</button>

                <div class="text-center add_top_10">
                    New to Yuktione? <strong><a href="{{ route('professional.register') }}">Sign up!</a></strong>
                </div>
            </form>
            <div class="copy text-center">© Yuktione</div>
        </aside>
    </div>

    <div class="yt-modal" id="deactivatedModal" role="dialog" aria-modal="true" aria-labelledby="deactivatedModalLabel" aria-hidden="true">
        <div class="yt-modal-panel">
            <div class="yt-modal-header">
                <h2 id="deactivatedModalLabel">Account Deactivated</h2>
                <button type="button" class="yt-modal-close" data-modal-close aria-label="Close">&times;</button>
            </div>
            <div class="yt-modal-body text-center">
                <div class="mb-4 text-3xl text-amber-600" aria-hidden="true"><i class="fas fa-exclamation-triangle"></i></div>
                <p>Your account has been deactivated by the administrator.</p>
                <p>Please contact support for assistance.</p>
            </div>
            <div class="yt-modal-footer">
                <a href="mailto:info@yuktione.in"><i class="fas fa-envelope me-2"></i>Contact Support</a>
                <button type="button" data-modal-close>Close</button>
            </div>
        </div>
    </div>

    <div class="yt-modal" id="termsModal" role="dialog" aria-modal="true" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="yt-modal-panel wide">
            <div class="yt-modal-header">
                <h2 id="termsModalLabel">Professional Terms and Conditions</h2>
                <button type="button" class="yt-modal-close" data-modal-close aria-label="Close">&times;</button>
            </div>
            <div class="yt-modal-body" id="termsModalBody">
                @include('professional.terms_agreement')
            </div>
            <div class="yt-modal-footer">
                <span class="scroll-indicator mr-auto text-sm text-text-muted">Please scroll to the end of the agreement.</span>
                <button type="button" data-modal-close>Close</button>
                <button type="button" class="primary" id="agreeButton" disabled onclick="agreeToTerms()">I Agree</button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        // CSRF Token Management
        function refreshCSRFToken() {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: '/csrf-token',
                    type: 'GET',
                    timeout: 5000,
                    success: function(data) {
                        if (data && data.csrf_token) {
                            $('meta[name="csrf-token"]').attr('content', data.csrf_token);
                            $('input[name="_token"]').val(data.csrf_token);
                            resolve(data.csrf_token);
                        } else {
                            reject('Invalid CSRF token response');
                        }
                    },
                    error: function() {
                        reject('Failed to refresh CSRF token');
                    }
                });
            });
        }

        // Global AJAX setup for CSRF
        $.ajaxSetup({
            beforeSend: function(xhr, settings) {
                if (!settings.crossDomain && settings.type !== 'GET') {
                    xhr.setRequestHeader('X-CSRF-TOKEN', $('meta[name="csrf-token"]').attr('content'));
                }
            }
        });

        // Terms Modal Functions
        function showModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');
        }

        function hideModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            if (!document.querySelector('.yt-modal.is-open')) {
                document.body.classList.remove('overflow-hidden');
            }
        }

        document.querySelectorAll('[data-modal-close]').forEach((button) => {
            button.addEventListener('click', () => hideModal(button.closest('.yt-modal').id));
        });

        document.querySelectorAll('.yt-modal').forEach((modal) => {
            modal.addEventListener('click', (event) => {
                if (event.target === modal) hideModal(modal.id);
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                document.querySelectorAll('.yt-modal.is-open').forEach((modal) => hideModal(modal.id));
            }
        });

        function updateTermsReadState() {
            const modalBody = document.getElementById('termsModalBody');
            const agreeButton = document.getElementById('agreeButton');
            const reachedBottom = modalBody.scrollTop + modalBody.clientHeight >= modalBody.scrollHeight - 10;

            if (reachedBottom) {
                agreeButton.disabled = false;
                agreeButton.textContent = 'I Agree';
                $('.scroll-indicator').html('<small class="text-success">✓ You have read the complete terms</small>');
            }
        }

        function openTermsModal(event) {
            event.preventDefault();
            showModal('termsModal');
            const modalBody = document.getElementById('termsModalBody');
            const agreeButton = document.getElementById('agreeButton');
            agreeButton.disabled = true;
            agreeButton.textContent = 'Please scroll to bottom';
            modalBody.scrollTop = 0;
            updateTermsReadState();
        }

        function agreeToTerms() {
            $('#terms_accepted').prop('disabled', false).prop('checked', true);
            $('#terms-error').hide();
            hideModal('termsModal');
            toastr.success('Thank you for accepting the Terms and Conditions');
        }

        document.getElementById('termsModalBody').addEventListener('scroll', updateTermsReadState);

        $('#loginForm').submit(function (e) {
            e.preventDefault();

            // Check if terms checkbox is checked
            if (!$('#terms_accepted').is(':checked')) {
                $('#terms-error').show();
                toastr.error('You must read and accept the Terms and Conditions to continue.');
                return false;
            }

            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            $submitBtn.prop('disabled', true).text('Logging in...');

            $.ajax({
                url: "{{ route('professional.store') }}",
                method: "POST",
                data: $form.serialize(),
                success: function (response) {
                    if (response.status === 'rejected') {
                        toastr.warning(response.message || "Your account has been rejected.");
                    } else {
                        toastr.success(response.message || "Login successful!");
                    }

                    if (response.redirect_url) {
                        setTimeout(() => window.location.href = response.redirect_url, 1500);
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 419) {
                        // CSRF token mismatch - refresh token and retry
                        refreshCSRFToken().then(function() {
                            toastr.info('Session refreshed. Please try logging in again.');
                        }).catch(function() {
                            toastr.error('Session expired. Please refresh the page and try again.');
                        });
                    } else if (xhr.status === 403 && xhr.responseJSON.status === 'deactivated') {
                        // Show deactivated account modal
                        showModal('deactivatedModal');
                    } else if (xhr.status === 422) {
                        // Validation errors - show inline
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            // Clear previous errors
                            $('.form-control').removeClass('is-invalid');
                            $('.invalid-feedback').hide();
                            
                            // Show specific field errors
                            $.each(xhr.responseJSON.errors, (key, value) => {
                                const $field = $(`#${key}`);
                                const $error = $(`#${key}-error`);
                                
                                if ($field.length && $error.length) {
                                    $field.addClass('is-invalid');
                                    $error.text(value[0]).show();
                                } else {
                                    toastr.error(value[0]);
                                }
                            });
                        } else {
                            toastr.error('Validation failed. Please check your input.');
                        }
                    } else {
                        // Other errors
                        var errorMessage = 'An error occurred. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        toastr.error(errorMessage);
                    }
                },
                complete: function () {
                    $submitBtn.prop('disabled', false).text('Login-Professionals');
                }
            });
        });

        $(document).ready(function() {
            // Hide error message when checkbox is checked
            $('#terms_accepted').change(function() {
                if($(this).is(':checked')) {
                    $('#terms-error').hide();
                }
            });
            
            // Clear field errors when user starts typing
            $('#email, #password').on('input', function() {
                $(this).removeClass('is-invalid');
                $(`#${this.id}-error`).hide();
            });
            
            // Initially hide all error messages
            $('.invalid-feedback').hide();
        });
    </script>
    <script>
        toastr.options = {
            "positionClass": "toast-top-center",
            "timeOut": "3000",
            "closeButton": true,
            "progressBar": true
        };

        @if (session('success'))
            toastr.success("{{ session('success') }}");
        @endif

        @if (session('error'))
            toastr.error("{{ session('error') }}");
        @endif

        @if (session('warning'))
            toastr.warning("{{ session('warning') }}");
        @endif

        @if (session('info'))
            toastr.info("{{ session('info') }}");
        @endif
    </script>
</body>

</html>