<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	@php
		$brandName = config('yuktione.name', 'YuktiOne');
		$isAuthenticationRoute = request()->routeIs('login', 'professional.login');
		$isCustomerPanelFlow = request()->routeIs(
			'user.booking',
			'user.booking.summary',
			'user.booking.success',
			'user.booking.retry',
			'user.customer.booking.retry',
			'user.event.booking.success',
			'event.booking.success',
			'user.my-bookings'
		);
		$defaultPublicTitle = $brandName . ' | Find Verified Experts for Career, Health & Finance';
		$defaultPublicDescription = 'Discover trusted professionals in career counselling, business, health, finance, and more. Book verified experts easily with ' . $brandName . '.';
	@endphp
	<meta name="author" content="{{ $brandName }}">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	@include('partials.dm-sans')
	
	<!-- SEO Meta Tags -->
	<meta name="robots" content="index, follow">
	<meta name="googlebot" content="index, follow">
	<link rel="canonical" href="{{ url()->current() }}">
	
	@hasSection('meta')
		@yield('meta')
	@else
		<!-- Default Meta Tags -->
		<meta name="description" content="@yield('meta_description', isset($metaDescription) ? $metaDescription : $defaultPublicDescription)">
		<meta name="keywords" content="career counselling, health experts, finance professionals, business consultants, verified experts, professional consultation, appointment booking, {{ $brandName }}">
		
		<!-- Open Graph Meta Tags for Social Media -->
		<meta property="og:title" content="{{ isset($metaTitle) ? $metaTitle : $defaultPublicTitle }}">
		<meta property="og:description" content="{{ isset($metaDescription) ? $metaDescription : $defaultPublicDescription }}">
		<meta property="og:type" content="website">
		<meta property="og:url" content="{{ url()->current() }}">
		<meta property="og:site_name" content="{{ $brandName }}">
		<meta property="og:image" content="{{ asset('frontend/assets/Yuktione_logo.png') }}">
		
		<!-- Twitter Card Meta Tags -->
		<meta name="twitter:card" content="summary_large_image">
		<meta name="twitter:title" content="{{ isset($metaTitle) ? $metaTitle : $defaultPublicTitle }}">
		<meta name="twitter:description" content="{{ isset($metaDescription) ? $metaDescription : $defaultPublicDescription }}">
		<meta name="twitter:image" content="{{ asset('frontend/assets/Yuktione_logo.png') }}">
		
		<title>@yield('title', isset($metaTitle) ? $metaTitle : $defaultPublicTitle)</title>
	@endif
	@stack('head')

	@if ($isAuthenticationRoute)
		@vite([
			'resources/css/tazen.css',
			'resources/css/auth-theme.css',
			'resources/css/yuktione-feedback.css',
		])
	@else
	<!-- Font Awesome -->
	<link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.6.0/css/all.css">

	<!-- slick / owl kept for pages that still initialise those carousels -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css" />
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css" />
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" />
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css" />
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />

	<!-- Project CSS -->
	<link href="{{ asset('frontend/assets/css/bootstrap.min.css') }}" rel="stylesheet">
	<link href="{{ asset('frontend/assets/css/style.css') }}" rel="stylesheet">
	<link href="{{ asset('frontend/assets/css/form.css') }}" rel="stylesheet">
	<link href="{{ asset('frontend/assets/css/home.css') }}" rel="stylesheet">
	<link href="{{ asset('frontend/assets/css/custom.css') }}" rel="stylesheet">
	<link href="{{ asset('frontend/assets/css/new-style.css') }}" rel="stylesheet">
	<link href="{{ asset('frontend/assets/css/layout.css') }}" rel="stylesheet">
	<link rel="stylesheet" href="{{ asset('frontend/assets/css/responsive2.css') }}" media="screen and (max-width: 992px)">

	@yield('styles')
	@stack('styles')

	<!-- Bootstrap Bundle -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
	<style>
		.item  {
		  display: block;
		
		  height: 400px;
		  
		}
		/* Success toast */
