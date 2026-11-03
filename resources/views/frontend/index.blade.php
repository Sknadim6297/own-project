<!-- /header -->
@extends('layouts.layout')
@section('styles')
   {{-- <link rel="stylesheet" href="{{ asset('admin/css/styles.css') }}" /> --}}
   <link rel="stylesheet" href="{{ asset('frontend/assets/css/newslidertwo.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/assets/css/newsliders.css') }}">
	<link rel="stylesheet" href="{{ asset('frontend/assets/css/responsive2.css') }}" media="screen and (max-width: 992px)">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
	 <style>
        html, body {
            overflow-x: hidden;
            width: 100%;
        }

        main {
            overflow-x: hidden;
        }

		@media only screen and (min-width: 768px) and (max-width: 1024px) {

			.fun-facts-cards .container .card-two {
    			margin-top: 20px;
    
		}

		.fun-facts-cards .container .right .row .third-card .card-three{
			margin-top: 14px;
		}

		.fun-facts-cards .container .card {
			margin-right: 12px
		}

		}
		
    </style>
	<style>
    @media (max-width: 768px) {
        /* Popular Services Mobile */
        .popular-services-main-section {
            padding: 40px 0;
        }
        
        .popular-services-title {
            font-size: 1.8rem;
            margin-bottom: 30px;
        }
        
        .popular-service-card {
            width: 180px;
            height: 200px;
            min-width: 180px;
            min-height: 200px;
            max-width: 180px;
            max-height: 200px;
        }
        
        .service-graphic {
            height: 130px;
            padding: 12px;
        }
        
        .service-content {
            min-height: 70px;
            padding: 15px;
        }
        
        .service-title {
            font-size: 1rem;
        }
    }
    /* Popular Services Section Styles */
    .popular-services-main-section {
        background-color: white;
        padding: 60px 0;
    }

    .popular-services-title {
        font-size: 2.2rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 40px;
        text-align: left;
    }

    .popular-services-scroll-container {
        position: relative;
        overflow: hidden;
    }

    .popular-services-wrapper {
        display: flex;
        gap: 20px;
        overflow-x: auto;
        scroll-behavior: smooth;
        padding-bottom: 10px;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .popular-services-wrapper::-webkit-scrollbar {
        display: none;
    }

    .popular-service-card {
        width: 200px;
        height: 275px;
        min-width: 200px;
        min-height: 275px;
        max-width: 200px;
        max-height: 275px;
        background: #0d1d53;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        text-decoration: none;
        color: inherit;
        cursor: pointer;
    }

    .service-graphic {
        height: 250px;
        overflow: hidden;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding: 5px;
        flex-shrink: 0;
    }

    .service-mini-card {
        width: 100%;
        height: 100%;
        border-radius: 8px;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .service-image {
        width: 200px !important;
        height: 150px !important;
 		/* margin-top: -10px; */
        object-fit: cover;
        opacity: 0.9;
        border-radius: 15px;
    }

    .service-content {
        padding: 20px;
        text-align: left;
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        min-height: 70px;
    }

    .service-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin: 0;
        color: white;
    }

    /* Scroll Arrow */
    .scroll-arrow-right {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: white;
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 10;
    }


    .scroll-arrow-right i {
        color: #2c5530;
        font-size: 1.2rem;
    }

    /* Mini Card Styles - White Background */
    .coding-card, .webdev-card, .video-card, .software-card, .seo-card, .architecture-card {
        background: #FFFFFF;
        border: 2px solid #000000;
    }

    /* New Card Content Styles */
    
    /* Common Elements */
    .dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: rgba(44, 85, 48, 0.6);
        margin: 0 1px;
    }

    /* Coding Card */
    .code-screen {
        width: 100%;
        height: 100%;
        padding: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .terminal-window {
        width: 90%;
        height: 80%;
        background: #1a1a1a;
        border-radius: 4px;
        padding: 6px;
        color: #00ff00;
    }

    .terminal-header {
        display: flex;
        align-items: center;
        margin-bottom: 4px;
    }

    .terminal-dots {
        display: flex;
        gap: 3px;
    }

    .terminal-content {
        font-size: 0.6rem;
        font-family: monospace;
    }

    .code-line {
        color: #87CEEB;
        margin-bottom: 2px;
    }

    .cursor-blink {
        color: #00ff00;
        animation: blink 1s infinite;
    }

    @keyframes blink {
        0%, 50% { opacity: 1; }
        51%, 100% { opacity: 0; }
    }

    /* Website Dev Card */
    .website-preview {
        width: 100%;
        height: 100%;
        padding: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .browser-window {
        width: 90%;
        height: 80%;
        background: white;
        border-radius: 4px;
        overflow: hidden;
    }

    .browser-header {
        background: #f0f0f0;
        padding: 4px 6px;
        display: flex;
        align-items: center;
    }

    .browser-dots {
        display: flex;
        gap: 3px;
    }

    .website-content {
        padding: 8px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
    }

    .web-logo {
        width: 16px;
        height: 16px;
        background: #2c5530;
        border-radius: 2px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 0.6rem;
        font-weight: bold;
    }

    .web-nav {
        display: flex;
        gap: 4px;
    }

    .nav-item {
        width: 12px;
        height: 2px;
        background: #ddd;
        border-radius: 1px;
    }

    /* Video Card */
    .video-editor {
        width: 100%;
        height: 100%;
        padding: 8px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .video-player {
        width: 70%;
        height: 60%;
    }

    .video-screen {
        width: 100%;
        height: 100%;
        background: #333;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .video-content {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .play-icon {
        color: white;
        font-size: 0.8rem;
    }

    .video-timeline {
        width: 80%;
    }

    .timeline-track {
        height: 4px;
        background: #ddd;
        border-radius: 2px;
        position: relative;
    }

    .timeline-progress {
        height: 100%;
        width: 60%;
        background: #2c5530;
        border-radius: 2px;
    }

    /* Software Card */
    .app-development {
        width: 100%;
        height: 100%;
        padding: 8px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .app-window {
        width: 80%;
        height: 60%;
        background: white;
        border-radius: 4px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .app-header {
        background: #2c5530;
        padding: 4px 6px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .app-title {
        color: white;
        font-size: 0.6rem;
        font-weight: bold;
    }

    .app-content {
        padding: 6px;
    }

    .code-lines {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .code-line-small {
        height: 2px;
        background: #ddd;
        border-radius: 1px;
    }

    .code-line-small:nth-child(1) { width: 80%; }
    .code-line-small:nth-child(2) { width: 60%; }
    .code-line-small:nth-child(3) { width: 90%; }

    .app-icons {
        display: flex;
        gap: 4px;
    }

    .app-icon {
        width: 8px;
        height: 8px;
        background: #2c5530;
        border-radius: 2px;
    }

    /* SEO Card */
    .seo-dashboard {
        width: 100%;
        height: 100%;
        padding: 8px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .search-engine {
        width: 90%;
        height: 40%;
    }

    .search-bar {
        width: 100%;
        height: 16px;
        background: white;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #ddd;
    }

    .search-icon {
        font-size: 0.6rem;
        color: #666;
    }

    .ranking-chart {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
    }

    .rank-bars {
        display: flex;
        align-items: end;
        gap: 2px;
        height: 20px;
    }

    .rank-bar {
        width: 4px;
        border-radius: 1px;
        background: #2c5530;
    }

    .rank-1 { height: 16px; }
    .rank-2 { height: 12px; }
    .rank-3 { height: 8px; }

    .ranking-text {
        font-size: 0.5rem;
        font-weight: bold;
        color: #2c5530;
    }

    /* Architecture Card */
    .design-studio {
        width: 100%;
        height: 100%;
        padding: 8px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .blueprint {
        width: 90%;
        height: 70%;
        background: rgba(255,255,255,0.8);
        border-radius: 4px;
        position: relative;
        border: 1px solid #ddd;
    }

    .floor-plan {
        width: 100%;
        height: 100%;
        position: relative;
        padding: 6px;
    }

    .room-outline {
        width: 100%;
        height: 100%;
        border: 1px solid #4A90E2;
        border-radius: 2px;
        position: relative;
    }

    .room-furniture {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
    }

    .furniture-chair {
        width: 8px;
        height: 6px;
        background: #4A90E2;
        border-radius: 1px;
    }

    .furniture-table {
        width: 12px;
        height: 8px;
        background: #87CEEB;
        border-radius: 1px;
    }

    .design-tools {
        display: flex;
        gap: 4px;
        align-items: center;
    }

    .ruler {
        width: 12px;
        height: 2px;
        background: #4A90E2;
        border-radius: 1px;
    }

    .pencil {
        width: 8px;
        height: 2px;
        background: #87CEEB;
        border-radius: 1px;
    }
	.btn-custom{
		    background: linear-gradient(135deg, #152a70, #c51010, #f39c12);
			border: none;
			color: white;
	}
	.text{
		color: white;
	}
.login-popup-title {
        font-size: 1.5rem !important;
        color: white !important;
    }

    .login-popup-title .wave-icon {
        font-size: 1.8rem !important;
    }

    .login-popup-custom {
        background: linear-gradient(135deg, #152a70, #c51010, #f39c12) !important;
        color: white !important;
        border-radius: 15px !important;
        padding: 25px 20px !important;
    }

    .login-popup-btn {
        background-color: #1e0d60 !important;
        color: white !important;
        font-size: 1.2rem !important;
        font-weight: 600 !important;
        padding: 12px 30px !important;
        border-radius: 50px !important;
        border: none !important;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2) !important;
    }

    .login-popup-btn:hover {
        transform: translateY(-3px) !important;
        box-shadow: 0 6px 20px rgba(0,0,0,0.3) !important;
    }
    
    .login-popup-close {
        color: white !important;
        opacity: 0.8 !important;
        font-size: 1.5rem !important;
    }
    
    .login-popup-close:hover {
        opacity: 1 !important;
    }

    /* New Launches Section Styling */
    .new-launches-section {
        background-color: #f8f9fa !important;
        padding: 80px 0 !important;
    }

    .launch-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 6px 20px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        height: 100%;
        cursor: pointer;
        position: relative;
    }

    .launch-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(0,0,0,0.12);
    }

    .launch-image {
        height: 180px;
        overflow: hidden;
        position: relative;
    }

    .launch-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .launch-card:hover .launch-image img {
        transform: scale(1.08);
    }

    .launch-content {
        padding: 20px 18px;
        text-align: center;
    }

    .launch-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
    }

    .launch-description {
        font-size: 0.9rem;
        color: #666;
        margin: 0;
        line-height: 1.4;
    }

    /* Interactive hover effect for launch cards */
    .launch-card::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(255,152,0,0.1), rgba(255,87,34,0.1));
        opacity: 0;
        transition: opacity 0.3s ease;
        pointer-events: none;
    }

    .launch-card:hover::after {
        opacity: 1;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .new-launches-section {
            padding: 60px 0 !important;
        }
        
        .launch-card {
            margin-bottom: 20px;
        }
        .launch-image {
            height: 150px;
        }
        
        .launch-content {
            padding: 18px 15px;
        }
        .launch-title {
            font-size: 1.1rem;
        }
    }

    @media (max-width: 576px) {
        .new-launches-section {
            padding: 40px 0 !important;
        }
        
        .launch-image {
            height: 130px;
        }
        
        .launch-content {
            padding: 15px 12px;
        }
        .launch-title {
            font-size: 1rem;
            margin-bottom: 6px;
        }
        
        .launch-description {
            font-size: 0.85rem;
        }
    }

    /* More FAQs Button Styles */
    .btn-outline-primary:hover {
        background-color: #1DBF73 !important;
        border-color: #1DBF73 !important;
        color: white !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(29, 191, 115, 0.3);
    }

    .btn-outline-primary:focus {
        box-shadow: 0 0 0 0.2rem rgba(29, 191, 115, 0.25);
    }
</style>
@endsection
@section('content')
	<main>
		<!-- Notification Section -->
		@if(session('warning'))
			<div class="alert alert-warning alert-dismissible fade show" role="alert" style="margin: 0; border-radius: 0;">
				<div class="container">
					<strong>Notice:</strong> {{ session('warning') }}
					<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
				</div>
			</div>
		@endif
		
		@include('frontend.home.partials.hero')

		@include('frontend.home.partials.categories')

		@include('frontend.home.partials.tools')

		@include('frontend.home.partials.insights')

		@include('frontend.home.partials.talk-experts')
		
		{{-- Popular Services temporarily hidden — uncomment to restore.
		<section class="popular-services-main-section py-5" style="background-color: white;">
			<div class="container">
				<div class="row">
					<div class="col-12">
						<h2 class="popular-services-title">Popular services</h2>
						<div class="popular-services-scroll-container">
							<div class="popular-services-wrapper" id="popularServicesScroll">
								@foreach($services as $service)
								<!-- Service Card: {{ $service->name }} -->
								<a href="{{ route('service.show', $service->slug) }}" class="popular-service-card">
									<div class="service-content">
										<h3 class="service-title">{{ $service->name }}</h3>
									</div>
									<div class="service-graphic">
										<div class="service-mini-card">
											<img src="{{ $service->image ? asset('storage/' . $service->image) : asset('frontend/assets/img/new-icons/header-menu-icon/designer.png') }}" alt="{{ $service->name }}" class="service-image">
										</div>
									</div>
								</a>
								@endforeach
							</div>
							
							<button class="scroll-arrow-right" onclick="scrollPopularServices('right')">
								<i class="fas fa-chevron-right"></i>
							</button>
						</div>
					</div>
				</div>
			</div>
		</section>
		--}}
		
        
		@include('frontend.home.partials.platform-experience')
		@include('frontend.home.partials.benefits')


		<!-- /bg_gray -->

		@if (config('yuktione.programmes_enabled'))
			@include('frontend.home.partials.events')
		@endif
		@include('frontend.home.partials.support-cta')
		@include('frontend.home.partials.how-it-works')

		@include('frontend.home.partials.testimonials')
		@include('frontend.home.partials.blogs')
		@include('frontend.home.partials.faq')
		@include('frontend.home.partials.professional-cta')

	</main>

    @endsection
	@section('script')
	<script>
// Simple service selection function for index page
function selectServiceAndRedirect(serviceId, serviceName) {
    // Save service ID and name in session for later use
    fetch('/set-service-session', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            service_id: serviceId,
            service_name: serviceName
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Redirect to professionals listing page
            window.location.href = '/professionals';
        } else {
            console.error('Failed to save service session');
            // Even if session save fails, still redirect
            window.location.href = '/professionals';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Even if session save fails, still redirect
        window.location.href = '/professionals';
    });
}
</script>
		
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	
	<script>
		// Popular Services scroll functionality
		function scrollPopularServices(direction) {
			const scrollContainer = document.querySelector('#popularServicesScroll');
			const scrollAmount = 300;
			
			if (direction === 'right') {
				scrollContainer.scrollBy({
					left: scrollAmount,
					behavior: 'smooth'
				});
			} else if (direction === 'left') {
				scrollContainer.scrollBy({
					left: -scrollAmount,
					behavior: 'smooth'
				});
			}
		}

		// Services scroll functionality
		function scrollServices(direction) {
			const scrollContainer = document.querySelector('#servicesScroll');
			const scrollAmount = 300;
			
			if (direction === 'right') {
				scrollContainer.scrollBy({
					left: scrollAmount,
					behavior: 'smooth'
				});
			} else if (direction === 'left') {
				scrollContainer.scrollBy({
					left: -scrollAmount,
					behavior: 'smooth'
				});
			}
		}

		// Auto scroll functionality for services with infinite loop
		function autoScrollServices() {
			const scrollContainer = document.querySelector('#servicesScroll');
			if (!scrollContainer) return;
			
			const scrollAmount = 220; // Width of one service card + gap
			const halfWidth = scrollContainer.scrollWidth / 2; // Half of total width (since we duplicated)
			
			// If we've scrolled past the first set, reset to beginning seamlessly
			if (scrollContainer.scrollLeft >= halfWidth - 10) {
				scrollContainer.scrollTo({
					left: 0,
					behavior: 'auto' // Instant reset for seamless loop
				});
			} else {
				scrollContainer.scrollBy({
					left: scrollAmount,
					behavior: 'smooth'
				});
			}
		}

		// Start auto scroll when page loads
		document.addEventListener('DOMContentLoaded', function() {
			// Auto scroll every 3 seconds
			setInterval(autoScrollServices, 3000);
		});
	</script>
 @endsection


<style>
.carousel-arrow {
    transition: background 0.2s;
}
.carousel-arrow:hover {
    background: #f0f0f0;
}
@media (max-width: 991px) {
    .carousel-arrow.left-arrow { left: 0; }
    .carousel-arrow.right-arrow { right: 0; }
}

</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var $carousel = $('.services-carousal');
    $('.left-arrow').on('click', function() {
        $carousel.trigger('prev.owl.carousel');
    });
    $('.right-arrow').on('click', function() {
        $carousel.trigger('next.owl.carousel');
    });
});
</script>
