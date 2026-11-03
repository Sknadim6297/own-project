@extends('layouts.layout')

@section('title', ($career->title ?? 'Job') . ' | Careers | YuktiOne')
@section('meta_description', 'Job details and application for ' . ($career->title ?? 'an open role') . ' at YuktiOne.')

@section('content')
<x-yuktione.page
    class="yk-job"
    title="{{ $career->title }}"
    lead="Review the role details, then apply when you are ready."
    crumb="Careers"
>
    <p class="yk-doc__actions" style="margin: 0 0 1rem;">
        <a class="yk-doc__button-secondary" href="{{ route('career') }}">All open roles</a>
    </p>

    <ul class="yk-job__facts">
        @if ($career->job_type)
            <li>{{ $career->job_type }}</li>
        @endif
        @if ($career->location)
            <li>{{ $career->location }}</li>
        @endif
        @if ($career->department)
            <li>{{ $career->department }}</li>
        @endif
    </ul>

    <div class="yk-doc__article">
        <h2 class="yk-job__section" style="margin-bottom: 0.85rem;">Job description</h2>

        @if ($career->description)
            <section class="yk-job__section">
                <div class="yk-job__html">{!! $career->description !!}</div>
            </section>
        @endif

        @if ($career->who_are_we)
            <section class="yk-job__section">
                <h2>Who are we?</h2>
                <div class="yk-job__html">{!! $career->who_are_we !!}</div>
            </section>
        @endif

        @if ($career->expectation_of_role)
            <section class="yk-job__section">
                <h2>Expectation of the role</h2>
                <div class="yk-job__html">{!! $career->expectation_of_role !!}</div>
            </section>
        @endif

        @if ($career->accounts_payable_payroll)
            <section class="yk-job__section">
                <h2>Accounts Payable &amp; Payroll</h2>
                <div class="yk-job__html">{!! $career->accounts_payable_payroll !!}</div>
            </section>
        @endif

        @if ($career->accounts_receivable)
            <section class="yk-job__section">
                <h2>Accounts Receivable</h2>
                <div class="yk-job__html">{!! $career->accounts_receivable !!}</div>
            </section>
        @endif

        @if ($career->financial_reporting)
            <section class="yk-job__section">
                <h2>Financial Reporting</h2>
                <div class="yk-job__html">{!! $career->financial_reporting !!}</div>
            </section>
        @endif

        @if ($career->requirements)
            <section class="yk-job__section">
                <h2>Requirements</h2>
                <div class="yk-job__html">{!! $career->requirements !!}</div>
            </section>
        @endif

        @if ($career->what_we_offer)
            <section class="yk-job__section">
                <h2>What We Offer</h2>
                <div class="yk-job__html">{!! $career->what_we_offer !!}</div>
            </section>
        @endif
    </div>

    <div class="yk-job__actions">
        <button type="button" class="yk-doc__button" data-bs-toggle="modal" data-bs-target="#jobApplicationModal">Apply</button>
        <button type="button" class="yk-doc__button-secondary" id="shareJobBtn">Share job</button>
        <div class="yk-job__share-menu" id="shareDropdown">
            <a href="#" class="share-link" data-platform="facebook" target="_blank" rel="noopener noreferrer"><i class="fab fa-facebook"></i><span>Facebook</span></a>
            <a href="#" class="share-link" data-platform="twitter" target="_blank" rel="noopener noreferrer"><i class="fab fa-twitter"></i><span>X</span></a>
            <a href="#" class="share-link" data-platform="linkedin" target="_blank" rel="noopener noreferrer"><i class="fab fa-linkedin"></i><span>LinkedIn</span></a>
            <a href="#" class="share-link" data-platform="whatsapp" target="_blank" rel="noopener noreferrer"><i class="fab fa-whatsapp"></i><span>WhatsApp</span></a>
            <a href="#" class="share-link" data-platform="email" target="_blank" rel="noopener noreferrer"><i class="fas fa-envelope"></i><span>Email</span></a>
            <a href="#" class="share-link" data-platform="copy" id="copyLinkBtn"><i class="fas fa-link"></i><span>Copy Link</span></a>
        </div>
    </div>