.toast-success {
    background-color: #51A351 !important;
    color: #fff !important;
}

/* Error toast */
.toast-error {
    background-color: #BD362F !important;
    color: #fff !important;
}

/* Info toast */
.toast-info {
    background-color: #2F96B4 !important;
    color: #fff !important;
}

/* Warning toast */
.toast-warning {
    background-color: #F89406 !important;
    color: #fff !important;
}

</style>
	
	<style>
		.question {
			display: none;
		}
		.question.active {
			display: block;
		}
		.form-check {
			margin-bottom: 10px;
		}
		.card-body {
			height: 200px;
			background-size: cover;
			background-position: center;
		}
		.one { background-color: #f8d7da; }
		.two { background-color: #d1e7dd; }
		.three { background-color: #cfe2ff; }
		.four { background-color: #fff3cd; }
		.five { background-color: #e2e3e5; }
		.six { background-color: #d9f2f9; }
		.seven { background-color: #f8d7da; }
		.eight { background-color: #d1e7dd; }
		.btn_1 {
			background: #4e63d7;
			color: #fff;
			padding: 8px 20px;
			border-radius: 30px;
			border: none;
			text-decoration: none;
			display: inline-block;
		}
		.btn_1:hover {
			background: #3a4eb3;
			color: #fff;
		}
		.content-discover {
			display: flex;
			justify-content: space-between;
			align-items: center;
		}
	</style>

	<!-- Tazen Favicon -->
	<link rel="icon" type="image/x-icon" href="{{ asset('tazen-favicon.png') }}">
	<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('frontend/assets/img/tazen_logo-01-removebg-preview.png') }}">
	<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('frontend/assets/img/tazen_logo-01-removebg-preview.png') }}">
	<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('frontend/assets/img/tazen_logo-01-removebg-preview.png') }}">
	<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('frontend/assets/img/tazen_logo-01-removebg-preview.png') }}">
	<link rel="shortcut icon" href="{{ asset('frontend/assets/img/tazen_logo-01-removebg-preview.png') }}">
	<meta name="msapplication-TileImage" content="{{ asset('frontend/assets/img/tazen_logo-01-removebg-preview.png') }}">
	<meta name="theme-color" content="#ffffff">
	
	<!-- Schema Markup -->
	@yield('schema')
	@vite('resources/css/yuktione-feedback.css')
	@if ($isCustomerPanelFlow)
		@vite('resources/css/customer-panel.css')
		<link href="{{ asset('frontend/assets/css/yuktione-booking.css') }}?v={{ filemtime(public_path('frontend/assets/css/yuktione-booking.css')) }}" rel="stylesheet">
	@endif
	<link href="{{ asset('frontend/assets/css/tazen-public-chrome.css') }}?v={{ filemtime(public_path('frontend/assets/css/tazen-public-chrome.css')) }}" rel="stylesheet">
	@if (request()->routeIs('home'))
		<link href="{{ asset('frontend/assets/css/yuktione-home.css') }}?v={{ filemtime(public_path('frontend/assets/css/yuktione-home.css')) }}" rel="stylesheet">
		<link href="{{ asset('frontend/assets/css/yuktione-hero.css') }}?v={{ filemtime(public_path('frontend/assets/css/yuktione-hero.css')) }}" rel="stylesheet">
		<link href="{{ asset('frontend/assets/css/yuktione-categories.css') }}?v={{ filemtime(public_path('frontend/assets/css/yuktione-categories.css')) }}" rel="stylesheet">
		<link href="{{ asset('frontend/assets/css/yuktione-tools.css') }}?v={{ filemtime(public_path('frontend/assets/css/yuktione-tools.css')) }}" rel="stylesheet">
		<link href="{{ asset('frontend/assets/css/yuktione-insights.css') }}?v={{ filemtime(public_path('frontend/assets/css/yuktione-insights.css')) }}" rel="stylesheet">
		<link href="{{ asset('frontend/assets/css/yuktione-talk.css') }}?v={{ filemtime(public_path('frontend/assets/css/yuktione-talk.css')) }}" rel="stylesheet">
		@if (config('yuktione.programmes_enabled'))
			<link href="{{ asset('frontend/assets/css/yuktione-programmes.css') }}?v={{ filemtime(public_path('frontend/assets/css/yuktione-programmes.css')) }}" rel="stylesheet">
		@endif
		<link href="{{ asset('admin/assets/css/icons.css') }}" rel="stylesheet">
	@endif
	@if (config('yuktione.programmes_enabled') && request()->routeIs('event.list', 'event.details'))
		<link href="{{ asset('frontend/assets/css/yuktione-programmes.css') }}?v={{ filemtime(public_path('frontend/assets/css/yuktione-programmes.css')) }}" rel="stylesheet">
	@endif
	@if (request()->routeIs('gridlisting', 'professionals'))
		<link href="{{ asset('frontend/assets/css/yuktione-explore.css') }}?v={{ filemtime(public_path('frontend/assets/css/yuktione-explore.css')) }}" rel="stylesheet">
	@endif
	@if (request()->routeIs('tools.index', 'professionals'))
		<link href="{{ asset('frontend/assets/css/yuktione-tools-page.css') }}?v={{ filemtime(public_path('frontend/assets/css/yuktione-tools-page.css')) }}" rel="stylesheet">
	@endif
	@if (request()->routeIs('tools.psychology'))
		<link href="{{ asset('frontend/assets/css/yuktione-psychology.css') }}?v={{ filemtime(public_path('frontend/assets/css/yuktione-psychology.css')) }}" rel="stylesheet">
	@endif
	@if (request()->routeIs('tools.psychology.mood', 'tools.psychology.mood.emotions', 'tools.psychology.mood.reflection', 'tools.psychology.mood.finish', 'tools.psychology.mood.complete', 'tools.psychology.mood.history'))
		<link href="{{ asset('frontend/assets/css/yuktione-mood.css') }}?v={{ filemtime(public_path('frontend/assets/css/yuktione-mood.css')) }}" rel="stylesheet">
	@endif
	@if (request()->routeIs('tools.career-interest*'))
		<link href="{{ asset('frontend/assets/css/yuktione-career-interest.css') }}?v={{ filemtime(public_path('frontend/assets/css/yuktione-career-interest.css')) }}" rel="stylesheet">
	@endif
	@if (request()->routeIs('tools.skills-strengths*'))
		<link href="{{ asset('frontend/assets/css/yuktione-skills-strengths.css') }}?v={{ filemtime(public_path('frontend/assets/css/yuktione-skills-strengths.css')) }}" rel="stylesheet">
	@endif
	@if (request()->routeIs('tools.bmi-bmr*'))
		<link href="{{ asset('frontend/assets/css/yuktione-bmi-bmr.css') }}?v={{ filemtime(public_path('frontend/assets/css/yuktione-bmi-bmr.css')) }}" rel="stylesheet">
	@endif
	@if (request()->routeIs('tools.water-intake*'))
		<link href="{{ asset('frontend/assets/css/yuktione-water-intake.css') }}?v={{ filemtime(public_path('frontend/assets/css/yuktione-water-intake.css')) }}" rel="stylesheet">
	@endif
	@if (request()->routeIs('tools.weight-journey*'))
		<link href="{{ asset('frontend/assets/css/yuktione-weight-journey.css') }}?v={{ filemtime(public_path('frontend/assets/css/yuktione-weight-journey.css')) }}" rel="stylesheet">
	@endif
	@if (request()->routeIs('tools.nutrition*'))
		<link href="{{ asset('frontend/assets/css/yuktione-nutrition.css') }}?v={{ filemtime(public_path('frontend/assets/css/yuktione-nutrition.css')) }}" rel="stylesheet">
	@endif
	<link href="{{ asset('frontend/assets/css/yuktione-navbar.css') }}?v={{ filemtime(public_path('frontend/assets/css/yuktione-navbar.css')) }}" rel="stylesheet">

	<link href="{{ asset('frontend/assets/css/yuktione-document.css') }}?v={{ filemtime(public_path('frontend/assets/css/yuktione-document.css')) }}" rel="stylesheet">
	@endif
