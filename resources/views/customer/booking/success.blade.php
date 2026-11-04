@extends('customer.layout.booking')

@section('title', config('yuktione.name', 'YuktiOne') . ' | Booking Confirmed')

@section('styles')
<style>
    .btn-dashboard {
        align-items: center;
        background: #36877F;
        border-radius: 0.75rem;
        color: #fff;
        display: inline-flex;
        font-weight: 600;
        justify-content: center;
        min-height: 2.75rem;
        padding: 0.65rem 1.5rem;
        text-decoration: none;
    }
    .btn-dashboard:hover { background: #286E67; color: #fff; text-decoration: none; }
</style>
@endsection

@section('content')
@php
    $serviceName = 'N/A';
    $subServiceName = null;

    if (session('booking_success.service_name')) {
        $serviceName = session('booking_success.service_name');
    } elseif (session('booking_success.professional_id')) {
        $professionalId = session('booking_success.professional_id');
        $professionalService = \App\Models\ProfessionalService::with('service')
            ->where('professional_id', $professionalId)
            ->first();

        if ($professionalService) {
            if ($professionalService->service && $professionalService->service->name) {
                $serviceName = $professionalService->service->name;
            } elseif ($professionalService->service_name) {
                $serviceName = $professionalService->service_name;
            }
        }
    } elseif (session('selected_service_name')) {
        $serviceName = session('selected_service_name');
    }

    if (session('booking_success.sub_service_name')) {
        $subServiceName = session('booking_success.sub_service_name');
    } elseif (session('booking_success.sub_service_id')) {
        $subService = \App\Models\SubService::find(session('booking_success.sub_service_id'));
        if ($subService) {
            $subServiceName = $subService->name;
        }
    }

    $successBookings = session('booking_success.bookings');
    $successBookings = is_array($successBookings) ? $successBookings : [];
    $bookingRef = session('booking_success.booking_id');
@endphp

<div class="yk-booking-shell mx-auto grid max-w-3xl gap-6">
    <x-tazen-v2.panel.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('user.dashboard')],
        ['label' => 'Booking Confirmed'],
    ]" />

    <article class="yk-booking-card success-box text-center">
        <div class="yk-booking-card__body">
            <div class="yk-booking-success-icon success-icon mx-auto" aria-hidden="true">
                <i class="fas fa-check"></i>
            </div>
            <h1 class="font-display text-page-title font-semibold text-brand-secondary">Booking Confirmed!</h1>
            <p class="mt-2 text-sm text-text-secondary">Your appointment has been successfully booked.</p>
            @if($bookingRef)
                <p class="mt-3 inline-flex rounded-full border border-brand-200 bg-brand-50 px-3 py-1 text-sm font-semibold text-brand-secondary">
                    Booking #{{ $bookingRef }}
                </p>
            @endif

            <div class="booking-details mt-6 rounded-panel border border-border bg-surface-muted/30 p-4 text-left sm:p-5">
                <h2 class="mb-3 border-b border-brand-primary/40 pb-2 text-base font-semibold text-brand-secondary">Professional details</h2>
                <dl class="yk-booking-dl">
                    <div>
                        <dt>Professional</dt>
                        <dd>{{ session('booking_success.professional_name') }}</dd>
                    </div>
                    <div>
                        <dt>Address</dt>
                        <dd>{{ session('booking_success.professional_address') }}</dd>
                    </div>
                    <div>
                        <dt>Service</dt>
                        <dd>{{ $serviceName }}</dd>
                    </div>
                    @if($subServiceName)
                        <div>
                            <dt>Sub-service</dt>
                            <dd>{{ $subServiceName }}</dd>
                        </div>
                    @endif
                </dl>

                <h2 class="mb-3 mt-6 border-b border-brand-primary/40 pb-2 text-base font-semibold text-brand-secondary">Booking information</h2>
                <dl class="yk-booking-dl">
                    <div>
                        <dt>Plan type</dt>
                        <dd>{{ ucwords(str_replace('_', ' ', (string) session('booking_success.plan_type'))) }}</dd>
                    </div>
                    @if(session('booking_success.base_amount') && session('booking_success.cgst'))
                        <div>
                            <dt>Base amount</dt>
                            <dd>{{ getCurrencySymbol() }}{{ number_format(convertCurrency(session('booking_success.base_amount')), 2) }}</dd>
                        </div>
                        <div>
                            <dt>CGST (9%)</dt>
                            <dd>{{ getCurrencySymbol() }}{{ number_format(convertCurrency(session('booking_success.cgst')), 2) }}</dd>
                        </div>
                        <div>
                            <dt>SGST (9%)</dt>
                            <dd>{{ getCurrencySymbol() }}{{ number_format(convertCurrency(session('booking_success.sgst')), 2) }}</dd>
                        </div>
                        <div>
                            <dt>IGST (0%)</dt>
                            <dd>{{ getCurrencySymbol() }}{{ number_format(convertCurrency(session('booking_success.igst')), 2) }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt>Total paid (incl. GST)</dt>
                        <dd>{{ getCurrencySymbol() }}{{ number_format(convertCurrency(session('booking_success.amount')), 2) }}</dd>
                    </div>
                    <div>
                        <dt>Booking dates</dt>
                        <dd>
                            <ul class="m-0 list-none space-y-1 p-0 text-right">
                                @forelse($successBookings as $booking)
                                    <li>
                                        {{ \Carbon\Carbon::parse($booking['date'])->format('d M Y') }}
                                        · {{ $booking['time_slot'] }}
                                    </li>
                                @empty
                                    <li>N/A</li>
                                @endforelse
                            </ul>
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="yk-booking-actions mt-6">
                <a href="{{ route('user.upcoming-appointment.index') }}" class="btn_1 btn-dashboard">View My Appointments</a>
                <a href="{{ route('user.my-bookings') }}" class="btn_1 outline">My Bookings</a>
                <a href="{{ route('user.dashboard') }}" class="btn_1 outline">Dashboard</a>
            </div>
        </div>
    </article>
</div>

<!-- MCQ Modal -->
@if(isset($mcqQuestions) && $mcqQuestions->count() > 0)
<div class="modal fade" id="mcqModal" tabindex="-1" aria-labelledby="mcqModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="mcqModalLabel">
                    <i class="fas fa-clipboard-list me-2"></i>Service Questionnaire
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="max-height: 500px; overflow-y: auto;">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Please complete this questionnaire to help us provide better service tailored to your needs.
                </div>

                <form id="mcqForm">
                    @csrf
                    <input type="hidden" id="service_id" name="service_id" value="{{ $serviceId }}">
                    <input type="hidden" id="booking_id" name="booking_id" value="{{ $bookingId }}">

                    @foreach($mcqQuestions as $index => $question)
                        <div class="mb-4">
                            <h6 class="fw-bold text-primary">Question {{ $index + 1 }}:</h6>
                            <p class="question-text">{{ $question->question }}</p>
                            
                            <div class="options-container">
                                @php
                                    $options = $question->formatted_options;
                                @endphp
                                
                                @foreach($options as $optionKey => $optionValue)
                                    @if(!empty($optionValue))
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" 
                                                   name="answers[{{ $index }}][selected_answer]" 
                                                   id="question_{{ $question->id }}_option_{{ $optionKey }}" 
                                                   value="{{ $optionValue }}" required>
                                            <label class="form-check-label" for="question_{{ $question->id }}_option_{{ $optionKey }}">
                                                <strong>{{ $optionKey }}:</strong> {{ $optionValue }}
                                            </label>
                                        </div>
                                    @endif
                                @endforeach

                                @if($question->has_other_option)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" 
                                               name="answers[{{ $index }}][selected_answer]" 
                                               id="question_{{ $question->id }}_option_other" 
                                               value="Other" required>
                                        <label class="form-check-label" for="question_{{ $question->id }}_option_other">
                                            <strong>Other:</strong>
                                        </label>
                                        <input type="text" class="form-control mt-2 other-input" 
                                               name="answers[{{ $index }}][other_answer]" 
                                               placeholder="Please specify..." 
                                               style="display: none;">
                                    </div>
                                @endif
                            </div>

                            <!-- Hidden inputs for form submission -->
                            <input type="hidden" name="answers[{{ $index }}][mcq_id]" value="{{ $question->id }}">
                            <input type="hidden" name="answers[{{ $index }}][question]" value="{{ $question->question }}">
                        </div>

                        @if(!$loop->last)
                            <hr>
                        @endif
                    @endforeach
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Skip for Now
                </button>
                <button type="button" class="btn btn-primary" id="submitMCQ">
                    <i class="fas fa-paper-plane me-1"></i>Submit Answers
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.question-text {
    background: #F7FAFB;
    border-left: 4px solid #36877F;
    border-radius: 0.75rem;
    margin-bottom: 15px;
    padding: 15px;
}

.options-container {
    padding-left: 20px;
}

.form-check {
    border-radius: 0.75rem;
    padding: 10px;
    transition: background-color 0.2s;
}

.form-check:hover {
    background-color: #EAF6F2;
}

.form-check-input:checked + .form-check-label {
    color: #36877F;
    font-weight: 600;
}

.other-input {
    margin-left: 24px;
    max-width: 300px;
}

#mcqModal .modal-content {
    border: 1px solid #E2E9EE;
    border-radius: 1.25rem;
    overflow: hidden;
}

