const form = document.querySelector('#registerForm');

if (form) {
    const registrationSteps = [...form.querySelectorAll('[data-registration-step]')];
    const stepLabel = document.querySelector('[data-registration-step-label]');
    const progressBar = document.querySelector('[data-registration-progress]');
    const progressRegion = progressBar?.parentElement;
    const emailInput = document.querySelector('#verify-email');
    const sendCodeButton = document.querySelector('#send-otp-btn');
    const resendCodeButton = document.querySelector('#resend-otp-btn');
    const verifyCodeButton = document.querySelector('#verify-otp-btn');
    const otpInput = document.querySelector('#otp');
    const otpDigits = [...document.querySelectorAll('[data-otp-digit]')];
    const otpSection = document.querySelector('#otp-section');
    const personalInfoSection = document.querySelector('#personal-info-section');
    const nextStepButton = document.querySelector('#step1-next');
    const gstCheckbox = document.querySelector('#has_gst');
    const gstFields = document.querySelector('#gst-fields-container');
    const loadingOverlay = document.querySelector('#loading-overlay');
    const submitButton = document.querySelector('#register-submit');
    let currentStep = 1;
    let requestedEmail = '';
    let verifiedEmail = '';

    function setVisible(element, visible) {
        element.hidden = !visible;
        element.classList.toggle('hidden', !visible);
    }

    function notify(type, title, message) {
        const toast = window.YuktioneToast?.[type];
        if (typeof toast !== 'function') {
            console.error('The shared Yuktione notification system is unavailable.');
            return;
        }
        toast(message, { title });
    }

    function updateProgress(step) {
        currentStep = step;
        registrationSteps.forEach((section) => {
            setVisible(section, Number(section.dataset.registrationStep) === step);
        });

        if (stepLabel) {
            stepLabel.textContent = `Step ${step} of ${registrationSteps.length}`;
        }
        if (progressBar && progressRegion) {
            progressBar.style.width = `${(step / registrationSteps.length) * 100}%`;
            progressRegion.setAttribute('aria-valuenow', String(step));
        }

        const heading = form.querySelector(`[data-registration-step="${step}"] h2`);
        heading?.setAttribute('tabindex', '-1');
        heading?.focus({ preventScroll: true });
        window.scrollTo({
            top: 0,
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
        });
    }

    function setError(fieldName, message) {
        const field = document.getElementById(fieldName);
        const feedback = form.querySelector(`[data-error-for="${fieldName}"]`);
        if (fieldName === 'otp') {
            otpDigits.forEach((digit) => digit.setAttribute('aria-invalid', 'true'));
        } else {
            field?.setAttribute('aria-invalid', 'true');
        }
        if (feedback) {
            feedback.textContent = message;
            setVisible(feedback, true);
        }
    }

    function clearError(fieldName) {
        const field = document.getElementById(fieldName);
        const feedback = form.querySelector(`[data-error-for="${fieldName}"]`);
        field?.removeAttribute('aria-invalid');
        if (fieldName === 'otp') {
            otpDigits.forEach((digit) => digit.removeAttribute('aria-invalid'));
        }
        if (feedback) {
            feedback.textContent = '';
            setVisible(feedback, false);
        }
    }

    function clearErrors() {
        form.querySelectorAll('[data-error-for]').forEach((feedback) => {
            feedback.textContent = '';
            setVisible(feedback, false);
        });
        form.querySelectorAll('[aria-invalid="true"]').forEach((field) => field.removeAttribute('aria-invalid'));
    }

    function focusFirstError() {
        let firstInvalid = form.querySelector('[aria-invalid="true"]');
        if (!firstInvalid) {
            return;
        }

        if (firstInvalid.id === 'otp') {
            firstInvalid = otpDigits[0];
        }
        const section = firstInvalid.closest('[data-registration-step]');
        if (section) {
            updateProgress(Number(section.dataset.registrationStep));
        }
        if (firstInvalid.matches('.file-upload-input')) {
            firstInvalid = form.querySelector(`label[for="${firstInvalid.id}"]`);
        }
        if (firstInvalid) {
            firstInvalid.tabIndex = -1;
            window.setTimeout(() => firstInvalid.focus(), 0);
        }
    }

    function validateRequired(fieldNames, message = 'This field is required.') {
        let valid = true;
        fieldNames.forEach((fieldName) => {
            const field = document.getElementById(fieldName);
            if (!field || !String(field.value || '').trim()) {
                setError(fieldName, message);
                valid = false;
            } else {
                clearError(fieldName);
            }
        });
        return valid;
    }

    function setOtpValue() {
        const value = otpDigits.map((digit) => digit.value).join('');
        otpInput.value = value;
        verifyCodeButton.disabled = value.length !== otpDigits.length;
        otpDigits.forEach((digit) => digit.removeAttribute('aria-invalid'));
        clearError('otp');
    }

    async function refreshCsrfToken() {
        const response = await fetch('/csrf-token', {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        const result = await response.json();
        if (!response.ok || typeof result.csrf_token !== 'string') {
            throw new Error('Could not refresh the security token.');
        }

        document.querySelector('meta[name="csrf-token"]').content = result.csrf_token;
        form.querySelectorAll('input[name="_token"]').forEach((token) => {
            token.value = result.csrf_token;
        });
    }

    async function postForm(url, values) {
        const send = () => {
            const body = new FormData();
            Object.entries(values).forEach(([key, value]) => body.append(key, value));
            body.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            return fetch(url, {
                method: 'POST',
                body,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                credentials: 'same-origin',
            });
        };

        let response = await send();
        if (response.status === 419) {
            await refreshCsrfToken();
            response = await send();
        }

        let data = {};
        try {
            data = await response.json();
        } catch (error) {
            if (response.ok) {
                throw error;
            }
        }
        return { response, data };
    }

    function resetOtp() {
        requestedEmail = '';
        otpInput.value = '';
        otpDigits.forEach((digit) => {
            digit.value = '';
            digit.removeAttribute('aria-invalid');
        });
        verifyCodeButton.disabled = true;
        clearError('otp');
        setVisible(otpSection, false);
        sendCodeButton.disabled = false;
        sendCodeButton.textContent = 'Send Verification Code';
    }

    async function sendOtp() {
        const email = emailInput.value.trim();
        clearError('verify-email');
        if (!emailInput.checkValidity()) {
            setError('verify-email', 'Enter a valid email address.');
            emailInput.focus();
            notify('error', 'Check your email', 'Enter a valid email address to receive a verification code.');
            return;
        }

        sendCodeButton.disabled = true;
        resendCodeButton.disabled = true;
        sendCodeButton.textContent = 'Sending code…';

        try {
            const { response, data } = await postForm(form.dataset.sendOtpUrl, { email });
            if (!response.ok || data.status !== 'success') {
                if (response.status === 419) {
                    notify('warning', 'Session expired', 'Refresh the page and try requesting a new code.');
                } else if (response.status === 422) {
                    setError('verify-email', 'This email cannot be used for a new registration. Please sign in or use another email.');
                    notify('error', 'Unable to send code', 'This email cannot be used for a new registration. Please sign in or use another email.');
                } else {
                    notify('error', 'Unable to send code', 'Please check your email address and try again.');
                }
                sendCodeButton.textContent = 'Send Verification Code';
                sendCodeButton.disabled = false;
                return;
            }

            requestedEmail = email;
            setVisible(otpSection, true);
            otpDigits[0].focus();
            sendCodeButton.textContent = 'Code sent';
            notify('success', 'Verification code sent', 'Please check your email for the verification code.');
        } catch (error) {
            console.error('Professional registration OTP request failed.', error);
            sendCodeButton.textContent = 'Send Verification Code';
            sendCodeButton.disabled = false;
            notify('error', 'Unable to send code', 'Please check your connection and try again.');
        } finally {
            resendCodeButton.disabled = false;
        }
    }

    sendCodeButton.addEventListener('click', sendOtp);
    resendCodeButton.addEventListener('click', sendOtp);

    emailInput.addEventListener('input', () => {
        clearError('verify-email');
        if (requestedEmail && emailInput.value.trim() !== requestedEmail) {
            resetOtp();
        }
    });

    otpDigits.forEach((digit, index) => {
        digit.addEventListener('input', () => {
            digit.value = digit.value.replace(/\D/g, '').slice(-1);
            setOtpValue();
            if (digit.value && index < otpDigits.length - 1) {
                otpDigits[index + 1].focus();
            }
        });

        digit.addEventListener('keydown', (event) => {
            if (event.key === 'Backspace' && !digit.value && index > 0) {
                otpDigits[index - 1].focus();
                otpDigits[index - 1].value = '';
                setOtpValue();
            } else if (event.key === 'ArrowLeft' && index > 0) {
                otpDigits[index - 1].focus();
            } else if (event.key === 'ArrowRight' && index < otpDigits.length - 1) {
                otpDigits[index + 1].focus();
            }
        });

        digit.addEventListener('paste', (event) => {
            const pasted = event.clipboardData?.getData('text').replace(/\D/g, '').slice(0, otpDigits.length);
            if (!pasted) {
                return;
            }
            event.preventDefault();
            otpDigits.forEach((input, digitIndex) => {
                input.value = pasted[digitIndex] || '';
            });
            setOtpValue();
            otpDigits[Math.min(pasted.length, otpDigits.length) - 1]?.focus();
        });
    });

    verifyCodeButton.addEventListener('click', async () => {
        if (otpInput.value.length !== 6) {
            setError('otp', 'Enter the complete 6-digit verification code.');
            otpDigits.forEach((digit) => digit.setAttribute('aria-invalid', 'true'));
            otpDigits[0].focus();
            return;
        }
        if (emailInput.value.trim() !== requestedEmail) {
            resetOtp();
            notify('warning', 'Request a new code', 'The email address changed. Send a new verification code.');
            return;
        }

        verifyCodeButton.disabled = true;
        verifyCodeButton.textContent = 'Verifying…';
        try {
            const { response, data } = await postForm(form.dataset.verifyOtpUrl, { otp: otpInput.value });
            if (!response.ok || data.status !== 'success') {
                if (response.status === 419) {
                    notify('warning', 'Session expired', 'Refresh the page and request a new verification code.');
                } else {
                    setError('otp', 'The code is incorrect or has expired. Please request a new one.');
                    otpDigits.forEach((digit) => digit.setAttribute('aria-invalid', 'true'));
                    notify('error', 'Invalid verification code', 'The code is incorrect or has expired. Please request a new one.');
                }
                return;
            }

            verifiedEmail = requestedEmail;
            emailInput.disabled = true;
            sendCodeButton.disabled = true;
            setVisible(otpSection, false);
            setVisible(personalInfoSection, true);
            let verifiedEmailInput = form.querySelector('input[name="email"]');
            if (!verifiedEmailInput) {
                verifiedEmailInput = document.createElement('input');
                verifiedEmailInput.type = 'hidden';
                verifiedEmailInput.name = 'email';
                verifiedEmailInput.id = 'verified-email';
                form.append(verifiedEmailInput);
            }
            verifiedEmailInput.value = verifiedEmail;
            nextStepButton.disabled = false;
            notify('success', 'Email verified', 'Your email address has been verified successfully.');
            document.querySelector('#first_name').focus();
        } catch (error) {
            console.error('Professional registration OTP verification failed.', error);
            notify('error', 'Unable to verify code', 'Please try again or request a new verification code.');
            verifyCodeButton.disabled = false;
        } finally {
            verifyCodeButton.textContent = 'Verify Code';
            verifyCodeButton.disabled = otpInput.value.length !== 6 || Boolean(verifiedEmail);
        }
    });

    function validatePersonalDetails() {
        clearErrors();
        let valid = validateRequired(['first_name', 'last_name', 'phone', 'password', 'password_confirmation']);
        const password = document.querySelector('#password').value;
        const confirmation = document.querySelector('#password_confirmation').value;
        if (password && password.length < 6) {
            setError('password', 'Password must be at least 6 characters.');
            valid = false;
        }
        if (confirmation && password !== confirmation) {
            setError('password_confirmation', 'Passwords do not match.');
            valid = false;
        }
        if (!verifiedEmail) {
            valid = false;
            notify('warning', 'Verify your email', 'Verify your email address before continuing.');
        }
        if (!valid) {
            focusFirstError();
        }
        return valid;
    }

    function validateProfessionalDetails() {
        clearErrors();
        const valid = validateRequired(['specialization', 'experience', 'address', 'education']);
        if (!valid) {
            focusFirstError();
            notify('warning', 'Complete this step', 'Please fill in the required professional details.');
        }
        return valid;
    }

    function validateDocuments() {
        clearErrors();
        let valid = validateRequired(['qualification_document', 'id_proof_document']);
        if (gstCheckbox.checked) {
            valid = validateRequired(['gst_number', 'state_code', 'gst_address']) && valid;
            valid = validateRequired(['gst_certificate']) && valid;
        }

        if (!valid) {
            focusFirstError();
            notify('warning', 'Complete this step', 'Please add the required documents and details.');
        }
        return valid;
    }

    nextStepButton.addEventListener('click', () => {
        if (verifiedEmail && validatePersonalDetails()) {
            updateProgress(2);
        }
    });

    form.querySelectorAll('.next-btn').forEach((button) => {
        button.addEventListener('click', () => {
            if (currentStep === 2 && validateProfessionalDetails()) {
                updateProgress(3);
            }
        });
    });

    form.querySelectorAll('.prev-btn').forEach((button) => {
        button.addEventListener('click', () => updateProgress(Math.max(1, currentStep - 1)));
    });

    form.addEventListener('input', (event) => {
        if (event.target.id) {
            clearError(event.target.id);
        }
    });
    form.addEventListener('change', (event) => {
        if (event.target.id) {
            clearError(event.target.id);
        }
    });

    gstCheckbox.addEventListener('change', () => {
        setVisible(gstFields, gstCheckbox.checked);
        if (!gstCheckbox.checked) {
            ['gst_number', 'state_code', 'gst_address', 'state_name', 'gst_certificate'].forEach((name) => {
                const field = document.getElementById(name);
                if (field) {
                    field.value = '';
                    field.removeAttribute('required');
                    clearError(name);
                }
            });
        }
    });

    document.querySelector('#state_code').addEventListener('change', (event) => {
        const selectedState = event.target.selectedOptions[0]?.dataset.state || '';
        let stateName = form.querySelector('input[name="state_name"]');
        if (!stateName) {
            stateName = document.createElement('input');
            stateName.type = 'hidden';
            stateName.name = 'state_name';
            stateName.id = 'state_name';
            form.append(stateName);
        }
        stateName.value = selectedState;
    });

    document.querySelectorAll('.file-upload-input').forEach((input) => {
        input.addEventListener('change', () => {
            const preview = document.getElementById(`${input.id}_preview`);
            const label = form.querySelector(`label[for="${input.id}"].file-upload-label`);
            const labelText = label?.querySelector('.file-upload-text');
            preview.replaceChildren();
            clearError(input.id);

            const file = input.files?.[0];
            if (!file) {
                if (labelText) {
                    labelText.textContent = input.id === 'qualification_document'
                        ? 'Choose qualification document'
                        : input.id === 'id_proof_document'
                            ? 'Choose Aadhaar or PAN document'
                            : input.id === 'gst_certificate'
                                ? 'Choose GST certificate'
                                : 'Choose profile photo';
                }
                return;
            }

            const maxSize = input.id === 'profile_photo' ? 5 * 1024 * 1024 : 10 * 1024 * 1024;
            if (file.size > maxSize) {
                input.value = '';
                setError(input.id, `This file is too large. Maximum size is ${input.id === 'profile_photo' ? '5' : '10'} MB.`);
                return;
            }

            if (labelText) {
                labelText.textContent = file.name;
            }
            const item = document.createElement('div');
            item.className = 'flex items-center justify-between gap-2 rounded-control border border-divider bg-surface-muted px-3 py-2.5 text-xs text-text-muted';
            const name = document.createElement('span');
            name.className = 'min-w-0';
            name.textContent = `${file.name} · ${(file.size / (1024 * 1024)).toFixed(1)} MB`;
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'shrink-0 rounded-control px-3 py-1 text-xs font-medium text-error hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring';
            remove.textContent = 'Remove';
            remove.setAttribute('aria-label', `Remove ${file.name}`);
            remove.addEventListener('click', () => {
                input.value = '';
                preview.replaceChildren();
                setVisible(preview, false);
                if (labelText) {
                    labelText.textContent = input.id === 'qualification_document'
                        ? 'Choose qualification document'
                        : input.id === 'id_proof_document'
                            ? 'Choose Aadhaar or PAN document'
                            : input.id === 'gst_certificate'
                                ? 'Choose GST certificate'
                                : 'Choose profile photo';
                }
            });
            item.append(name, remove);
            preview.append(item);
            setVisible(preview, true);
        });
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!verifiedEmail) {
            updateProgress(1);
            notify('warning', 'Verify your email', 'Verify your email address before submitting your application.');
            return;
        }
        if (!validatePersonalDetails()) {
            updateProgress(1);
            return;
        }
        if (!validateProfessionalDetails()) {
            updateProgress(2);
            return;
        }
        if (!validateDocuments()) {
            updateProgress(3);
            return;
        }

        clearErrors();
        loadingOverlay.hidden = false;
        loadingOverlay.classList.remove('hidden');
        loadingOverlay.classList.add('flex');
        submitButton.disabled = true;
        try {
            const body = new FormData(form);
            const send = () => fetch(form.action, {
                method: 'POST',
                body,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                credentials: 'same-origin',
            });

            let response = await send();
            if (response.status === 419) {
                await refreshCsrfToken();
                body.set('_token', document.querySelector('meta[name="csrf-token"]').content);
                response = await send();
            }

            let data = {};
            try {
                data = await response.json();
            } catch (error) {
                if (response.ok) {
                    throw error;
                }
            }

            if (!response.ok || data.status !== 'success') {
                if (response.status === 422 && data.errors) {
                    Object.entries(data.errors).forEach(([field, messages]) => {
                        const targetField = field === 'state_name' ? 'state_code' : field;
                        if (document.getElementById(targetField)) {
                            setError(targetField, messages[0]);
                        }
                    });
                    focusFirstError();
                    notify('error', 'Check your application', 'Please review the highlighted fields and try again.');
                } else if (response.status === 419) {
                    notify('warning', 'Session expired', 'Refresh the page and try submitting your application again.');
                } else {
                    notify('error', 'Unable to submit application', 'Something went wrong. Please try again later.');
                }
                return;
            }

            notify('success', 'Application submitted', 'Your registration is under review.');
            if (data.redirect_url) {
                window.setTimeout(() => {
                    window.location.assign(data.redirect_url);
                }, 900);
            }
        } catch (error) {
            console.error('Professional registration submission failed.', error);
            notify('error', 'Unable to submit application', 'Please check your connection and try again.');
        } finally {
            loadingOverlay.hidden = true;
            loadingOverlay.classList.remove('flex');
            loadingOverlay.classList.add('hidden');
            submitButton.disabled = false;
        }
    });

    updateProgress(1);
}