</head>

<body class="@yield('body_class')">

	@if (!$isAuthenticationRoute)
		@include('frontend.sections.header')
	@endif

	@if ($isCustomerPanelFlow)
		<div data-yuktione-panel="customer">
			@yield('content')
		</div>
	@else
		@yield('content')
	@endif
	
	@if (!$isAuthenticationRoute && (!isset($showFooter) || $showFooter))
		@include('frontend.sections.footer')
	@endif

	<x-yuktione.feedback />

	<!-- WhatsApp Floating Button -->
	@if (!$isAuthenticationRoute)
		<a href="https://wa.me/+919147421560?text=Hello%20{{ rawurlencode(config('yuktione.name', 'YuktiOne')) }}!%20I%20need%20assistance%20with%20your%20services." target="_blank" rel="noopener noreferrer" class="whatsapp-float" aria-label="Chat with us on WhatsApp">
			<svg viewBox="0 0 32 32" aria-hidden="true" focusable="false">
				<path d="M16 3.4a12.1 12.1 0 0 0-10.4 18.3L4 28l6.5-1.7A12.3 12.3 0 1 0 16 3.4Z"></path>
				<path d="M12 10.2c.3-.6.6-.6.9-.6h.7c.2 0 .5 0 .7.5l1 2.4c.1.3.1.5-.1.7l-.8 1c-.2.2-.2.4-.1.6.4.8 1.2 1.6 2.1 2.2.8.5 1.5.8 1.8.9.3.1.5 0 .7-.2l1-1.2c.2-.3.5-.3.8-.2l2.3 1.1c.3.2.5.3.5.5 0 .2-.2 1.4-.8 2-.7.7-1.8 1-2.5 1-1.1 0-2.5-.4-4.3-1.4-2.2-1.2-4-3.4-4.6-4.2-.6-.8-1.5-2.1-1.5-3.5s.7-2.1 1-2.5Z"></path>
			</svg>
			<span class="tooltip-text">Chat with us on WhatsApp</span>
		</a>
	@endif

	<!-- view-specific scripts will be injected later (after libraries) -->

	@if (!$isAuthenticationRoute)
	<!-- External JS -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>

	<!-- Project JS -->
	<script src="{{ asset('frontend/assets/js/common_scripts.min.js') }}"></script>
	<script src="{{ asset('frontend/assets/js/isotope.pkgd.min.js') }}"></script>
	<script src="{{ asset('frontend/assets/js/common_func.js') }}"></script>
	<script src="{{ asset('frontend/assets/validate.js') }}"></script>
	<script src="{{ asset('frontend/assets/js/sticky_sidebar.min.js') }}"></script>
	<script src="{{ asset('frontend/assets/js/datepicker_func_1.js') }}"></script>
	<script src="{{ asset('frontend/assets/js/datepicker.min.js') }}"></script>
	<script src="{{ asset('frontend/assets/js/specific_detail.min.js') }}"></script>
	<script src="{{ asset('frontend/assets/js/jquery.cookiebar.js') }}"></script>
	<script>
		$(document).ready(function () {
			if (typeof $.fn.slick === 'function') {
				$('.slick_slider').slick();
			}
		});

		$(window).on("load",function(){
		  var $container = $('.isotope-wrapper');
		  if ($container.length && typeof $.fn.isotope === 'function') {
			$container.isotope({ itemSelector: '.isotope-item', layoutMode: 'masonry' });
		  }
		});
		$('.switch-field').on( 'click', 'input', 'change', function(){
		  var selector = $(this).attr('data-filter');
		  if (typeof $.fn.isotope === 'function') {
			$('.isotope-wrapper').isotope({ filter: selector });
		  }
		});
	</script>


	<script>
		$(document).ready(function () {
			$('.services-carousal').owlCarousel({
				loop: true,
				margin: 10,
				responsiveClass: true,

				nav: false,
				dots: false,
				autoplay: true,               // Enable autoplay
				autoplayTimeout: 2000,        // Time between slides (1 second)
				autoplayHoverPause: true,     // Pause on mouse hover
				ltr: true,
				responsive: {
					0: {
						items: 1,

					},
					600: {
						items: 2,

					},
					1000: {
						items: 3,

					}
				}
			})
		});
	</script>


	<script type="text/javascript">
		function openmodal(val) {

			$("#openPopups").modal('show');
			$('#modal_title').val(val);

		}
	</script>
	 <script type="text/javascript">
        $(function(){   
    initSlider();
});