</x-yuktione.page>
<!-- Job Application Modal -->
<div class="modal fade yk-job-modal" id="jobApplicationModal" tabindex="-1" aria-labelledby="jobApplicationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="jobApplicationModalLabel">Apply for {{ $career->title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="jobApplicationForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="career_id" value="{{ $career->id }}">
                <div class="modal-body">
                    <!-- Navigation Tabs -->
                    <ul class="nav nav-tabs mb-4" id="applicationTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab">My information</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="questions-tab" data-bs-toggle="tab" data-bs-target="#questions" type="button" role="tab">Questions</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button" role="tab">Documents</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="applicationTabContent">
                        <!-- My Information Tab -->
                        <div class="tab-pane fade show active" id="info" role="tabpanel">
                            <h6 class="mb-3" style="color: #0b2048; font-size: 1.15rem; font-weight: 700;">My information</h6>
                            <p class="mb-4" style="color: #636e72;">Fill out the information below</p>

                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required placeholder="Full name">
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required placeholder="Your email address">
                            </div>

                            <div class="mb-3">
                                <label for="phone_number" class="form-label">Phone number <span class="text-danger">*</span></label>
                                <div class="row">
                                    <div class="col-4">
                                        <select class="form-control" id="phone_country" name="phone_country">
                                            <option value="44" selected>🇬🇧 +44</option>
                                            <option value="91">🇮🇳 +91</option>
                                            <option value="1">🇺🇸 +1</option>
                                            <option value="86">🇨🇳 +86</option>
                                        </select>
                                    </div>
                                    <div class="col-8">
                                        <input type="text" class="form-control" id="phone_number" name="phone_number" required placeholder="Phone number">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Questions Tab -->
                        <div class="tab-pane fade" id="questions" role="tabpanel">
                            <h6 class="mb-3" style="color: #0b2048; font-size: 1.15rem; font-weight: 700;">Questions</h6>
                            <p class="mb-4" style="color: #636e72;">Please fill in additional questions</p>

                            <div class="mb-3">
                                <label for="compensation_expectation" class="form-label" style="color: #316d61;">What is your compensation expectation for this role? (GBP/gross) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="compensation_expectation" name="compensation_expectation" required placeholder="e.g., £30,000 - £40,000">
                            </div>

                            <div class="mb-3">
                                <label for="why_perfect_fit" class="form-label" style="color: #316d61;">What makes you the perfect fit for this role? <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="why_perfect_fit" name="why_perfect_fit" rows="5" required placeholder="Tell us why you're the perfect fit..."></textarea>
                            </div>

                            <p class="text-muted small">All fields marked with * are required.</p>
                        </div>

                        <!-- Documents Tab -->
                        <div class="tab-pane fade" id="documents" role="tabpanel">
                            <h6 class="mb-3" style="color: #0b2048; font-size: 1.15rem; font-weight: 700;">Documents</h6>

                            <div class="mb-4">
                                <label for="cv_resume" class="form-label" style="color: #316d61;">CV or resume <span class="text-danger">*</span></label>
                                <p class="small text-muted mb-2">Upload your CV or resume file</p>
                                <div class="upload-box border rounded p-4 text-center" style="border-color: #e9ecef; background: #f8f9fa; cursor: pointer;" onclick="document.getElementById('cv_resume').click();">
                                    <input type="file" class="form-control d-none" id="cv_resume" name="cv_resume" accept=".pdf,.doc,.docx,.jpeg,.jpg,.png" required>
                                    <span style="color: #316d61; text-decoration: underline; cursor: pointer;">Upload a file</span> or drag and drop here
                                    <p class="small text-muted mt-2 mb-0">Accepted files: PDF, DOC, DOCX, JPEG and PNG</p>
                                    <p class="small text-muted">up to 50MB.</p>
                                    <div id="cv_file_name" class="mt-2 text-success"></div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="cover_letter_file" class="form-label" style="color: #316d61;">Cover letter</label>
                                <p class="small text-muted mb-2">Upload your cover letter</p>
                                <div class="upload-box border rounded p-4 text-center" style="border-color: #e9ecef; background: #f8f9fa; cursor: pointer;" onclick="document.getElementById('cover_letter_file').click();">
                                    <input type="file" class="form-control d-none" id="cover_letter_file" name="cover_letter_file" accept=".pdf,.doc,.docx,.jpeg,.jpg,.png">
                                    <span style="color: #316d61; text-decoration: underline; cursor: pointer;">Upload a file</span> or drag and drop here
                                    <p class="small text-muted mt-2 mb-0">Accepted files: PDF, DOC, DOCX, JPEG and PNG</p>
                                    <p class="small text-muted">up to 50MB.</p>
                                    <div id="cover_file_name" class="mt-2 text-success"></div>
                                </div>
                                <a href="#" class="text-decoration-none" style="color: #316d61;" id="writeCoverLetterToggle">
                                    <i class="fas fa-edit"></i> Write it here instead
                                </a>
                            </div>

                            <div class="mb-3" id="coverLetterTextArea" style="display: none;">
                                <label for="cover_letter_text" class="form-label" style="color: #316d61;">Cover Letter Text</label>
                                <textarea class="form-control" id="cover_letter_text" name="cover_letter_text" rows="6" placeholder="Write your cover letter here..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div id="formError" class="alert alert-danger" style="display: none;"></div>
                    <div id="formSuccess" class="alert alert-success" style="display: none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background: #316d61; border-color: #316d61;">Send</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('jobApplicationForm');
    if (!form) return;
    
    const cvInput = document.getElementById('cv_resume');
    const coverInput = document.getElementById('cover_letter_file');
    const coverTextArea = document.getElementById('coverLetterTextArea');
    const writeCoverToggle = document.getElementById('writeCoverLetterToggle');

    if (!cvInput || !coverInput || !coverTextArea || !writeCoverToggle) return;

    // File name display
    cvInput.addEventListener('change', function(e) {
        e.stopPropagation();
        if (e.target.files.length > 0) {
            document.getElementById('cv_file_name').textContent = 'Selected: ' + e.target.files[0].name;
        }
    });

    coverInput.addEventListener('change', function(e) {
        e.stopPropagation();
        if (e.target.files.length > 0) {
            document.getElementById('cover_file_name').textContent = 'Selected: ' + e.target.files[0].name;
        }
    });
    
    // Prevent file input click from triggering parent click
    cvInput.addEventListener('click', function(e) {
        e.stopPropagation();
    });
    
    coverInput.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    // Toggle cover letter text area
    writeCoverToggle.addEventListener('click', function(e) {
        e.preventDefault();
        coverTextArea.style.display = coverTextArea.style.display === 'none' ? 'block' : 'none';
        if (coverTextArea.style.display === 'block') {
            coverInput.removeAttribute('required');
        }
    });

    // Share job functionality
    const shareJobBtn = document.getElementById('shareJobBtn');
    const shareDropdown = document.getElementById('shareDropdown');
    const shareLinks = document.querySelectorAll('.share-link');
    
    if (shareJobBtn && shareDropdown) {
        const jobUrl = window.location.href;
        const jobTitle = {!! json_encode($career->title) !!};
        @php
            $shareContent = [];
            $shareContent[] = 'Job Title: ' . $career->title;
            
            if ($career->location) {
                $shareContent[] = 'Location: ' . $career->location;
            }
            if ($career->job_type) {
                $shareContent[] = 'Job Type: ' . $career->job_type;
            }
            if ($career->department) {
                $shareContent[] = 'Department: ' . $career->department;
            }
            
            $shareContent[] = '';
            $shareContent[] = 'DESCRIPTION:';
            if ($career->description) {
                $shareContent[] = strip_tags($career->description);
            }
            
            if ($career->who_are_we) {
                $shareContent[] = '';
                $shareContent[] = 'WHO ARE WE:';
                $shareContent[] = strip_tags($career->who_are_we);
            }
            
            if ($career->expectation_of_role) {
                $shareContent[] = '';
                $shareContent[] = 'EXPECTATION OF THE ROLE:';
                $shareContent[] = strip_tags($career->expectation_of_role);
            }
            
            if ($career->accounts_payable_payroll) {
                $shareContent[] = '';
                $shareContent[] = 'ACCOUNTS PAYABLE & PAYROLL:';
                $shareContent[] = strip_tags($career->accounts_payable_payroll);
            }
            
            if ($career->accounts_receivable) {
                $shareContent[] = '';
                $shareContent[] = 'ACCOUNTS RECEIVABLE:';
                $shareContent[] = strip_tags($career->accounts_receivable);
            }
            
            if ($career->financial_reporting) {
                $shareContent[] = '';
                $shareContent[] = 'FINANCIAL REPORTING:';
                $shareContent[] = strip_tags($career->financial_reporting);
            }
            
            if ($career->requirements) {
                $shareContent[] = '';
                $shareContent[] = 'REQUIREMENTS:';
                $shareContent[] = strip_tags($career->requirements);
            }
            
            if ($career->what_we_offer) {
                $shareContent[] = '';
                $shareContent[] = 'WHAT WE OFFER:';
                $shareContent[] = strip_tags($career->what_we_offer);
            }
            
            $shareContent[] = '';
            $shareContent[] = 'View full details and apply: ' . url()->current();
            
            $fullShareText = implode("\n", $shareContent);
            $fullShareText = json_encode($fullShareText);
        @endphp
        const fullShareText = {!! $fullShareText !!};
        const shortShareText = jobTitle + '\n\n' + {!! json_encode(Str::limit(strip_tags($career->description ?? ""), 150)) !!} + '...\n\nView full details: ' + jobUrl;
        
        // Toggle dropdown
        shareJobBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            shareDropdown.classList.toggle('is-open'); shareDropdown.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!shareJobBtn.contains(e.target) && !shareDropdown.contains(e.target)) {
                shareDropdown.classList.remove('is-open'); shareDropdown.classList.remove('show');
            }
        });
        
        // Set up share links
        shareLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const platform = this.getAttribute('data-platform');
                let shareUrl = '';
                
                switch(platform) {
                    case 'facebook':
                        shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(jobUrl)}&quote=${encodeURIComponent(shortShareText)}`;
                        break;
                    case 'twitter':
                        const twitterText = jobTitle + '\n\n' + {!! json_encode(Str::limit(strip_tags($career->description ?? ""), 100)) !!} + '...\n\n' + jobUrl;
                        shareUrl = `https://twitter.com/intent/tweet?url=${encodeURIComponent(jobUrl)}&text=${encodeURIComponent(twitterText)}`;
                        break;
                    case 'linkedin':
                        shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(jobUrl)}`;
                        break;
                    case 'whatsapp':
                        shareUrl = `https://wa.me/?text=${encodeURIComponent(fullShareText)}`;
                        break;
                    case 'email':
                        shareUrl = `mailto:?subject=${encodeURIComponent('Job Opportunity: ' + jobTitle)}&body=${encodeURIComponent(fullShareText)}`;
                        break;
                    case 'copy':
                        e.preventDefault();
                        navigator.clipboard.writeText(fullShareText).then(function() {
                            const originalText = this.querySelector('span').textContent;
                            this.querySelector('span').textContent = 'Copied!';
                            setTimeout(() => {
                                this.querySelector('span').textContent = originalText;
                            }, 2000);
                        }.bind(this));
                        shareDropdown.classList.remove('is-open'); shareDropdown.classList.remove('show');
                        return;
                }
                
                if (shareUrl) {
                    window.open(shareUrl, '_blank', 'width=600,height=400');
                    shareDropdown.classList.remove('is-open'); shareDropdown.classList.remove('show');
                }
            });
        });
    }

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Disable submit button to prevent double submission
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';
        }
        
        const formData = new FormData(form);
        const errorDiv = document.getElementById('formError');
        const successDiv = document.getElementById('formSuccess');
        
        if (errorDiv) errorDiv.style.display = 'none';
        if (successDiv) successDiv.style.display = 'none';

        fetch('{{ route("job.application.store") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(async response => {
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.includes("application/json")) {
                const data = await response.json();
                if (!response.ok) {
                    throw data;
                }
                return data;
            } else {
                // If response is not JSON, it's likely an HTML error page
                const text = await response.text();
                throw {
                    success: false,
                    message: 'Server returned an unexpected response. Please try again.',
                    errors: {}
                };
            }
        })
        .then(data => {
            if (data.success) {
                if (successDiv) {
                    successDiv.textContent = data.message;
                    successDiv.style.display = 'block';
                }
                form.reset();
                const cvFileName = document.getElementById('cv_file_name');
                const coverFileName = document.getElementById('cover_file_name');
                if (cvFileName) cvFileName.textContent = '';
                if (coverFileName) coverFileName.textContent = '';
                if (coverTextArea) coverTextArea.style.display = 'none';
                
                // Reset tabs to first tab
                const firstTab = document.getElementById('info-tab');
                const firstTabPane = document.getElementById('info');
                if (firstTab && firstTabPane) {
                    document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
                    document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('show', 'active'));
                    firstTab.classList.add('active');
                    firstTabPane.classList.add('show', 'active');
                }
                
                setTimeout(function() {
                    const modalElement = document.getElementById('jobApplicationModal');
                    if (modalElement && typeof bootstrap !== 'undefined') {
                        const modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) {
                            modal.hide();
                        }
                    }
                    if (successDiv) successDiv.style.display = 'none';
                }, 2000);
            } else {
                throw new Error(data.message || 'An error occurred');
            }
        })
        .catch(error => {
            console.error('Form submission error:', error);
            
            let errorMessage = 'Failed to submit application. Please try again.';
            let errorList = '';
            
            if (error.message) {
                errorMessage = error.message;
            }
            
            if (error.errors && typeof error.errors === 'object') {
                const errorArray = [];
                for (const key in error.errors) {
                    if (error.errors.hasOwnProperty(key)) {
                        errorArray.push(...error.errors[key]);
                    }
                }
                if (errorArray.length > 0) {
                    errorList = '<ul style="margin: 10px 0; padding-left: 20px;">' + 
                                errorArray.map(err => '<li>' + err + '</li>').join('') + 
                                '</ul>';
                }
            }
            
            if (errorDiv) {
                errorDiv.innerHTML = errorMessage + errorList;
                errorDiv.style.display = 'block';
                // Scroll to error
                errorDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else {
                alert(errorMessage);
            }
        })
        .finally(() => {
            // Re-enable submit button
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Send';
            }
        });
    });
});
</script>
@endsection