#mcqModal .modal-header {
    background: #EAF6F2;
    border-bottom: 1px solid #E2E9EE;
    color: #142B4D;
}

#mcqModal .modal-footer {
    border-top: 1px solid #E2E9EE;
}

#mcqModal .btn-primary {
    background: #36877F;
    border-color: #36877F;
}

#mcqModal .btn-primary:hover {
    background: #286E67;
    border-color: #286E67;
}

#mcqModal .modal-body {
    font-size: 14px;
}

#mcqModal h6 {
    font-size: 16px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show MCQ modal automatically after success page loads
    @if(isset($mcqQuestions) && $mcqQuestions->count() > 0)
        setTimeout(function() {
            var mcqModal = new bootstrap.Modal(document.getElementById('mcqModal'));
            mcqModal.show();
        }, 1500); // Show after 1.5 seconds
    @endif

    // Handle "Other" option selection
    document.addEventListener('change', function(e) {
        if (e.target.type === 'radio' && e.target.value === 'Other') {
            const otherInput = e.target.closest('.form-check').querySelector('.other-input');
            if (otherInput) {
                otherInput.style.display = 'block';
                otherInput.required = true;
                otherInput.focus();
            }
        } else if (e.target.type === 'radio' && e.target.value !== 'Other') {
            // Hide other inputs in the same question group
            const questionContainer = e.target.closest('.mb-4');
            const otherInputs = questionContainer.querySelectorAll('.other-input');
            otherInputs.forEach(input => {
                input.style.display = 'none';
                input.required = false;
                input.value = '';
            });
        }
    });

    // Handle MCQ form submission
    document.getElementById('submitMCQ').addEventListener('click', function() {
        const form = document.getElementById('mcqForm');
        const formData = new FormData(form);
        const submitBtn = this;
        
        // Validate form
        const requiredInputs = form.querySelectorAll('input[required]');
        let isValid = true;
        
        requiredInputs.forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
                input.closest('.mb-4').classList.add('border', 'border-danger');
            } else {
                input.closest('.mb-4').classList.remove('border', 'border-danger');
            }
        });

        if (!isValid) {
            alert('Please answer all questions before submitting.');
            return;
        }

        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Submitting...';

        // Convert FormData to JSON
        const answers = [];
        const serviceId = formData.get('service_id');
        const bookingId = formData.get('booking_id');

        @foreach($mcqQuestions as $index => $question)
            const answer{{ $index }} = {
                mcq_id: formData.get('answers[{{ $index }}][mcq_id]'),
                question: formData.get('answers[{{ $index }}][question]'),
                // Map selected_answer -> answer for user.mcq.submit validation
                answer: formData.get('answers[{{ $index }}][selected_answer]'),
                other_answer: formData.get('answers[{{ $index }}][other_answer]') || null
            };
            answers.push(answer{{ $index }});
        @endforeach

        // Submit via existing authenticated MCQ endpoint (compatible payload shape)
        fetch('{{ route("user.mcq.submit") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                service_id: serviceId,
                booking_id: bookingId,
                answers: answers
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Show success message and close modal
                alert(data.message);
                bootstrap.Modal.getInstance(document.getElementById('mcqModal')).hide();
            } else {
                throw new Error(data.message || 'Failed to submit answers');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to submit your answers. Please try again.');
        })
        .finally(() => {
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Submit Answers';
        });
    });
});
</script>
@endif
@endsection