function initSlider() {
        
    var slider = $(".slider");
        
    slider.on("init", function(slick) {
        checkSlides(this, 0);
    });
    
    slider.on("beforeChange", function(event, slick, currentSlide, nextSlide) {
        $(this).addClass("changing");
    });
    
    slider.on("afterChange", function(event, slick, currentSlide) {
        $(this).removeClass("changing");
        checkSlides(this, currentSlide);
    });
    
    
	if (typeof jQuery !== 'undefined' && typeof jQuery.fn !== 'undefined' && typeof jQuery.fn.slick === 'function') {
		slider.slick({
        prevArrow: "<button type='button' class='slick-prev' aria-label='Previous picture'></button>",
        nextArrow: "<button type='button' class='slick-next' aria-label='Next Picture'></button>",
        centerMode: true,
        variableWidth: true,
        dots: true,
    autoplay: true,
    slidesToShow: 1,
		});
	} else {
		console.warn('Slick is not available. initSlider will skip slider initialization.');
	}
    
}

function checkSlides(slider, currentSlide) {
    var slides = $(".slide", $(slider));
    slides.removeClass("prev");
    slides.filter(function(index) {
        return $(this).attr("data-slick-index") < currentSlide;
    }).addClass("prev");;
}

    </script>

    <script type="text/javascript">
		if (typeof jQuery !== 'undefined' && typeof jQuery.fn !== 'undefined' && typeof jQuery.fn.slick === 'function') {
			jQuery(".property-slide").slick({
          dots: false,
          infinite: true,
          speed: 300,
          slidesToShow: 2,
          slidesToScroll: 2,
          arrows: true,
          autoplay: true,
          autoplaySpeed: 6000,
          prevArrow:
            '<button class="slick-prev fa-solid fa-arrow-left" aria-label="Prev"></button>',
          nextArrow:
            '<button class="slick-next fa-solid fa-arrow-right" aria-label="Next"></button>',
          responsive: [
            {
              breakpoint: 992,
              settings: {
                slidesToShow: 2,
                slidesToScroll: 2
              }
            },
            {
              breakpoint: 600,
              settings: {
                slidesToShow: 1,
                slidesToScroll: 1
              }
            },
            {
              breakpoint: 480,
              settings: {
                slidesToShow: 1,
                slidesToScroll: 1
              }
            }
            // You can unslick at a given breakpoint now by adding:
            // settings: "unslick"
            // instead of a settings object
          ]
			});
		} else {
			console.warn('Slick is not available. Skipping property-slide initialization.');
		}

    </script>
	<script>
		$(document).ready(function(){
				if (typeof jQuery !== 'undefined' && typeof jQuery.fn !== 'undefined' && typeof jQuery.fn.slick === 'function') {
					$('.slick_slider').slick();
				} else {
					console.warn('Slick is not available. Skipping slick_slider initialization.');
				}
			});
	</script>
	

	<script>
		$(document).ready(function () {
			$('.services-carousal').owlCarousel({
				loop: true,
				margin: 10,
				responsiveClass: true,

				nav: false,
				dots: false,
				autoplay: true,               // Enable autoplay
				autoplayTimeout: 2000,        // Time between slides (1 second)
				autoplayHoverPause: true,     // Pause on mouse hover
				ltr: true,
				responsive: {
					0: {
						items: 1,

					},
					600: {
						items: 2,

					},
					1000: {
						items: 3,

					}
				}
			})
		});
	</script>
	<script>
		$(document).ready(function() {
			'use strict';
			$.cookieBar({
				fixed: true,
				zindex: 999,
				autoEnable: false
			});
		});
	</script>
<script>
toastr.options = {
    "positionClass": "toast-top-center",
    "timeOut": "3000",
    "closeButton": true,
    "progressBar": true
};
</script>
<script>
	$(document).ready(function () {
		$('.owl-carousel').owlCarousel({
			loop: true,
			margin: 0,
			autoWidth:true,
		   nav: false,
			responsive: {
				0: {
					items: 1
				},
				600: {
					items: 3
				},
				1000: {
					items: 3
				}
			}
		});
	});
</script>
<script>
$(document).ready(function () {
	$('.gallery-carousal').owlCarousel({
		loop: true,
		margin: 10,
	  
		autoplay: true,           // Enables autoplay
		autoplayHoverPause: true, // Pauses autoplay on hover
		nav: false, 
		responsive: {
			0: {
				items: 1
			},
			600: {
				items: 2
			},
			1000: {
				items: 2
			}
		}
	});
});
</script>


<script>
	const readMoreButton = document.getElementById("read-more");
	if (readMoreButton) {
		readMoreButton.addEventListener("click", function() {
			const fullText = document.getElementById("full-text");
			const shortText = document.getElementById("short-text");

			if (fullText && shortText) {
				if (fullText.style.display === "none") {
					fullText.style.display = "inline";
					shortText.style.display = "none";
					this.textContent = "Read Less";
				} else {
					fullText.style.display = "none";
					shortText.style.display = "inline";
					this.textContent = "Read More";
				}
			}
		});
	}

</script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>

	<!-- yield view scripts after libraries -->
	@yield('script')

	<!-- Global image fallback: replace broken images with a default placeholder -->
	<script>
		(function(){
			const fallbackSrc = "{{ asset('frontend/assets/img/detail_placeholder.png') }}";
			function onImgError(e) {
				const el = e.target;
				if (!el || el.tagName !== 'IMG') return;
				// Prevent infinite loop
				if (el.dataset.fallbackApplied === '1') return;
				el.dataset.fallbackApplied = '1';
				el.src = fallbackSrc;
			}
			// Capture errors in capture phase to catch as many as possible
			document.addEventListener('error', onImgError, true);
		})();
	</script>
<script>
	let currentQuestion = 1;
	const totalQuestions = 5;
	
	function showQuestion(num) {
		$('.question').hide();
		$('#question' + num).show();
	
		$('#prevBtn').toggle(num > 1);
		$('#nextBtn').toggle(num < totalQuestions);
		$('#submitBtn').toggle(num === totalQuestions);
	}
	
	$('.book-now-btn').on('click', function () {
		const serviceId = $(this).data('service-id');
	
		// Reset navigation
		currentQuestion = 1;
	
		$.ajax({
			url: `/get-mcqs/${serviceId}`,
			method: 'GET',
			success: function (mcq) {
				if (mcq) {
					// Fill the questions
					$('#question1 h6').text('Question 1: ' + mcq.question1);
					$('#q1a').next().text(mcq.option1_a);
					$('#q1b').next().text(mcq.option1_b);
					$('#q1c').next().text(mcq.option1_c);
					$('#q1d').next().text(mcq.option1_d);
	
					$('#question2 h6').text('Question 2: ' + mcq.question2);
					$('#q2a').next().text(mcq.option2_a);
					$('#q2b').next().text(mcq.option2_b);
					$('#q2c').next().text(mcq.option2_c);
					$('#q2d').next().text(mcq.option2_d);
	
					$('#question3 h6').text('Question 3: ' + mcq.question3);
					$('#q3a').next().text(mcq.option3_a);
					$('#q3b').next().text(mcq.option3_b);
					$('#q3c').next().text(mcq.option3_c);
					$('#q3d').next().text(mcq.option3_d);
	
					$('#question4 h6').text('Question 4: ' + mcq.question4);
					$('#q4a').next().text(mcq.option4_a);
					$('#q4b').next().text(mcq.option4_b);
					$('#q4c').next().text(mcq.option4_c);
					$('#q4d').next().text(mcq.option4_d);
	
					$('#question5 h6').text('Question 5: ' + mcq.question5);
					$('#mcqForm textarea[name="q5"]').val('');
	
					showQuestion(currentQuestion);
				}
			}
		});
	});
	
	$('#nextBtn').on('click', function () {
		if (currentQuestion < totalQuestions) {
			currentQuestion++;
			showQuestion(currentQuestion);
		}
	});
	
	$('#prevBtn').on('click', function () {
		if (currentQuestion > 1) {
			currentQuestion--;
			showQuestion(currentQuestion);
		}
	});
	if (typeof serviceId !== 'undefined' && serviceId) {
		$.get('/get-mcq-questions/' + serviceId, function(data) {
		    // fill the modal content with MCQ questions
		    $('#mcqModal .modal-body').html(data);
		    $('#mcqModal').modal('show');
		});
	}
	</script>
	
	
	@if (request()->routeIs('home'))
		<script src="{{ asset('frontend/assets/js/yuktione-home.js') }}?v={{ filemtime(public_path('frontend/assets/js/yuktione-home.js')) }}"></script>
		<script src="{{ asset('frontend/assets/js/home-category-slider.js') }}?v={{ filemtime(public_path('frontend/assets/js/home-category-slider.js')) }}"></script>
		@if (config('yuktione.programmes_enabled'))
			<script src="{{ asset('frontend/assets/js/yuktione-programmes.js') }}?v={{ filemtime(public_path('frontend/assets/js/yuktione-programmes.js')) }}"></script>
		@endif
	@endif
	@if (config('yuktione.programmes_enabled') && request()->routeIs('event.list'))
		<script src="{{ asset('frontend/assets/js/yuktione-programmes.js') }}?v={{ filemtime(public_path('frontend/assets/js/yuktione-programmes.js')) }}"></script>
	@endif
	@if (config('yuktione.programmes_enabled') && request()->routeIs('event.details'))
		<script src="{{ asset('frontend/assets/js/yuktione-programmes.js') }}?v={{ filemtime(public_path('frontend/assets/js/yuktione-programmes.js')) }}"></script>
	@endif
	@endif
	@stack('scripts')
	@vite('resources/js/yuktione-feedback.js')
	@if ($isAuthenticationRoute)
		@php
			$authFlashes = array_values(array_filter([
				session('status') ? ['success', session('status'), 'Password updated'] : null,
				session('success') ? ['success', session('success'), 'Signed in'] : null,
				session('error') ? ['error', session('error'), 'Something went wrong'] : null,
				session('warning') ? ['warning', session('warning'), 'Please note'] : null,
				session('message') ? ['info', session('message'), 'Please note'] : null,
			]));
		@endphp
		<script type="module">
			const flashes = @json($authFlashes);
			const notify = () => {
				if (!window.YuktioneToast) {
					return false;
				}
				flashes.forEach(([type, message, title]) => {
					if (window.YuktioneToast[type]) {
						window.YuktioneToast[type](message, { title });
					}
				});
				return true;
			};
			if (!notify()) {
				window.addEventListener('load', notify, { once: true });
			}
		</script>
	@endif
</body>
</html>
