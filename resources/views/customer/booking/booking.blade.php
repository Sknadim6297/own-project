@extends('customer.layout.booking')

@section('title', config('yuktione.name', 'YuktiOne') . ' | Booking Checkout')

@section('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        .login-popup-title { font-size: 1.5rem !important; color: #142B4D !important; }
        .login-popup-title .wave-icon { font-size: 1.8rem !important; color: #36877F !important; }
        .login-popup-custom {
            background: #ffffff !important;
            border: 1px solid #E2E9EE !important;
            border-radius: 1.25rem !important;
            color: #475569 !important;
            padding: 25px 20px !important;
        }
        .login-popup-btn {
            background-color: #36877F !important;
            border: none !important;
            border-radius: 0.75rem !important;
            box-shadow: none !important;
            color: white !important;
            font-size: 1rem !important;
            font-weight: 600 !important;
            padding: 12px 30px !important;
        }
        .login-popup-btn:hover { background-color: #286E67 !important; box-shadow: none !important; transform: none !important; }
        .login-popup-close { color: #718096 !important; font-size: 1.5rem !important; opacity: 1 !important; }
        .login-popup-close:hover { color: #142B4D !important; }
    </style>
@endsection

@section('content')
<div class="yk-booking-shell mx-auto grid max-w-3xl gap-6">
    <x-tazen-v2.panel.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('user.dashboard')],
        ['label' => 'Booking Checkout'],
    ]" />

    <header>
        <p class="text-sm font-semibold uppercase tracking-wide text-brand-primary">Consultations</p>
        <h1 class="mt-1 font-display text-page-title font-semibold text-brand-secondary">Booking Checkout</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-text-secondary sm:text-base">
            Confirm your consultation details, add a contact number, and complete payment securely.
        </p>
    </header>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    @if(session()->has('booking_data'))
        @php
            $bookingData = session('booking_data');
            $professionalId = $bookingData['professional_id'] ?? null;
            $professional = \App\Models\Professional::with('profile')->where('id', $professionalId)->first();

            $professionalService = null;
            $serviceName = 'N/A';
            $subServiceName = null;

            if ($professional) {
                $professionalService = \App\Models\ProfessionalService::with('service')
                    ->where('professional_id', $professional->id)
                    ->first();

                if ($professionalService) {
                    if ($professionalService->service && $professionalService->service->name) {
                        $serviceName = $professionalService->service->name;
                    } elseif ($professionalService->service_name) {
                        $serviceName = $professionalService->service_name;
                    }
                }
            }

            if ($serviceName === 'N/A') {
                $serviceName = session('selected_service_name') ?? 'N/A';
            }

            if (!empty($bookingData['sub_service_name'])) {
                $subServiceName = $bookingData['sub_service_name'];
            } elseif (!empty($bookingData['sub_service_id'])) {
                $subService = \App\Models\SubService::find($bookingData['sub_service_id']);
                if ($subService) {
                    $subServiceName = $subService->name;
                }
            } else {
                $subServiceName = session('selected_sub_service_name');
            }

            $baseAmount = $bookingData['total_amount'] ?? 0;
            $cgst = $baseAmount * 0.09;
            $sgst = $baseAmount * 0.09;
            $igst = 0;
            $totalWithGst = $baseAmount + $cgst + $sgst + $igst;
            $user = Auth::guard('user')->user();
        @endphp

        <article class="yk-booking-card box_booking_2">
            <div class="yk-booking-card__head head">
                <div class="title">
                    <p class="text-xs font-semibold uppercase tracking-wide text-brand-primary">Signed in as</p>
                    <h3>{{ $user->name ?? 'N/A' }}</h3>
                    <p>{{ $user->email ?? 'N/A' }}</p>
                </div>
            </div>

            <div class="yk-booking-card__body main">
                <h2 class="mb-4 text-base font-semibold text-brand-secondary">Booking Summary</h2>

                <dl class="yk-booking-dl">
                    <div>
                        <dt>Date &amp; time</dt>
                        <dd>
                            @if(!empty($bookingData['bookings']))
                                <ul class="m-0 list-none space-y-1 p-0 text-right">
                                    @foreach($bookingData['bookings'] as $booking)
                                        <li>{{ \Carbon\Carbon::parse($booking['date'])->format('d M Y') }} · {{ $booking['time_slot'] ?? 'N/A' }}</li>
                                    @endforeach
                                </ul>
                            @else
                                No bookings found.
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt>Service</dt>
                        <dd>{{ $serviceName }}</dd>
                    </div>
                    @if(!empty($subServiceName))
                        <div>
                            <dt>Sub-service</dt>
                            <dd>{{ $subServiceName }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt>Plan type</dt>
                        <dd>{{ isset($bookingData['plan_type']) ? ucwords(str_replace('_', ' ', $bookingData['plan_type'])) : 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt>Type</dt>
                        <dd>Appointment</dd>
                    </div>
                    <div>
                        <dt>Base amount</dt>
                        <dd>{{ getCurrencySymbol() }}{{ number_format(convertCurrency($baseAmount), 2) }}</dd>
                    </div>
                    <div>
                        <dt>CGST (9%)</dt>
                        <dd>{{ getCurrencySymbol() }}{{ number_format(convertCurrency($cgst), 2) }}</dd>
                    </div>
                    <div>
                        <dt>SGST (9%)</dt>
                        <dd>{{ getCurrencySymbol() }}{{ number_format(convertCurrency($sgst), 2) }}</dd>
                    </div>
                    <div>
                        <dt>IGST (0%)</dt>
                        <dd>{{ getCurrencySymbol() }}{{ number_format(convertCurrency($igst), 2) }}</dd>
                    </div>
                    <div>
                        <dt>Total (incl. GST)</dt>
                        <dd>{{ getCurrencySymbol() }}{{ number_format(convertCurrency($totalWithGst), 2) }}</dd>
                    </div>
                    <div>
                        <dt>Professional</dt>
                        <dd>{{ $professional['name'] ?? 'Booking Details' }}</dd>
                    </div>
                    <div>
                        <dt>Address</dt>
                        <dd>{{ $professional->profile->address ?? 'Booking Address is not available' }}</dd>
                    </div>
                </dl>

                <div class="mt-6 rounded-panel border border-border bg-surface-muted/40 p-4">
                    <h6 class="mb-2 text-sm font-semibold text-brand-secondary">Enter contact number for the booking</h6>
                    <div class="form-group add_bottom_15 yk-booking-phone relative">
                        <input type="tel" class="form-control" id="phone" name="phone" placeholder="10-digit mobile number" maxlength="10" pattern="\d{10}" autocomplete="tel" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)">
                        <i class="icon_phone" aria-hidden="true"></i>
                    </div>
                    <p class="mt-2 text-xs text-text-muted">
                        Support: {{ config('yuktione.email', 'info@yuktione.in') }} · {{ config('yuktione.phone_display', '+91 96050 08040') }}
                    </p>
                </div>

                <div class="yk-booking-actions">
                    <a href="javascript:void(0);" class="btn_1 full-width mb_5 booking">Book Now</a>
                    <a href="{{ route('user.reset-booking') }}" class="btn_1 full-width outline mb_25">Change Booking</a>
                </div>
            </div>
        </article>
    @else
        <article class="yk-booking-card">
            <div class="yk-booking-card__body main text-center">
                <h2 class="font-display text-lg font-semibold text-brand-secondary">No booking data available</h2>
                <p class="mt-2 text-sm text-text-secondary">Select a service and professional before you can proceed with booking.</p>
                <div class="yk-booking-actions mt-6">
                    <a href="{{ route('professionals') }}" class="btn_1">View Professionals</a>
                    <a href="{{ route('user.dashboard') }}" class="btn_1 outline">Back to Dashboard</a>
                </div>
            </div>
        </article>
    @endif
</div>
@endsection

@section('script')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Check if booking elements exist before initializing
    const bookingBtn = document.querySelector('.booking');
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    
    if (!bookingBtn || !csrfToken) {
        console.log('Booking elements not found - user may not have booking data');
        return;
    }
    
    const csrfTokenValue = csrfToken.getAttribute('content');

    if (bookingBtn) {
        bookingBtn.addEventListener('click', function (e) {
            e.preventDefault();

            const phoneElement = document.getElementById('phone');
            if (!phoneElement) {
                console.error('Phone input element not found');
                return;
            }
            
            const phone = phoneElement.value;

            if (!phone || phone.length !== 10) {
                if (typeof toastr !== 'undefined') {
                    toastr.warning('Please enter a valid 10-digit phone number.');
                } else {
                    alert('Please enter a valid 10-digit phone number.');
                }
                return;
            }

            // Show loading state on button
            const originalText = bookingBtn.innerHTML;
            bookingBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';
            bookingBtn.disabled = true;

            // First, initiate payment
            fetch("{{ route('user.booking.initiate-payment') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfTokenValue,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ phone: phone })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Reset button before showing Razorpay
                    bookingBtn.innerHTML = originalText;
                    bookingBtn.disabled = false;
                    
                    // Initialize Razorpay
                    const options = {
                        key: data.key,
                        amount: data.amount,
                        currency: "INR",
                        name: @json(config('yuktione.name', 'YuktiOne')),
                        description: "Booking Payment",
                        order_id: data.order_id,
                        handler: function (response) {
                            console.debug('PAYMENT: Razorpay payment handler called', response);
                            
                            // Show immediate loader when payment succeeds
                            Swal.fire({
                                title: 'Payment Successful!',
                                text: 'Processing your booking...',
                                icon: 'success',
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                showConfirmButton: false,
                                showClass: {
                                    popup: 'animate__animated animate__fadeInDown animate__faster'
                                },
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });
                            
                            console.debug('PAYMENT: Starting payment verification...');
                            
                            // Verify payment
                            fetch("{{ route('user.booking.verify-payment') }}", {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfTokenValue,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    razorpay_payment_id: response.razorpay_payment_id,
                                    razorpay_order_id: response.razorpay_order_id,
                                    razorpay_signature: response.razorpay_signature
                                })
                            })
                            .then(res => {
                                console.debug('PAYMENT: Raw verification response:', res);
                                return res.json();
                            })
                            .then(data => {
                                console.debug('PAYMENT: Payment verification response:', data);
                                if (data.status === 'success') {
                                    console.debug('PAYMENT: Verification successful, checking MCQ questions...');
                                    console.debug('PAYMENT: Response data:', JSON.stringify(data));
                                    
                                    // Important: Close the payment processing loader first
                                    Swal.close();
                                    
                                    // Wait a brief moment before starting MCQ flow to ensure clean state
                                    setTimeout(() => {
                                        try {
                                            // Check for MCQ questions after successful payment
                                            checkAndShowMCQQuestions(data);
                                        } catch (mcqError) {
                                            console.error('PAYMENT: MCQ check failed, using emergency fallback:', mcqError);
                                            // Emergency fallback - show success modal then redirect
                                            Swal.fire({
                                                title: 'Booking Confirmed!',
                                                text: 'Your payment was successful and booking is confirmed.',
                                                icon: 'success',
                                                timer: 3000,
                                                timerProgressBar: true,
                                                allowOutsideClick: false,
                                                showConfirmButton: false
                                            }).then(() => {
                                                console.debug('PAYMENT: Emergency redirect to:', data.redirect_url);
                                                window.location.href = data.redirect_url || "{{ route('user.booking.success') }}";
                                            });
                                        }
                                    }, 300); // Small delay for clean state transition
                                } else {
                                    console.error('PAYMENT: Verification failed:', data);
                                    // Close the loader before showing error
                                    Swal.close();
                                    
                                    // Handle authentication errors in payment verification
                                    if (data.redirect) {
                                        Swal.fire({
                                            title: 'Session Expired',
                                            text: data.message,
                                            icon: 'warning',
                                            confirmButtonText: 'Login Again',
                                            confirmButtonColor: '#36877F'
                                        }).then(() => {
                                            window.location.href = data.redirect;
                                        });
                                    } else {
                                        if (typeof toastr !== 'undefined') {
                                            toastr.error(data.message);
                                        } else {
                                            alert(data.message);
                                        }
                                    }
                                }
                            })
                            .catch(error => {
                                console.error('PAYMENT: Payment verification error:', error);
                                console.error('PAYMENT: Error details:', error.message, error.stack);
                                
                                // Close the loader before showing error
                                Swal.close();
                                
                                if (error.response) {
                                    error.response.json().then(errorData => {
                                        console.error('PAYMENT: Server error response:', errorData);
                                        if (errorData.redirect) {
                                            Swal.fire({
                                                title: '👋 Hey! You forgot to login',
                                                text: '',
                                                showCloseButton: true,
                                                showCancelButton: false,
                                                confirmButtonText: 'Login',
                                                confirmButtonColor: '#3085d6'
                                            }).then(() => {
                                                window.location.href = errorData.redirect;
                                            });
                                        } else {
                                            if (typeof toastr !== 'undefined') {
                                                toastr.error(errorData.message || 'Payment verification failed');
                                            } else {
                                                alert(errorData.message || 'Payment verification failed');
                                            }
                                        }
                                    }).catch(() => {
                                        if (typeof toastr !== 'undefined') {
                                            toastr.error('Payment verification failed');
                                        } else {
                                            alert('Payment verification failed');
                                        }
                                    });
                                } else {
                                    if (typeof toastr !== 'undefined') {
                                        toastr.error('Payment verification failed');
                                    } else {
                                        alert('Payment verification failed');
                                    }
                                }
                            });
                        },
                        prefill: {
                            name: data.name,
                            email: data.email,
                            contact: data.phone
                        },
                        theme: {
                            color: "#36877F"
                        }
                    };
                    const rzp = new Razorpay(options);
                    
                    // Show loading state when opening Razorpay
                    rzp.on('payment.created', function(response) {
                        console.log('Payment created:', response);
                    });
                    
                    // Show brief loading when opening payment modal
                    setTimeout(() => {
                        rzp.open();
                    }, 300); // Small delay to show button feedback
                    
                    // Enhanced payment failure handling
                    rzp.on('payment.failed', function (response) {
                        // Log failure and send notifications
                        fetch("{{ route('user.booking.payment.failed') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfTokenValue,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                razorpay_payment_id: response.error.metadata?.payment_id || null,
                                razorpay_order_id: response.error.metadata?.order_id || null,
                                error_code: response.error.code,
                                error_description: response.error.description,
                                error_source: response.error.source,
                                error_step: response.error.step,
                                error_reason: response.error.reason,
                                send_notifications: true
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            showAppointmentPaymentFailureDialog(response.error, data);
                        })
                        .catch((error) => {
                            console.error('Error logging payment failure:', error);
                            showAppointmentPaymentFailureDialog(response.error, null);
                        });
                    });
                } else {
                    // Reset button before showing error
                    bookingBtn.innerHTML = originalText;
                    bookingBtn.disabled = false;
                    
                    // Handle authentication errors specifically
                    if (data.redirect) {
                        Swal.fire({
                            title: '<span class="login-popup-title"><span class="wave-icon">👋</span> Hey! You forgot to login</span>',
                            text: '',
                            showCloseButton: true,
                            showCancelButton: false,
                            confirmButtonText: 'Login',
                            customClass: {
                                popup: 'login-popup-custom',
                                confirmButton: 'login-popup-btn',
                                closeButton: 'login-popup-close'
                            },
                            confirmButtonColor: '#36877F'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = data.redirect;
                            }
                        });
                    } else {
                        toastr.error(data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Payment initiation error:', error);
                
                // Reset button before showing error
                bookingBtn.innerHTML = originalText;
                bookingBtn.disabled = false;
                
                // Handle different types of errors
                if (error.response) {
                    // Server responded with error status
                    error.response.json().then(errorData => {
                        if (errorData.redirect) {
                            Swal.fire({
                                title: '👋 Hey! You forgot to login',
                                text: '',
                                showCloseButton: true,
                                showCancelButton: false,
                                confirmButtonText: 'Login',
                                confirmButtonColor: '#3085d6'
                            }).then(() => {
                                window.location.href = errorData.redirect;
                            });
                        } else {
                            toastr.error(errorData.message || 'Payment initiation failed');
                        }
                    }).catch(() => {
                        toastr.error('Payment initiation failed. Please try again.');
                    });
                } else {
                    // Network or other errors
                    Swal.fire({
                        title: 'Connection Error',
                        text: 'Unable to connect to payment server. Please check your internet connection and try again.',
                        icon: 'error',
                        confirmButtonText: 'Try Again',
                        confirmButtonColor: '#dc3545'
                    });
                }
            });
        });
    }

    // Enhanced appointment payment failure handling functions
    function showAppointmentPaymentFailureDialog(error, logData) {
        const errorMessage = getAppointmentErrorMessage(error.code, error.description);
        const isRetryable = isAppointmentRetryableError(error.code);
        
        Swal.fire({
            title: 'Appointment Payment Failed',
            html: `
                <div style="text-align: left; margin: 20px 0;">
                    <p style="margin-bottom: 15px;"><strong>Reason:</strong> ${errorMessage}</p>
                    ${logData?.reference_id ? `<p style="margin-bottom: 15px;"><strong>Reference ID:</strong> ${logData.reference_id}</p>` : ''}
                    <p style="margin-bottom: 15px; color: #666; font-size: 14px;">
                        <i class="fa fa-shield"></i> No amount has been deducted from your account.
                    </p>
                    ${logData?.notifications_sent ? 
                        '<p style="margin-bottom: 15px; color: #36877F; font-size: 14px;"><i class="fa fa-check"></i> Your assigned professional and our support team have been notified.</p>' : 
                        ''
                    }
                </div>
            `,
            icon: 'error',
            showCancelButton: true,
            confirmButtonText: isRetryable ? '<i class="fa fa-refresh"></i> Retry Payment' : '<i class="fa fa-phone"></i> Contact Support',
            cancelButtonText: '<i class="fa fa-arrow-left"></i> Modify Booking',
            confirmButtonColor: isRetryable ? '#dc3545' : '#36877F',
            cancelButtonColor: '#6c757d',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                if (isRetryable) {
                    // Show loading and retry payment
                    Swal.fire({
                        title: 'Retrying Payment...',
                        text: 'Please wait while we process your payment.',
                        icon: 'info',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });
                    
                    setTimeout(() => {
                        Swal.close();
                        bookingBtn.click();
                    }, 1500);
                } else {
                    showAppointmentContactSupportDialog();
                }
            } else {
                // Go back to modify booking
                window.location.href = "{{ route('user.reset-booking') }}";
            }
        });
    }

    function showAppointmentContactSupportDialog() {
        Swal.fire({
            title: 'Get Help with Your Appointment',
            html: `
                <div style="text-align: left; margin: 20px 0;">
                    <p style="margin-bottom: 15px;">Our team will help you complete your appointment booking.</p>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;">
                        <p style="margin: 5px 0;"><strong><i class="fa fa-phone"></i> Call:</strong> {{ config('yuktione.phone_display', '+91 96050 08040') }}</p>
                        <p style="margin: 5px 0;"><strong><i class="fa fa-envelope"></i> Email:</strong> {{ config('yuktione.email', 'info@yuktione.in') }}</p>
                        <p style="margin: 5px 0;"><strong><i class="fa fa-clock"></i> Hours:</strong> {{ config('yuktione.hours', 'Monday to Friday, 9am–6pm. Saturday, 9am–2pm.') }}</p>
                    </div>
                    <p style="margin-bottom: 15px; color: #36877F; font-size: 14px;">
                        <i class="fa fa-user-md"></i> Your assigned professional has been notified about the payment issue.
                    </p>
                </div>
            `,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: '<i class="fa fa-refresh"></i> Try Again',
            cancelButtonText: '<i class="fa fa-home"></i> Go to Dashboard',
            confirmButtonColor: '#36877F',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                bookingBtn.click();
            } else {
                window.location.href = "{{ route('user.dashboard') }}";
            }
        });
    }

    function getAppointmentErrorMessage(errorCode, errorDescription) {
        const errorMessages = {
            'BAD_REQUEST_ERROR': 'Invalid payment details. Please verify your information.',
            'GATEWAY_ERROR': 'Payment gateway temporarily unavailable. Please try again.',
            'NETWORK_ERROR': 'Network connection issue. Please check your internet.',
            'SERVER_ERROR': 'Server error. Please try again after some time.',
            'PAYMENT_DECLINED': 'Payment declined by bank. Contact your bank or try another method.',
            'INSUFFICIENT_FUNDS': 'Insufficient funds. Please check your account balance.',
            'INVALID_CARD': 'Invalid card details. Please check your card information.',
            'CARD_EXPIRED': 'Card has expired. Please use a different card.',
            'CVV_MISMATCH': 'CVV mismatch. Please check your security code.',
            'OTP_VERIFICATION_FAILED': 'OTP verification failed. Please try again.',
            'PAYMENT_CANCELLED': 'Payment was cancelled.',
            'PAYMENT_TIMEOUT': 'Payment session timed out. Please try again.'
        };

        return errorMessages[errorCode] || errorDescription || 'An unexpected error occurred.';
    }

    function isAppointmentRetryableError(errorCode) {
        const retryableErrors = [
            'NETWORK_ERROR',
            'SERVER_ERROR', 
            'GATEWAY_ERROR',
            'PAYMENT_TIMEOUT',
            'OTP_VERIFICATION_FAILED'
        ];
        
        return retryableErrors.includes(errorCode);
    }

    // MCQ Functions
    function checkAndShowMCQQuestions(bookingData) {
        console.debug('MCQ: checkAndShowMCQQuestions called', bookingData);
        console.debug('MCQ: bookingData keys:', Object.keys(bookingData));
        console.debug('MCQ: professional_id:', bookingData.professional_id);
        console.debug('MCQ: redirect_url:', bookingData.redirect_url);
        
        // Use professional_id from the payment response instead of session
        if (!bookingData.professional_id) {
            console.debug('MCQ: No professional_id in response, proceeding to success page');
            // No professional_id in response, proceed to success page
            proceedToSuccessPage(bookingData);
            return;
        }

        console.debug('MCQ: Fetching MCQ questions for professional_id:', bookingData.professional_id);
        // Fetch MCQ questions for the service
        fetch("{{ route('get.mcq.questions') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfTokenValue,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                professional_id: bookingData.professional_id
            })
        })
        .then(res => {
            console.debug('MCQ: MCQ fetch response status:', res.status);
            if (!res.ok) {
                throw new Error(`HTTP ${res.status}: ${res.statusText}`);
            }
            return res.json();
        })
        .then(data => {
            console.debug('MCQ: MCQ fetch response data:', data);
            if (data.status === 'success' && data.mcq_questions && data.mcq_questions.length > 0) {
                console.debug('MCQ: Found', data.mcq_questions.length, 'MCQ questions, showing modal');
                // Show MCQ modal
                showMCQModal(data.mcq_questions, data.service_id, bookingData);
            } else {
                console.debug('MCQ: No MCQ questions found, proceeding to success page');
                // No MCQ questions, show success modal and proceed
                showSuccessModal('Booking confirmed successfully!');
                setTimeout(() => {
                    proceedToSuccessPage(bookingData);
                }, 2000);
            }
        })
        .catch(error => {
            console.error('MCQ: Error fetching MCQ questions:', error);
            console.error('MCQ: Full error details:', error.message, error.stack);
            // Show default success modal and proceed to success page
            showSuccessModal('Booking confirmed successfully!');
            setTimeout(() => {
                console.debug('MCQ: Error fallback - proceeding to success page');
                proceedToSuccessPage(bookingData);
            }, 2000);
        });
    }

    function showSuccessModal(message) {
        console.debug('MCQ: showSuccessModal called with message:', message);
        Swal.fire({
            title: 'Success!',
            text: message,
            icon: 'success',
            timer: 3000,
            timerProgressBar: true,
            allowOutsideClick: false,
            showConfirmButton: false,
            showClass: {
                popup: 'animate__animated animate__bounceIn animate__faster'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp animate__faster'
            }
        });
    }

    function proceedToSuccessPage(bookingData) {
        console.debug('MCQ: proceedToSuccessPage called', bookingData);
        console.debug('MCQ: redirect_url:', bookingData.redirect_url);
        
        // Ensure we have a redirect URL
        const redirectUrl = bookingData.redirect_url || "{{ route('user.booking.success') }}";
        console.debug('MCQ: Final redirect URL:', redirectUrl);
        
        // Show final success message before redirect
        Swal.fire({
            title: 'Booking Confirmed!',
            text: 'Redirecting to success page...',
            icon: 'success',
            timer: 2000,
            timerProgressBar: true,
            allowOutsideClick: false,
            showConfirmButton: false,
            showClass: {
                popup: 'animate__animated animate__bounceIn animate__faster'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp animate__faster'
            }
        }).then(() => {
            console.debug('MCQ: Executing redirect to:', redirectUrl);
            // Force redirect
            window.location.href = redirectUrl;
        });
    }

    let currentQuestionIndex = 0;
    let mcqQuestions = [];
    let mcqAnswers = [];
    let mcqServiceId = null;
    let mcqBookingData = null;

    function showMCQModal(questions, serviceId, bookingData) {
        console.debug('MCQ: showMCQModal called', { questionsCount: (questions || []).length, serviceId, bookingData });
        mcqQuestions = questions;
        mcqServiceId = serviceId;
        mcqBookingData = bookingData;
        currentQuestionIndex = 0;
        mcqAnswers = [];

        // Create modal HTML
        const modalHtml = `
            <div class="modal fade" id="mcqModal" tabindex="-1" role="dialog" aria-labelledby="mcqModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="mcqModalLabel">
                                <i class="fa fa-clipboard-list"></i> Service Feedback Questions
                            </h5>
                        </div>
                        <div class="modal-body">
                            <div class="progress mb-4">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                     role="progressbar" style="width: 0%" id="mcqProgress">
                                </div>
                            </div>
                            <div id="mcqQuestionContainer">
                                <!-- Question content will be dynamically inserted here -->
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" id="mcqSkipBtn">
                                <i class="fa fa-skip-forward"></i> Skip Questions
                            </button>
                            <div class="navigation-buttons">
                                <button type="button" class="btn btn-secondary" id="mcqPrevBtn" style="display: none;">
                                    <i class="fa fa-arrow-left"></i> Previous
                                </button>
                                <button type="button" class="btn btn-primary" id="mcqNextBtn">
                                    Next <i class="fa fa-arrow-right"></i>
                                </button>
                                <button type="button" class="btn btn-success" id="mcqSubmitBtn" style="display: none;">
                                    <i class="fa fa-check"></i> Submit Answers
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal if any
        const existingModal = document.getElementById('mcqModal');
        if (existingModal) {
            existingModal.remove();
        }

        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // Show first question
        showCurrentQuestion();

        // Add event listeners for buttons
        document.getElementById('mcqPrevBtn').addEventListener('click', previousQuestion);
        document.getElementById('mcqNextBtn').addEventListener('click', nextQuestion);
        document.getElementById('mcqSubmitBtn').addEventListener('click', submitMCQAnswers);
        document.getElementById('mcqSkipBtn').addEventListener('click', skipMCQQuestions);

        // Show modal - try different modal initialization methods
        const modalElement = document.getElementById('mcqModal');
        let modal;
        
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            // Bootstrap 5
            modal = new bootstrap.Modal(modalElement);
        } else if (typeof $ !== 'undefined' && $.fn.modal) {
            // Bootstrap 4 with jQuery
            $(modalElement).modal('show');
            console.debug('MCQ: jQuery modal shown');
            return;
        } else {
            // Fallback - manual show
            modalElement.style.display = 'block';
            modalElement.classList.add('show');
            document.body.classList.add('modal-open');
            console.debug('MCQ: fallback modal shown');
            return;
        }
        
        modal.show();
        console.debug('MCQ: bootstrap modal shown');
    }

    function showCurrentQuestion() {
        if (currentQuestionIndex >= mcqQuestions.length) return;

        const question = mcqQuestions[currentQuestionIndex];
        const progress = ((currentQuestionIndex + 1) / mcqQuestions.length) * 100;
        
        // Update progress bar
        document.getElementById('mcqProgress').style.width = progress + '%';
        document.getElementById('mcqProgress').textContent = `${currentQuestionIndex + 1} of ${mcqQuestions.length}`;

        let questionHtml = `
            <div class="question-card">
                <h6 class="mb-3">
                    <span class="badge bg-primary me-2">${currentQuestionIndex + 1}</span>
                    ${question.question}
                </h6>
        `;

        if (question.question_type === 'mcq') {
            questionHtml += '<div class="options-container">';
            
            if (question.formatted_options && Array.isArray(question.formatted_options)) {
                question.formatted_options.forEach((option, index) => {
                    questionHtml += `
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="mcq_answer_${question.id}" 
                                   id="option_${question.id}_${index}" value="${option}">
                            <label class="form-check-label" for="option_${question.id}_${index}">
                                ${option}
                            </label>
                        </div>
                    `;
                });
            }

            if (question.has_other_option) {
                questionHtml += `
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="mcq_answer_${question.id}" 
                               id="option_${question.id}_other" value="Other">
                        <label class="form-check-label" for="option_${question.id}_other">
                            Other
                        </label>
                    </div>
                    <div class="mt-2" id="other_text_${question.id}" style="display: none;">
                        <input type="text" class="form-control" placeholder="Please specify..." 
                               id="other_input_${question.id}">
                    </div>
                `;
            }

            questionHtml += '</div>';
        } else {
            // Text question
            questionHtml += `
                <div class="form-group">
                    <textarea class="form-control" rows="4" id="text_answer_${question.id}" 
                              placeholder="Please enter your answer..."></textarea>
                </div>
            `;
        }

        questionHtml += '</div>';

        document.getElementById('mcqQuestionContainer').innerHTML = questionHtml;

        // Add event listener for "Other" option if it exists
        if (question.has_other_option) {
            const otherRadio = document.getElementById(`option_${question.id}_other`);
            const otherTextDiv = document.getElementById(`other_text_${question.id}`);
            const allRadios = document.querySelectorAll(`input[name="mcq_answer_${question.id}"]`);

            allRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.value === 'Other') {
                        otherTextDiv.style.display = 'block';
                    } else {
                        otherTextDiv.style.display = 'none';
                    }
                });
            });
        }

        // Update button visibility
        updateMCQButtons();

        // Restore previous answer if exists
        if (mcqAnswers[currentQuestionIndex]) {
            const savedAnswer = mcqAnswers[currentQuestionIndex];
            if (question.question_type === 'mcq') {
                const radio = document.querySelector(`input[name="mcq_answer_${question.id}"][value="${savedAnswer.answer}"]`);
                if (radio) {
                    radio.checked = true;
                    if (savedAnswer.answer === 'Other' && savedAnswer.other_answer) {
                        document.getElementById(`other_text_${question.id}`).style.display = 'block';
                        document.getElementById(`other_input_${question.id}`).value = savedAnswer.other_answer;
                    }
                }
            } else {
                document.getElementById(`text_answer_${question.id}`).value = savedAnswer.answer;
            }
        }
    }

    function updateMCQButtons() {
        const prevBtn = document.getElementById('mcqPrevBtn');
        const nextBtn = document.getElementById('mcqNextBtn');
        const submitBtn = document.getElementById('mcqSubmitBtn');

        // Show/hide previous button
        prevBtn.style.display = currentQuestionIndex > 0 ? 'inline-block' : 'none';

        // Show/hide next and submit buttons
        if (currentQuestionIndex === mcqQuestions.length - 1) {
            nextBtn.style.display = 'none';
            submitBtn.style.display = 'inline-block';
        } else {
            nextBtn.style.display = 'inline-block';
            submitBtn.style.display = 'none';
        }
    }

    function previousQuestion() {
        console.log('Previous button clicked, current index:', currentQuestionIndex);
        if (currentQuestionIndex > 0) {
            saveCurrentAnswer();
            currentQuestionIndex--;
            showCurrentQuestion();
        }
    }

    function nextQuestion() {
        console.log('Next button clicked, current index:', currentQuestionIndex);
        if (currentQuestionIndex < mcqQuestions.length - 1) {
            if (saveCurrentAnswer()) {
                currentQuestionIndex++;
                showCurrentQuestion();
            }
        }
    }

    function saveCurrentAnswer() {
        const question = mcqQuestions[currentQuestionIndex];
        console.log('Saving answer for question:', question);
        let answer = '';
        let otherAnswer = '';

        if (question.question_type === 'mcq') {
            const selectedRadio = document.querySelector(`input[name="mcq_answer_${question.id}"]:checked`);
            console.log('Selected radio:', selectedRadio);
            if (!selectedRadio) {
                if (typeof toastr !== 'undefined') {
                    toastr.warning('Please select an answer before proceeding.');
                } else {
                    alert('Please select an answer before proceeding.');
                }
                return false;
            }
            
            answer = selectedRadio.value;
            
            if (answer === 'Other') {
                const otherInput = document.getElementById(`other_input_${question.id}`);
                if (!otherInput || !otherInput.value.trim()) {
                    if (typeof toastr !== 'undefined') {
                        toastr.warning('Please specify your other answer.');
                    } else {
                        alert('Please specify your other answer.');
                    }
                    return false;
                }
                otherAnswer = otherInput.value.trim();
            }
        } else {
            const textArea = document.getElementById(`text_answer_${question.id}`);
            if (!textArea || !textArea.value.trim()) {
                if (typeof toastr !== 'undefined') {
                    toastr.warning('Please enter your answer before proceeding.');
                } else {
                    alert('Please enter your answer before proceeding.');
                }
                return false;
            }
            answer = textArea.value.trim();
        }

        // Save or update answer
        mcqAnswers[currentQuestionIndex] = {
            mcq_id: question.id,
            question: question.question,
            answer: answer,
            other_answer: otherAnswer
        };

        console.log('Answer saved:', mcqAnswers[currentQuestionIndex]);
        return true;
    }

    function submitMCQAnswers() {
        // Save the last answer
        if (!saveCurrentAnswer()) {
            return;
        }

        // Show loading
        const submitBtn = document.getElementById('mcqSubmitBtn');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Submitting...';
        submitBtn.disabled = true;

        // Submit answers
        fetch("{{ route('user.mcq.submit') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfTokenValue,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                service_id: mcqServiceId,
                answers: mcqAnswers
            })
        })
        .then(res => res.json())
        .then(data => {
            console.debug('MCQ: Submit MCQ response:', data);
            if (data.status === 'success') {
                console.debug('MCQ: submitMCQAnswers successful response', data);
                // Close MCQ modal
                const modalElement = document.getElementById('mcqModal');
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) modal.hide();
                } else if (typeof $ !== 'undefined' && $.fn.modal) {
                    $(modalElement).modal('hide');
                } else {
                    modalElement.style.display = 'none';
                    modalElement.classList.remove('show');
                    document.body.classList.remove('modal-open');
                    // Remove modal backdrop if exists
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) backdrop.remove();
                }
                
                // Show thank you message and proceed to success page
                Swal.fire({
                    title: 'Thank You!',
                    text: 'Your feedback has been submitted successfully.',
                    icon: 'success',
                    timer: 2000,
                    timerProgressBar: true,
                    allowOutsideClick: false,
                    showConfirmButton: false
                }).then(() => {
                    console.debug('MCQ: proceeding to success page with mcqBookingData', mcqBookingData);
                    proceedToSuccessPage(mcqBookingData);
                });
            } else {
                console.error('MCQ: Failed to submit answers:', data.message);
                // Reset button and show error
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                if (typeof toastr !== 'undefined') {
                    toastr.error(data.message || 'Failed to submit answers. Please try again.');
                } else {
                    alert(data.message || 'Failed to submit answers. Please try again.');
                }
            }
        })
        .catch(error => {
            console.error('MCQ: Error submitting MCQ answers:', error);
            console.error('MCQ: Full submission error details:', error.message, error.stack);
            // Reset button and show error
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            if (typeof toastr !== 'undefined') {
                toastr.error('Failed to submit answers. Please try again.');
            } else {
                alert('Failed to submit answers. Please try again.');
            }
            // Alternatively, proceed to success page anyway after showing error
            // setTimeout(() => proceedToSuccessPage(mcqBookingData), 3000);
        });
    }

    function skipMCQQuestions() {
        console.debug('MCQ: Skip button clicked');
        
        // Show confirmation modal
        Swal.fire({
            title: 'Skip Questions?',
            text: 'You can always provide feedback later. Your booking will be confirmed.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fa fa-check"></i> Yes, Skip',
            cancelButtonText: '<i class="fa fa-times"></i> Continue Questions',
            confirmButtonColor: '#36877F',
            cancelButtonColor: '#718096',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                console.debug('MCQ: User confirmed skip, closing modal and proceeding to success');
                
                // Close MCQ modal
                const modalElement = document.getElementById('mcqModal');
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) modal.hide();
                } else if (typeof $ !== 'undefined' && $.fn.modal) {
                    $(modalElement).modal('hide');
                } else {
                    modalElement.style.display = 'none';
                    modalElement.classList.remove('show');
                    document.body.classList.remove('modal-open');
                    // Remove modal backdrop if exists
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) backdrop.remove();
                }
                
                // Show brief message and proceed to success page
                Swal.fire({
                    title: 'Booking Confirmed!',
                    text: 'Your payment was successful and booking is confirmed.',
                    icon: 'success',
                    timer: 2000,
                    timerProgressBar: true,
                    allowOutsideClick: false,
                    showConfirmButton: false
                }).then(() => {
                    console.debug('MCQ: Skip - proceeding to success page with mcqBookingData', mcqBookingData);
                    proceedToSuccessPage(mcqBookingData);
                });
            }
        });
    }
});
</script>

<!-- YuktiOne booking interaction styles -->
<style>
.payment-failure-popup {
    font-family: "DM Sans", sans-serif;
}

.payment-failure-confirm,
.payment-failure-cancel {
    border-radius: 0.75rem !important;
    font-weight: 600 !important;
    padding: 10px 20px !important;
}

.swal2-icon.swal2-error {
    border-color: #C85B65 !important;
    color: #C85B65 !important;
}

.swal2-icon.swal2-warning {
    border-color: #B78538 !important;
    color: #B78538 !important;
}

.swal2-icon.swal2-info {
    border-color: #36877F !important;
    color: #36877F !important;
}

.btn_1[disabled] {
    cursor: not-allowed;
    opacity: 0.7;
}

.btn_1 .fa-spinner {
    animation: yk-spin 1s linear infinite;
}

@keyframes yk-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.btn_1 {
    transition: background-color 0.2s ease, border-color 0.2s ease;
}

.loading-overlay {
    align-items: center;
    backdrop-filter: blur(2px);
    background: rgba(247, 250, 251, 0.92);
    display: none;
    height: 100%;
    justify-content: center;
    left: 0;
    position: fixed;
    top: 0;
    width: 100%;
    z-index: 9999;
}

.loading-overlay.show {
    display: flex;
}

.loading-spinner {
    animation: yk-spin 1s linear infinite;
    border: 4px solid #E2E9EE;
    border-radius: 50%;
    border-top-color: #36877F;
    height: 50px;
    width: 50px;
}

#mcqModal .modal-content {
    background: #ffffff;
    border: 1px solid #E2E9EE;
    border-radius: 1.25rem;
    box-shadow: 0 18px 42px -20px rgb(20 43 77 / 0.24);
    overflow: hidden;
}

#mcqModal .modal-header {
    background: #EAF6F2;
    border-bottom: 1px solid #E2E9EE;
    color: #142B4D;
    padding: 1.25rem 1.5rem;
}

#mcqModal .modal-title {
    align-items: center;
    color: #142B4D;
    display: flex;
    font-size: 1.125rem;
    font-weight: 700;
    gap: 0.75rem;
}

#mcqModal .modal-title i {
    color: #36877F;
}

#mcqModal .modal-body {
    background: #ffffff;
    padding: 1.5rem;
}

#mcqModal .modal-footer {
    align-items: center;
    background: #F7FAFB;
    border-top: 1px solid #E2E9EE;
    display: flex;
    gap: 0.75rem;
    justify-content: space-between;
    padding: 1rem 1.5rem;
}

#mcqModal .modal-footer .navigation-buttons {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.question-card {
    background: #ffffff;
    border: 1px solid #E2E9EE;
    border-radius: 1rem;
    box-shadow: 0 2px 8px rgb(20 43 77 / 0.05);
    margin-bottom: 1rem;
    padding: 1.25rem;
}

.question-card h6 {
    color: #142B4D;
    font-size: 1.05rem;
    font-weight: 700;
    line-height: 1.5;
    margin-bottom: 1rem;
}

.question-card .badge {
    background: #EAF6F2;
    border-radius: 999px;
    color: #286E67;
    font-size: 0.8125rem;
    font-weight: 600;
    padding: 0.35rem 0.7rem;
}

.options-container {
    display: grid;
    gap: 0.75rem;
}

.options-container .form-check {
    background: #F7FAFB;
    border: 1px solid #E2E9EE;
    border-radius: 0.75rem;
    cursor: pointer;
    margin-bottom: 0;
    padding: 0.9rem 1rem;
    transition: border-color 0.2s ease, background-color 0.2s ease;
}

.options-container .form-check:hover {
    background: #EAF6F2;
    border-color: #36877F;
}

.options-container .form-check-label {
    color: #475569;
    cursor: pointer;
    font-size: 0.95rem;
    font-weight: 500;
    margin-left: 0.5rem;
}

.options-container .form-check-input {
    accent-color: #36877F;
}

.options-container .form-check-input:checked + .form-check-label {
    color: #142B4D;
    font-weight: 600;
}

.options-container .form-check:has(.form-check-input:checked) {
    background: #EAF6F2;
    border-color: #36877F;
}

.progress {
    background: #E2E9EE;
    border-radius: 999px;
    height: 0.5rem;
    overflow: hidden;
}

.progress-bar {
    background: #36877F;
    border-radius: 999px;
    color: #fff;
    font-size: 0.65rem;
    font-weight: 600;
    line-height: 0.5rem;
    text-align: center;
    transition: width 0.3s ease;
}

#mcqModal .btn {
    border-radius: 0.75rem;
    font-size: 0.875rem;
    font-weight: 600;
    letter-spacing: 0;
    min-height: 2.75rem;
    padding: 0.625rem 1.1rem;
    text-transform: none;
}

#mcqModal .btn-primary {
    background: #36877F;
    border-color: #36877F;
    color: #fff;
}

#mcqModal .btn-primary:hover {
    background: #286E67;
    border-color: #286E67;
}

#mcqModal .btn-secondary {
    background: #E7F0FB;
    border-color: #E2E9EE;
    color: #142B4D;
}

#mcqModal .btn-secondary:hover {
    background: #d9e6f5;
    color: #142B4D;
}

#mcqModal .btn-success {
    background: #1D8C65;
    border-color: #1D8C65;
    color: #fff;
}

#mcqModal .btn-success:hover {
    background: #177553;
    border-color: #177553;
}

#mcqModal .btn-outline-secondary {
    background: transparent;
    border: 1px solid #E2E9EE;
    color: #475569;
}

#mcqModal .btn-outline-secondary:hover {
    background: #F7FAFB;
    border-color: #36877F;
    color: #36877F;
}

#mcqModal textarea.form-control,
#mcqModal input[type="text"].form-control {
    background: #ffffff;
    border: 1px solid #E2E9EE;
    border-radius: 0.75rem;
    box-shadow: none;
    font-size: 0.95rem;
    padding: 0.75rem 0.9rem;
}

#mcqModal textarea.form-control:focus,
#mcqModal input[type="text"].form-control:focus {
    border-color: #36877F;
    box-shadow: 0 0 0 3px rgb(54 135 127 / 0.12);
    transform: none;
}

@media (max-width: 768px) {
    #mcqModal .modal-content {
        margin: 0.75rem;
    }

    #mcqModal .modal-header,
    #mcqModal .modal-body,
    #mcqModal .modal-footer {
        padding: 1rem;
    }

    #mcqModal .modal-footer {
        align-items: stretch;
        flex-direction: column;
    }

    #mcqModal .modal-footer .navigation-buttons,
    #mcqModal .btn {
        width: 100%;
    }
}
</style>

@endsection
