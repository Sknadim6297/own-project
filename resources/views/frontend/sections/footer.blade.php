<footer class="tazen-footer" id="tazen-public-footer">
    <svg class="tazen-footer__wave-art" viewBox="0 0 2000 700" preserveAspectRatio="none" aria-hidden="true" focusable="false">
        <path d="M0 430c170 120 245 118 405 60 157-57 270-74 440-8 154 60 274 181 475 149 175-28 250-159 415-154 103 3 174 51 265 111v112H0Z" fill="#b5f1ed" fill-opacity=".5"></path>
        <path d="M0 490c160 101 252 113 430 57 181-57 267-73 425-2 171 77 293 162 465 118 186-47 263-150 426-121 95 17 177 62 254 108v89H0Z" fill="#9bece6" fill-opacity=".25"></path>
    </svg>
    <div class="tazen-footer__main">
        <div class="tazen-footer__brand">
            <a class="tazen-footer__logo" href="{{ url('/') }}" aria-label="Yuktione home">
                <img src="{{ asset('frontend/assets/Yuktione_logo.png') }}" alt="Yuktione" width="300" height="100" loading="lazy">
            </a>
            <p>Discover trusted professionals and services for your next step.</p>
            <h2 class="tazen-footer__social-title">Connect With Us</h2>
            <nav class="tazen-footer__social" aria-label="Connect with us">
                <a class="is-facebook" href="https://www.facebook.com/share/18vaMJRLFG/" target="_blank" rel="noopener noreferrer" aria-label="YuktiOne on Facebook" title="Facebook">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M14.5 8.5V6.8c0-.7.5-1 1.2-1H17V3h-2.1C12.2 3 11 4.5 11 6.6v1.9H9v2.7h2V21h3.1v-9.8h2.4l.4-2.7h-2.8Z"></path></svg>
                </a>
                <a class="is-instagram" href="https://www.instagram.com/tazen.in?igsh=eml2bnhsMTdvaTlj" target="_blank" rel="noopener noreferrer" aria-label="YuktiOne on Instagram" title="Instagram">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="3.5" y="3.5" width="17" height="17" rx="5"></rect><circle cx="12" cy="12" r="4"></circle><circle cx="17.4" cy="6.6" r="1"></circle></svg>
                </a>
                <a class="is-linkedin" href="https://www.linkedin.com/company/tazen/" target="_blank" rel="noopener noreferrer" aria-label="YuktiOne on LinkedIn" title="LinkedIn">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5.2 8.5a1.8 1.8 0 1 0 0-3.6 1.8 1.8 0 0 0 0 3.6ZM3.7 10h3v10h-3zM9 10h2.9v1.4h.1a3.2 3.2 0 0 1 2.9-1.6c3.1 0 3.7 2 3.7 4.6V20h-3v-5c0-1.2 0-2.7-1.7-2.7s-2 1.3-2 2.6V20H9z"></path></svg>
                </a>
                <a class="is-youtube" href="https://www.youtube.com/@Tazenofficial1" target="_blank" rel="noopener noreferrer" aria-label="YuktiOne on YouTube" title="YouTube">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M23 12.2s0-3.2-.4-4.6c-.2-.9-.9-1.6-1.8-1.8C19.2 5.4 12 5.4 12 5.4s-7.2 0-8.8.4c-.9.2-1.6.9-1.8 1.8C1 9 1 12.2 1 12.2s0 3.2.4 4.6c.2.9.9 1.6 1.8 1.8 1.6.4 8.8.4 8.8.4s7.2 0 8.8-.4c.9-.2 1.6-.9 1.8-1.8.4-1.4.4-4.6.4-4.6ZM9.8 15.5v-6.6l6.2 3.3-6.2 3.3Z"></path></svg>
                </a>
                <a class="is-x" href="https://x.com/Tazenofficial_" target="_blank" rel="noopener noreferrer" aria-label="YuktiOne on X" title="X">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M18.9 3H22l-6.8 7.8L23.2 21h-6.3L12 14.7 6.5 21H3.3l7.3-8.4L2.8 3h6.5l4.5 5.9L18.9 3Zm-1.1 16h1.7L8.2 4.9H6.4L17.8 19Z"></path></svg>
                </a>
                <a class="is-whatsapp" href="https://wa.me/+919147421560?text=Hello%20{{ rawurlencode(config('yuktione.name', 'YuktiOne')) }}!%20I%20need%20assistance%20with%20your%20services." target="_blank" rel="noopener noreferrer" aria-label="{{ config('yuktione.name', 'YuktiOne') }} on WhatsApp" title="WhatsApp">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 2.5a9.3 9.3 0 0 0-8 14l-1.2 4.4 4.5-1.2A9.5 9.5 0 1 0 12 2.5Zm0 17.1a7.6 7.6 0 0 1-3.9-1.1l-.3-.2-2.7.7.7-2.6-.2-.3A7.6 7.6 0 1 1 12 19.6Zm4.2-5.7c-.2-.1-1.3-.7-1.5-.8-.2-.1-.4-.1-.5.1-.2.2-.6.8-.8.9-.1.2-.3.2-.5.1-.2-.1-.9-.3-1.8-1.1-.6-.5-1.1-1.2-1.2-1.4-.1-.2 0-.4.1-.5l.4-.5.2-.4c.1-.2 0-.3 0-.5l-.7-1.6c-.2-.4-.4-.4-.5-.4h-.5c-.2 0-.5.1-.7.3-.2.2-.9.9-.9 2.1s.9 2.4 1 2.5c.1.2 1.8 2.7 4.3 3.8.6.3 1.1.4 1.4.5.6.2 1.2.2 1.6.1.5-.1 1.3-.5 1.5-1 .2-.5.2-.9.1-1-.1-.1-.3-.2-.5-.3Z"></path></svg>
                </a>
            </nav>
        </div>

        <nav class="tazen-footer__column" aria-label="Quick links">
            <h2>Quick Links</h2>
            <ul>
                <li><a href="{{ url('/') }}">Home</a></li>
                <li><a href="{{ url('/about') }}">About Us</a></li>
                <li><a href="{{ route('professional.login') }}">Join Us as professional</a></li>
                <li><a href="{{ route('login') }}">Customer Login</a></li>
                <li><a href="{{ route('career') }}">Careers</a></li>
            </ul>
        </nav>

        <nav class="tazen-footer__column" aria-label="Services and resources">
            <h2>Services &amp; Resources</h2>
            <ul>
                <li><a href="{{ route('gridlisting') }}">Explore Services</a></li>
                <li><a href="{{ url('/help') }}">Help</a></li>
                <li><a href="{{ route('blog.index') }}">Blog</a></li>
                @if (config('yuktione.programmes_enabled'))
                    <li><a href="{{ route('programmes.index') }}">Programmes</a></li>
                @endif
                <li><a href="{{ url('/contact') }}">Contact Us</a></li>
            </ul>
        </nav>

        <div class="tazen-footer__column tazen-footer__contact">
            <h2>Get in Touch</h2>
            <a href="mailto:{{ config('yuktione.email', 'info@yuktione.in') }}">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m4 7 8 6 8-6"></path></svg>
                <span>{{ config('yuktione.email', 'info@yuktione.in') }}</span>
            </a>
            <p>For support and enquiries, contact the {{ config('yuktione.name', 'YuktiOne') }} team.</p>
            <form class="tazen-footer__contact-form" data-contact-redirect>
                <label for="tazen-footer-email">Send us your email</label>
                <div>
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m4 7 8 6 8-6"></path></svg>
                    <input id="tazen-footer-email" type="email" name="email" placeholder="Your email address" required>
                    <button type="submit" aria-label="Continue to contact form">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 12h15M13 5l7 7-7 7"></path></svg>
                    </button>
                </div>
                <p>Get the latest updates, resources and news directly in your inbox.</p>
            </form>
        </div>
    </div>

    <div class="tazen-footer__bottom">
        <div class="tazen-footer__copyright">
            <span>&copy; {{ date('Y') }} Tazen. All rights reserved.</span>
            <span>Building a trusted community for a brighter tomorrow.</span>
        </div>
        <div class="tazen-footer__preferences">
            <label class="tazen-footer__select-wrap">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9"></circle><path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"></path></svg>
                <span class="visually-hidden">Language</span>
                <select aria-label="Language">
                    <option value="English" selected>English</option>
                </select>
                <svg class="tazen-footer__select-chevron" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m6 9 6 6 6-6"></path></svg>
            </label>
            @php
                $supportedCurrencies = app(\App\Services\CurrencyService::class)->getSupportedCurrencies();
                $currentCurrency = getCurrentCurrency();
            @endphp
            <form action="{{ route('currency.switch') }}" method="POST" class="tazen-footer__currency-form">
                @csrf
                <label class="tazen-footer__select-wrap">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 5h10M7 9h10M9 5c4 0 6 2 6 5s-2 5-6 5h-2l8 6"></path></svg>
                    <span class="visually-hidden">Currency</span>
                    <select name="currency" aria-label="Select currency" onchange="this.form.submit()">
                        @foreach ($supportedCurrencies as $code => $data)
                            <option value="{{ $code }}" @selected($currentCurrency === $code)>{{ $code }} ({{ $data['symbol'] }})</option>
                        @endforeach
                    </select>
                    <svg class="tazen-footer__select-chevron" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m6 9 6 6 6-6"></path></svg>
                </label>
            </form>
        </div>
        <nav aria-label="Legal links">
            <a href="{{ url('/terms') }}">Terms and conditions</a>
            <a href="{{ url('/privacy') }}">Privacy Policy</a>
            <a href="{{ asset('sitemap.xml') }}">Sitemap</a>
        </nav>
    </div>
</footer>

<div id="toTop" aria-label="Back to top"></div>
<div class="layer"></div>
