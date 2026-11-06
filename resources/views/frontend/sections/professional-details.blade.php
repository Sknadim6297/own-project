@extends('layouts.layout')
@section('body_class', 'yk-profile-page')
@section('styles')
    <link rel="stylesheet" href="{{ asset('frontend/assets/css/yuktione-expert-profile.css') }}?v={{ filemtime(public_path('frontend/assets/css/yuktione-expert-profile.css')) }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endsection

@section('content')
    @if (!$profile)
        <div class="yk-profile"><p class="yk-profile__empty">This expert profile is not available.</p></div>
    @else
        @include('frontend.sections.expert-profile')
    @endif
@endsection
@section('script')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Helper function to format the date to local date string (GLOBAL SCOPE)
        function formatLocalDate(date) {
            const offset = date.getTimezoneOffset();
            const localDate = new Date(date.getTime() - offset * 60000);
            return localDate.toISOString().split('T')[0];
        }
        
        document.addEventListener("DOMContentLoaded", function () {
            let enabledDates = @json($enabledDates);
            
            // Get existing bookings for this professional
            const existingBookings = @json($existingBookings ?? []);
            
            // Debug: Log the existing bookings data
            console.log('Existing bookings data:', existingBookings);
            console.log('Enabled dates:', enabledDates);
            
            let selectedBookings = {};
            
            // Initialize calendars for both desktop and mobile
            function initializeCalendar(calendarId, isMobile = false) {
                if (!document.getElementById(calendarId)) return;
                
                // Initialize Flatpickr
                flatpickr("#" + calendarId, {
                    inline: true,
                    mode: "multiple",
                    dateFormat: "Y-m-d",
                    minDate: "today",
                    enable: enabledDates, 
                    disable: [
                        function (date) {
                            const dateString = formatLocalDate(date);
                            return !enabledDates.includes(dateString);
                        }
                    ],
                    onDayCreate: function (dObj, dStr, fp, dayElem) {
                        const date = dayElem.dateObj;
                        const dateString = formatLocalDate(date);
                        dayElem.classList.toggle('yk-day-open', enabledDates.includes(dateString));
                    },
                    onChange: function (selectedDates, dateStr, instance) {
                        const offset = selectedDates.length ? selectedDates[0].getTimezoneOffset() : 0;
                        const selectedDatesLocal = selectedDates.map(d => {
                            return new Date(d.getTime() - offset * 60000).toISOString().split('T')[0];
                        });

                        // Remove unselected dates from selectedBookings
                        Object.keys(selectedBookings).forEach(date => {
                            if (!selectedDatesLocal.includes(date)) {
                                delete selectedBookings[date];
                            }
                        });

                        // Hide all slot boxes first
                        const slotBoxes = isMobile ? 
                            Array.from(document.querySelectorAll('.mobile-time-slot')).map(slot => slot.closest('.slot-box')) :
                            Array.from(document.querySelectorAll('.time-slot')).map(slot => slot.closest('.slot-box'));
                            
                        slotBoxes.forEach(box => {
                            if (box) {
                                box.style.display = 'none';
                                box.removeAttribute('data-current-date');
                            }
                        });

                        // Show slots for last selected date only
                        if (selectedDates.length > 0) {
                            const selectedDateUTC = selectedDates[selectedDates.length - 1];
                            const selectedDate = new Date(selectedDateUTC.getTime() - offset * 60000);
                            const weekdayNames = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
                            const weekday = weekdayNames[selectedDate.getDay()];
                            const dateString = selectedDate.toISOString().split('T')[0];
                            
                            // Get month from selected date
                            const monthNames = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
                            const selectedMonth = monthNames[selectedDate.getMonth()];

                            console.log('Selected date:', dateString, 'Weekday:', weekday, 'Month:', selectedMonth);
                            console.log('Looking for slots with selector:', `.slot-box[data-weekday="${weekday}"][data-month="${selectedMonth}"]`);
                            
                            // Debug: Log all available slot boxes
                            const allSlotBoxes = document.querySelectorAll('.slot-box');
                            console.log('Total slot boxes:', allSlotBoxes.length);
                            allSlotBoxes.forEach((box, index) => {
                                if (index < 5) { // Show first 5 for debugging
                                    console.log(`Slot ${index}:`, {
                                        weekday: box.getAttribute('data-weekday'),
                                        month: box.getAttribute('data-month'),
                                        debugMonth: box.getAttribute('data-debug-month')
                                    });
                                }
                            });

                            const relevantSlotBoxes = isMobile ? 
                                Array.from(document.querySelectorAll(`.slot-box[data-weekday="${weekday}"][data-month="${selectedMonth}"] .mobile-time-slot`)).map(slot => slot.closest('.slot-box')) :
                                Array.from(document.querySelectorAll(`.slot-box[data-weekday="${weekday}"][data-month="${selectedMonth}"] .time-slot`)).map(slot => slot.closest('.slot-box'));

                            console.log('Found', relevantSlotBoxes.length, 'relevant slot boxes for', weekday, selectedMonth);

                            relevantSlotBoxes.forEach(box => {
                                if (box) {
                                    box.style.display = 'flex';
                                    box.style.alignItems = 'center';
                                    box.setAttribute('data-current-date', dateString);
                                    
                                    // Check if the slot is already booked
                                    const timeInput = box.querySelector(isMobile ? '.mobile-time-slot' : '.time-slot');
                                    const timeValue = timeInput ? timeInput.value : '';
                                    const label = timeInput ? timeInput.nextElementSibling : null;

                                    // AGGRESSIVELY Reset any previous styling and state
                                    if (timeInput) {
                                        timeInput.disabled = false;
                                        timeInput.checked = false;
                                        timeInput.removeAttribute('disabled');
                                    }
                                    if (box) {
                                        box.classList.remove('slot-booked');
                                        box.style.opacity = '';
                                        box.style.pointerEvents = '';
                                        box.style.cursor = '';
                                    }
                                    if (label) {
                                        label.style.opacity = '';
                                        label.style.textDecoration = '';
                                        label.style.pointerEvents = '';
                                        label.style.cursor = '';
                                        label.style.color = '#222';
                                        label.style.background = '#f8f9fa';
                                        label.style.display = 'block';
                                        
                                        // Remove previous BOOKED text FIRST
                                        if (label.innerHTML && label.innerHTML.includes('(BOOKED)')) {
                                            label.innerHTML = label.innerHTML.replace(/ <span[^>]*>\(BOOKED\)<\/span>/g, '');
                                        }
                                        
                                        // Ensure label text is present (fallback to data attributes)
                                        try {
                                            const start = timeInput ? timeInput.getAttribute('data-start') || '' : '';
                                            const end = timeInput ? timeInput.getAttribute('data-end') || '' : '';
                                            const cleanText = label.textContent.replace(/\(BOOKED\)/g, '').trim();
                                            if (!cleanText || cleanText === '') {
                                                label.textContent = start + (start && end ? ' - ' : '') + end;
                                            }
                                        } catch (e) {
                                            console.warn('label autofill failed', e);
                                        }
                                    }
                                    
                                    // Check if this slot is booked for the selected date
                                    console.log('Checking booking for date:', dateString, 'time:', timeValue);
                                    const isBooked = isTimeSlotBooked(dateString, timeValue);
                                    console.log('Is booked result:', isBooked);
                                    
                                    if (timeInput && isBooked) {
                                        console.log('MARKING AS BOOKED:', dateString, timeValue);
                                        timeInput.disabled = true;
                                        timeInput.setAttribute('disabled', 'disabled');
                                        box.classList.add('slot-booked');
                                        box.style.pointerEvents = 'none';
                                        box.style.cursor = 'not-allowed';
                                        if (label) {
                                            label.style.opacity = '0.5';
                                            label.style.textDecoration = 'line-through';
                                            label.style.pointerEvents = 'none';
                                            label.style.cursor = 'not-allowed';
                                            label.innerHTML = label.innerHTML + ' <span style="color: red; font-size: 10px; font-weight: bold;">(BOOKED)</span>';
                                        }
                                    } else {
                                        console.log('MARKING AS AVAILABLE:', dateString, timeValue);
                                    }
                                }
                            });
                        }

                        updateSelectedTimeList(isMobile);
                    }
                });
            }
            
            // Initialize both calendars
            initializeCalendar('calendarDiv', false); // Desktop
            initializeCalendar('mobile-calendarDiv', true); // Mobile

            // Note: Calendar initialization is handled by initializeCalendar function above

            // Function to check if a time slot is already booked
            function isTimeSlotBooked(date, timeSlot) {
                console.log('=== Checking if booked ===');
                console.log('Requested Date:', date);
                console.log('Requested TimeSlot:', timeSlot);
                console.log('All existing bookings:', existingBookings);
                console.log('Bookings for this specific date:', existingBookings[date]);
                
                if (!existingBookings[date]) {
                    console.log('No bookings found for this date - AVAILABLE');
                    return false;
                }
                
                // Normalize both time slot formats for comparison
                const normalizeTimeSlot = (slot) => {
                    return slot.replace(' to ', ' - ').trim();
                };
                
                const normalizedTimeSlot = normalizeTimeSlot(timeSlot);
                console.log('Normalized time slot:', normalizedTimeSlot);
                
                const isBooked = existingBookings[date].some(bookedTime => {
                    const normalizedBookedTime = normalizeTimeSlot(bookedTime);
                    console.log('Comparing with booked time:', normalizedBookedTime);
                    return normalizedTimeSlot === normalizedBookedTime;
                });
                
                console.log('Is booked result:', isBooked);
                return isBooked;
            }
            
            // Function to update selected time list for both desktop and mobile
            function updateSelectedTimeList(isMobile = false) {
                const listId = isMobile ? 'mobile-selected-time-list' : 'selected-time-list';
                const list = document.getElementById(listId);
                if (list) {
                    list.innerHTML = '';

                    // Sort the dates first
                    const sortedDates = Object.keys(selectedBookings).sort();

                    sortedDates.forEach(date => {
                        // Sort times within each date
                        const sortedTimes = selectedBookings[date].slice().sort((a, b) => {
                            return new Date(`1970-01-01T${a}`) - new Date(`1970-01-01T${b}`);
                        });

                        sortedTimes.forEach(time => {
                            const item = document.createElement('li');
                            item.classList.add('selected-booking-item');
                            // Convert YYYY-MM-DD to dd/mm/yy for display
                            let [year, month, day] = date.split('-');
                            let displayDate = `${day}/${month}/${year.slice(-2)}`;
                            item.innerHTML = `
                                <span>${displayDate} - ${time}</span>
                                <button type="button" class="remove-booking-item" data-date="${date}" data-time="${time}" aria-label="Remove booking">
                                    &#x2716;
                                </button>
                            `;
                            list.appendChild(item);
                        });
                    });

                    // Add event listeners for remove buttons
                    document.querySelectorAll('.remove-booking-item').forEach(button => {
                        button.addEventListener('click', function() {
                            const dateToRemove = this.getAttribute('data-date');
                            const timeToRemove = this.getAttribute('data-time');

                            if (selectedBookings[dateToRemove]) {
                                // Remove the specific time from the array for that date
                                selectedBookings[dateToRemove] = selectedBookings[dateToRemove].filter(time => time !== timeToRemove);

                                // If no more times for this date, remove the date entry
                                if (selectedBookings[dateToRemove].length === 0) {
                                    delete selectedBookings[dateToRemove];
                                }
                            }
                            updateSelectedTimeList(isMobile); // Re-render the list
                        });
                    });
                } else {
                    console.warn('Selected time list element not found!');
                }
            }

            // Function to mark booked time slots visually
            function markBookedSlots() {
                document.querySelectorAll('.time-slot').forEach(slot => {
                    const timeValue = slot.value;
                    const slotBox = slot.closest('.slot-box');
                    const label = slot.nextElementSibling;
                    
                    // Get the current selected date from calendar or use a default
                    const selectedDates = document.querySelectorAll('.flatpickr-day.selected');
                    
                    if (selectedDates.length > 0) {
                        const selectedDate = selectedDates[0].getAttribute('aria-label');
                        const dateString = formatLocalDate(new Date(selectedDate));
                        
                        if (isTimeSlotBooked(dateString, timeValue)) {
                            slot.disabled = true;
                            slotBox.classList.add('slot-booked');
                            label.style.opacity = '0.5';
                            label.style.textDecoration = 'line-through';
                            label.innerHTML = label.innerHTML + ' <span style="color: red; font-size: 10px;">(BOOKED)</span>';
                        }
                    }
                });
            }

            // Function to update booked slots when date changes
            function updateBookedSlotsForDate(dateString) {
                document.querySelectorAll('.time-slot').forEach(slot => {
                    const timeValue = slot.value;
                    const slotBox = slot.closest('.slot-box');
                    const label = slot.nextElementSibling;
                    
                    // Reset previous booked styling
                    slot.disabled = false;
                    slotBox.classList.remove('slot-booked');
                    label.style.opacity = '';
                    label.style.textDecoration = '';
                    // Remove previous BOOKED text
                    label.innerHTML = label.innerHTML.replace(/ <span[^>]*>\(BOOKED\)<\/span>/g, '');
                    
                    // Check if this slot is booked for the selected date
                    if (isTimeSlotBooked(dateString, timeValue)) {
                        slot.disabled = true;
                        slotBox.classList.add('slot-booked');
                        label.style.opacity = '0.5';
                        label.style.textDecoration = 'line-through';
                        label.innerHTML = label.innerHTML + ' <span style="color: red; font-size: 10px;">(BOOKED)</span>';
                    }
                });
            }

            // Handle slot selection for both desktop and mobile
            function initializeSlotHandlers(isMobile = false) {
                const selector = isMobile ? '.mobile-time-slot' : '.time-slot';
                document.querySelectorAll(selector).forEach(slot => {
                    // Prevent clicks on disabled inputs
                    slot.addEventListener('click', function (e) {
                        if (this.disabled) {
                            e.preventDefault();
                            e.stopPropagation();
                            const box = this.closest('.slot-box');
                            if (box && box.classList.contains('slot-booked')) {
                                toastr.error('This time slot is already booked. Please choose another time.');
                            }
                            console.log('Click prevented on disabled slot');
                            return false;
                        }
                    });
                    
                    slot.addEventListener('change', function () {
                        // Prevent selection if disabled
                        if (this.disabled) {
                            console.log('Slot is disabled, preventing selection');
                            this.checked = false;
                            return;
                        }
                        
                        const box = this.closest('.slot-box');
                        const currentDate = box.getAttribute('data-current-date');
                        const selectedTime = this.value;

                        // Don't allow selection of already booked slots
                        if (isTimeSlotBooked(currentDate, selectedTime)) {
                            console.log('Slot is booked, preventing selection');
                            toastr.error('This time slot is already booked. Please choose another time.');
                            this.checked = false;
                            return;
                        }

                        if (currentDate && selectedTime) {
                            if (!selectedBookings[currentDate]) {
                                selectedBookings[currentDate] = [];
                            }
                            selectedBookings[currentDate] = [selectedTime];
                            const slotSelector = isMobile ? '.mobile-time-slot' : '.time-slot';
                            document.querySelectorAll(`.slot-box[data-current-date="${currentDate}"] ${slotSelector}`).forEach(input => {
                                input.checked = (input.value === selectedTime);
                            });

                            updateSelectedTimeList(isMobile);
                        }
                    });
                });
            }
            
            // Initialize slot handlers for both desktop and mobile
            initializeSlotHandlers(false); // Desktop
            initializeSlotHandlers(true); // Mobile
            
            // Add CSS for booked slots
            const style = document.createElement('style');
            style.textContent = `
                .slot-booked {
                    opacity: 0.6;
                    position: relative;
                    pointer-events: none;
                    cursor: not-allowed;
                }
                .slot-booked input {
                    pointer-events: none;
                    cursor: not-allowed;
                }
                .slot-booked::after {
                    content: "BOOKED";
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    background-color: rgba(255, 0, 0, 0.7);
                    color: white;
                    font-size: 10px;
                    padding: 2px 5px;
                    border-radius: 3px;
                    white-space: nowrap;
                    z-index: 2;
                }
            `;
            document.head.appendChild(style);

            function updateSelectedTimeList() {
                const list = document.getElementById('selected-time-list');
                if (list) {
                    list.innerHTML = '';

                    // Sort the dates first
                    const sortedDates = Object.keys(selectedBookings).sort();

                    sortedDates.forEach(date => {
                        // Sort times within each date
                        const sortedTimes = selectedBookings[date].slice().sort((a, b) => {
                            return new Date(`1970-01-01T${a}`) - new Date(`1970-01-01T${b}`);
                        });

                        sortedTimes.forEach(time => {
                            const item = document.createElement('li');
                            item.classList.add('selected-booking-item');
                            // Convert YYYY-MM-DD to dd/mm/yy for display
                            let [year, month, day] = date.split('-');
                            let displayDate = `${day}/${month}/${year.slice(-2)}`;
                            item.innerHTML = `
                                <span>${displayDate} - ${time}</span>
                                <button type="button" class="remove-booking-item" data-date="${date}" data-time="${time}" aria-label="Remove booking">
                                    &#x2716;
                                </button>
                            `;
                            list.appendChild(item);
                        });
                    });
                    const bookingDataInput = document.getElementById('booking_data_json');
                    if (bookingDataInput) {
                        bookingDataInput.value = JSON.stringify(selectedBookings);
                    }

                    // Add event listeners for remove buttons
                    document.querySelectorAll('.remove-booking-item').forEach(button => {
                        button.addEventListener('click', function() {
                            const dateToRemove = this.getAttribute('data-date');
                            const timeToRemove = this.getAttribute('data-time');

                            if (selectedBookings[dateToRemove]) {
                                // Remove the specific time from the array for that date
                                selectedBookings[dateToRemove] = selectedBookings[dateToRemove].filter(time => time !== timeToRemove);

                                // If no more times for this date, remove the date entry
                                if (selectedBookings[dateToRemove].length === 0) {
                                    delete selectedBookings[dateToRemove];
                                    // Also unselect the date in Flatpickr if no times remain for it
                                    const flatpickrInstance = document.querySelector('#calendarDiv')._flatpickr;
                                    if (flatpickrInstance) {
                                        const currentSelectedDates = flatpickrInstance.selectedDates.map(d => formatLocalDate(d));
                                        const newSelectedDates = currentSelectedDates.filter(d => d !== dateToRemove);
                                        flatpickrInstance.setDate(newSelectedDates, true); // Update Flatpickr without triggering onChange
                                    }
                                }
                            }
                            updateSelectedTimeList(); // Re-render the list
                        });
                    });
                } else {
                    console.warn('Selected time list element not found!');
                }
            }

            // Handle plan selection
            const planButtons = document.querySelectorAll('.select-plan');
            const selectedPlanDisplay = document.getElementById('selected-plan-display');
            const selectedPlanText = document.getElementById('selected-plan-text');
            const selectedPlanInput = document.getElementById('selected_plan');
            let sessionCount = 0; 
            let selectedRate = 0;

            planButtons.forEach(button => {
                button.addEventListener('click', function () {
                    // First check if user is logged in before allowing plan selection
                    checkAuthenticationBeforePlanSelection(this);
                });
            });

            // Function to check authentication before plan selection
            function checkAuthenticationBeforePlanSelection(planButton) {
                console.log('Checking authentication for plan selection...');
                
                fetch('/check-login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        check: true
                    })
                })
                .then(response => {
                    console.log('Authentication response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Authentication response data:', data);
                    if (data.logged_in) {
                        // User is logged in, proceed with plan selection
                        console.log('User is authenticated, selecting plan...');
                        selectPlan(planButton);
                    } else {
                        // User is not logged in, show login popup
                        console.log('User is not authenticated, showing login popup...');
                        console.log('Swal object available:', typeof Swal);
                        showLoginPopupForPlanSelection(planButton);
                    }
                })
                .catch(error => {
                    console.error('Error checking login status:', error);
                    // On error, show login popup (safer fallback)
                    console.log('Error occurred, showing login popup as fallback...');
                    showLoginPopupForPlanSelection(planButton);
                });
            }

            // Function to show login popup for plan selection
            function showLoginPopupForPlanSelection(planButton) {
                console.log('showLoginPopupForPlanSelection called');
                console.log('Swal available:', typeof Swal !== 'undefined');
                
                const plan = planButton.getAttribute('data-plan');
                const planDisplayName = plan.split('_')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');

                console.log('Plan:', plan, 'Display name:', planDisplayName);

                // Check if Swal is available
                if (typeof Swal === 'undefined') {
                    console.error('SweetAlert2 not loaded!');
                    alert('Please login to select a consultation plan and proceed with booking.');
                    window.location.href = "/login?redirect=" + encodeURIComponent(window.location.href);
                    return;
                }

                console.log('Showing SweetAlert popup...');

                // Show the login popup with custom styling
                Swal.fire({
                    title: '<span class="login-popup-title"><span class="wave-icon">👋</span> Hey! You forgot to login</span>',
                    html: '<p style="color: white; margin: 15px 0;">Please login to select a consultation plan and proceed with booking.</p>',
                    showCloseButton: true,
                    showCancelButton: false,
                    confirmButtonText: 'Login',
                    customClass: {
                        popup: 'login-popup-custom',
                        confirmButton: 'login-popup-btn',
                        closeButton: 'login-popup-close'
                    },
                    confirmButtonColor: '#1e0d60',
                    background: 'linear-gradient(135deg, #152a70, #c51010, #f39c12)',
                    backdrop: 'rgba(0,0,0,0.8)'
                }).then((result) => {
                    console.log('SweetAlert result:', result);
                    if (result.isConfirmed) {
                        // Redirect to login with current professional page as intended destination
                        window.location.href = "/login?redirect=" + encodeURIComponent(window.location.href);
                    }
                    // If cancelled, do nothing - user stays on current page
                });
            }

            // Function to actually select the plan (for authenticated users)
            function selectPlan(planButton) {
                const plan = planButton.getAttribute('data-plan');
                sessionCount = parseInt(planButton.getAttribute('data-sessions')); 
                selectedRate = parseFloat(planButton.getAttribute('data-rate'));
                selectedPlanInput.value = plan;
                
                // Format the plan name for display
                const displayPlan = plan.split('_')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');
                    
                document.querySelectorAll('.select-plan').forEach(function (button) {
                    button.classList.toggle('is-selected', button === planButton);
                });
                selectedPlanText.textContent = sessionCount === 1
                    ? `${displayPlan} selected. Choose 1 date and a time.`
                    : `${displayPlan} selected. Choose ${sessionCount} dates, one for each session.`;
                selectedPlanDisplay.style.display = 'block';
            }

            // Handle booking submission for both desktop and mobile
            function initializeBookingHandlers(isMobile = false) {
                const buttonId = isMobile ? 'mobile-bookNowBtn' : 'bookNowBtn';
                const bookingButton = document.getElementById(buttonId) || document.querySelector('.booking');
                
                if (bookingButton) {
                    bookingButton.addEventListener('click', function (e) {
                        e.preventDefault();
                        
                        // Proceed directly with booking since auth check happened at plan selection
                        proceedWithBooking();
                    });
                } else {
                    console.error('Booking button not found for ' + (isMobile ? 'mobile' : 'desktop') + '!');
                }
            }
            
            // Initialize booking handlers for both desktop and mobile
            initializeBookingHandlers(false);

            // Function to proceed with booking (for authenticated users)
            function proceedWithBooking() {
                const planType = selectedPlanInput.value;
                const bookingData = selectedBookings;

                // Get the number of dates selected by the user
                const selectedDatesCount = Object.keys(bookingData).length;


                if (!planType) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Please select a consultation plan.');
                    } else {
                        alert('Please select a consultation plan.');
                    }
                    return;
                }

                if (selectedDatesCount !== sessionCount) {
                    const message = `You need to select ${sessionCount} dates for this booking.`;
                    if (typeof toastr !== 'undefined') {
                        toastr.error(message);
                    } else {
                        alert(message);
                    }
                    return;
                }

                const professionalNameEl = document.getElementById('professional_name');
                const professionalAddressEl = document.getElementById('professional_address');
                
                if (!professionalNameEl) {
                    console.error('Professional name element not found');
                    const message = 'Professional information missing. Please refresh the page.';
                    if (typeof toastr !== 'undefined') {
                        toastr.error(message);
                    } else {
                        alert(message);
                    }
                    return;
                }
                
                if (!professionalAddressEl) {
                    console.error('Professional address element not found');
                    const message = 'Professional address information missing. Please refresh the page.';
                    if (typeof toastr !== 'undefined') {
                        toastr.error(message);
                    } else {
                        alert(message);
                    }
                    return;
                }

                const professionalName = professionalNameEl.textContent.trim();
                const professionalAddress = professionalAddressEl.textContent.trim();
                const professionalId = {{ json_encode(($profile && $profile->professional && $profile->professional->id) ? $profile->professional->id : $id ?? null) }};
                // Format the bookings data into the expected structure
                const formattedBookings = {};
                Object.keys(bookingData).forEach(date => {
                    formattedBookings[date] = bookingData[date];
                });
                // Send the booking data to the server
                fetch("{{ route('user.booking.session.store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        professional_name: professionalName,
                        professional_address: professionalAddress,
                        professional_id: professionalId,
                        plan_type: planType,
                        bookings: formattedBookings,
                        total_amount: selectedRate
                    })
                })
                .then(res => {
                    // Check if response is redirected to login page
                    if (res.redirected && res.url.includes('/login')) {
                        throw new Error('Authentication required');
                    }
                    if (!res.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return res.text(); // Get as text first to check if it's JSON
                })
                .then(responseText => {
                    let data;
                    try {
                        data = JSON.parse(responseText);
                    } catch (parseError) {
                        // If JSON parsing fails, it might be an HTML login page
                        if (responseText.includes('<html') || responseText.includes('login')) {
                            throw new Error('Authentication required');
                        }
                        throw new Error('Invalid response format');
                    }
                    
                    if (data.status === 'success') {
                        if (typeof toastr !== 'undefined') {
                            toastr.success(data.message);
                        } else {
                            alert(data.message || 'Booking successful!');
                        }
                        setTimeout(() => {
                            window.location.href = "{{ route('user.booking') }}";
                        }, 1000);
                    } else {
                        const message = data.message || 'Something went wrong.';
                        if (typeof toastr !== 'undefined') {
                            toastr.error(message);
                        } else {
                            alert(message);
                        }
                    }
                })
                .catch(err => {
                    console.error('Booking error:', err);
                    let message = 'Server error. Please try again later.';
                    
                    if (err.message === 'Authentication required') {
                        message = 'Please login to complete your booking.';
                        // Show login modal or redirect to login
                        if (typeof toastr !== 'undefined') {
                            toastr.info(message);
                        } else {
                            alert(message);
                        }
                        // Redirect to login with current page as the redirect
                        setTimeout(() => {
                            window.location.href = "{{ route('login') }}?redirect=" + encodeURIComponent(window.location.href);
                        }, 1500);
                        return;
                    }
                    
                    if (typeof toastr !== 'undefined') {
                        toastr.error(message);
                    } else {
                        alert(message);
                    }
                });
            }

            // Toggle Bio functionality
            const bioShort = document.getElementById("bio-short");
            const bioFull = document.getElementById("bio-full");
            const toggleBtn = document.getElementById("toggle-bio");

            if (toggleBtn) {
                toggleBtn.addEventListener("click", function () {
                    if (bioFull.style.display === "none") {
                        bioShort.style.display = "none";
                        bioFull.style.display = "block";
                        toggleBtn.textContent = "Read Less";
                    } else {
                        bioShort.style.display = "block";
                        bioFull.style.display = "none";
                        toggleBtn.textContent = "Read More";
                    }
                });
            }

            // Star rating selection — only when the review form exists (logged-in customers).
            const starRating = document.querySelector('.star-rating');
            const starInputs = starRating ? starRating.querySelectorAll('input') : [];
            const ratingValue = document.getElementById('selected-rating');

            starInputs.forEach(input => {
                input.addEventListener('change', function() {
                    const value = parseInt(this.value);
                    let text = '';
                    let emoji = '';
                    
                    switch(value) {
                        case 5: 
                            text = 'Excellent'; 
                            emoji = '⭐⭐⭐⭐⭐'; 
                            break;
                        case 4: 
                            text = 'Very Good'; 
                            emoji = '⭐⭐⭐⭐'; 
                            break;
                        case 3: 
                            text = 'Good'; 
                            emoji = '⭐⭐⭐'; 
                            break;
                        case 2: 
                            text = 'Fair'; 
                            emoji = '⭐⭐'; 
                            break;
                        case 1: 
                            text = 'Poor'; 
                            emoji = '⭐'; 
                            break;
                        default: 
                            text = 'Select a rating';
                            emoji = '';
                    }
                    
                    if (ratingValue && value >= 1 && value <= 5) {
                        ratingValue.innerHTML = `<span style="color: #ffa500; font-weight: 600;">${emoji} ${text}</span> <span style="color: #666;">(${value}/5)</span>`;

                        // Visual feedback with animation
                        ratingValue.style.transform = 'scale(1.1)';
                        setTimeout(() => {
                            ratingValue.style.transform = 'scale(1)';
                        }, 200);

                        // Show success message for higher ratings
                        if (value >= 4) {
                            if (typeof toastr !== 'undefined') {
                                toastr.success(`Thanks for rating ${value} stars! Your feedback is valuable.`);
                            }
                        }
                    }
                });

                // Add hover effect for better UX
                input.addEventListener('mouseover', function() {
                    const value = parseInt(this.value);
                    const labels = starRating.querySelectorAll('label');

                    labels.forEach((label, index) => {
                        const starValue = 5 - index; // Since we're using reverse order
                        if (starValue <= value) {
                            label.style.color = '#ffa500';
                            label.style.transform = 'scale(1.1)';
                        } else {
                            label.style.color = '#ddd';
                            label.style.transform = 'scale(1)';
                        }
                    });
                });
            });

            // Reset stars on mouse leave
            if (starRating) {
                starRating.addEventListener('mouseleave', function() {
                    const checkedInput = starRating.querySelector('input:checked');
                    const labels = starRating.querySelectorAll('label');

                    labels.forEach((label, index) => {
                        const starValue = 5 - index;
                        if (checkedInput && starValue <= parseInt(checkedInput.value)) {
                            label.style.color = '#ffa500';
                            label.style.transform = 'scale(1.1)';
                        } else {
                            label.style.color = '#ddd';
                            label.style.transform = 'scale(1)';
                        }
                    });
                });
            }

            // Submit review with enhanced validation and feedback
            const submitReviewBtn = document.getElementById('submitReview');
            if (submitReviewBtn) {
                submitReviewBtn.addEventListener('click', function() {
                    const form = document.getElementById('reviewForm');
                    const formData = new FormData(form);
                    const rating = formData.get('rating');
                    const reviewText = formData.get('review_text');
                    
                    // Enhanced validation
                    if (!rating) {
                        if (typeof toastr !== 'undefined') {
                            toastr.error('Please select a star rating (1-5 stars)', 'Rating Required');
                        } else {
                            alert('Please select a star rating (1-5 stars)');
                        }
                        
                        // Highlight the rating section
                        const ratingContainer = document.querySelector('.rating-container');
                        ratingContainer.style.border = '2px solid #dc3545';
                        ratingContainer.style.backgroundColor = '#fff5f5';
                        setTimeout(() => {
                            ratingContainer.style.border = '';
                            ratingContainer.style.backgroundColor = '';
                        }, 3000);
                        return;
                    }
                    
                    // Validate rating is between 1-5
                    const ratingValue = parseInt(rating);
                    if (ratingValue < 1 || ratingValue > 5) {
                        if (typeof toastr !== 'undefined') {
                            toastr.error('Rating must be between 1 and 5 stars', 'Invalid Rating');
                        } else {
                            alert('Rating must be between 1 and 5 stars');
                        }
                        return;
                    }
                    
                    if (!reviewText.trim()) {
                        if (typeof toastr !== 'undefined') {
                            toastr.error('Please write your review experience', 'Review Text Required');
                        } else {
                            alert('Please write your review experience');
                        }
                        
                        // Focus on textarea
                        document.getElementById('review_text').focus();
                        return;
                    }
                    
                    if (reviewText.trim().length < 10) {
                        if (typeof toastr !== 'undefined') {
                            toastr.error('Please write at least 10 characters in your review', 'Review Too Short');
                        } else {
                            alert('Please write at least 10 characters in your review');
                        }
                        return;
                    }
                    
                    // Show loading state with better UX
                    submitReviewBtn.disabled = true;
                    const originalHTML = submitReviewBtn.innerHTML;
                    submitReviewBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Submitting Review...';
                    submitReviewBtn.style.opacity = '0.7';
                    
                    // Show progress message
                    if (typeof toastr !== 'undefined') {
                        toastr.info('Submitting your review...', 'Please Wait');
                    }
                    
                    // Submit the review
                    fetch('{{ route("user.review.store") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            professional_id: formData.get('professional_id'),
                            rating: ratingValue,
                            review_text: reviewText.trim()
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Reset button state
                        submitReviewBtn.disabled = false;
                        submitReviewBtn.innerHTML = originalHTML;
                        submitReviewBtn.style.opacity = '1';
                        
                        if (data.success) {
                            // Show success message with star rating
                            const rating = formData.get('rating');
                            let ratingEmoji = '⭐'.repeat(parseInt(rating));
                            
                            if (typeof toastr !== 'undefined') {
                                toastr.success(`${ratingEmoji} Thank you for your ${rating}-star review!`, 'Review Submitted Successfully');
                            } else {
                                alert(`Thank you for your ${rating}-star review! Your feedback has been submitted successfully.`);
                            }
                            
                            // Reset form
                            form.reset();
                            document.getElementById('selected-rating').innerHTML = '<span style="color: #666; font-style: italic;">Click on stars above to rate (1-5)</span>';
                            
                            // Clear star selection visually
                            const labels = document.querySelectorAll('.star-rating label');
                            labels.forEach(label => {
                                label.style.color = '#ddd';
                                label.style.transform = 'scale(1)';
                            });
                            
                            // Close modal with delay for user to see success message
                            setTimeout(() => {
                                const modal = bootstrap.Modal.getInstance(document.getElementById('reviewModal'));
                                if (modal) {
                                    modal.hide();
                                }
                                
                                // Reload page to show new review
                                setTimeout(() => {
                                    window.location.reload();
                                }, 500);
                            }, 2000);
                            
                        } else {
                            // Handle validation errors
                            const message = data.message || 'Something went wrong while submitting your review';
                            if (typeof toastr !== 'undefined') {
                                toastr.error(message, 'Submission Failed');
                            } else {
                                alert(message);
                            }
                            
                            // Handle specific errors
                            if (data.errors) {
                                Object.keys(data.errors).forEach(field => {
                                    if (typeof toastr !== 'undefined') {
                                        toastr.error(data.errors[field][0], 'Validation Error');
                                    }
                                });
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Review submission error:', error);
                        
                        // Reset button state
                        submitReviewBtn.disabled = false;
                        submitReviewBtn.innerHTML = originalHTML;
                        submitReviewBtn.style.opacity = '1';
                        
                        // Show error message
                        if (typeof toastr !== 'undefined') {
                            toastr.error('Network error occurred. Please check your connection and try again.', 'Connection Failed');
                        } else {
                            alert('Network error occurred. Please check your connection and try again.');
                        }
                    });
                });
            }
        });

        // Social sharing functions for professional profiles
        window.shareOnFacebookProfessional = function(professionalName, url) {
            const shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}&quote=${encodeURIComponent(`Check out this professional: ${professionalName}`)}`;
            window.open(shareUrl, '_blank', 'width=600,height=400');
        };

        window.shareOnTwitterProfessional = function(professionalName, url) {
            const text = `Check out this professional: ${professionalName}`;
            const shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(url)}`;
            window.open(shareUrl, '_blank', 'width=600,height=400');
        };

        window.shareOnXProfessional = function(professionalName, url) {
            const text = `Check out this professional: ${professionalName}`;
            const shareUrl = `https://x.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(url)}`;
            window.open(shareUrl, '_blank', 'width=600,height=400');
        };

        window.shareOnWhatsAppProfessional = function(professionalName, url) {
            const text = `Check out this professional: ${professionalName} ${url}`;
            const shareUrl = `https://wa.me/?text=${encodeURIComponent(text)}`;
            window.open(shareUrl, '_blank');
        };

        window.shareOnInstagram = function(professionalName, url) {
            // Instagram doesn't have a direct sharing URL, so we copy the link and show instructions
            navigator.clipboard.writeText(url).then(function() {
                if (typeof toastr !== 'undefined') {
                    toastr.success('Link copied! Open Instagram and paste it in your story or post.');
                } else {
                    alert('Link copied to clipboard! Open Instagram and paste it in your story or post.');
                }
            }).catch(function() {
                if (typeof toastr !== 'undefined') {
                    toastr.info('Please copy this link and share it on Instagram: ' + url);
                } else {
                    alert('Please copy this link and share it on Instagram: ' + url);
                }
            });
        };

        window.copyLinkProfessional = function(url) {
            navigator.clipboard.writeText(url).then(function() {
                if (typeof toastr !== 'undefined') {
                    toastr.success('Professional profile link copied to clipboard!');
                } else {
                    alert('Professional profile link copied to clipboard!');
                }
            }).catch(function() {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = url;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                
                if (typeof toastr !== 'undefined') {
                    toastr.success('Professional profile link copied to clipboard!');
                } else {
                    alert('Professional profile link copied to clipboard!');
                }
            });
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection