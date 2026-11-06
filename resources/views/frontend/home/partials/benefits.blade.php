@if(isset($whychooses) && $whychooses->isNotEmpty())
    @php
        $brand = config('yuktione.name', 'YuktiOne');
        $brandize = static fn (?string $text): string => str_ireplace(['Tazen', 'Yuktione'], $brand, (string) $text);
        $whychoose = $whychooses->first();
        $whyChooseItems = [
            ['heading' => $brandize($whychoose->card1_heading), 'description' => $brandize($whychoose->card1_description)],
            ['heading' => $brandize($whychoose->card2_heading), 'description' => $brandize($whychoose->card2_description)],
            ['heading' => $brandize($whychoose->card3_heading), 'description' => $brandize($whychoose->card3_description)],
            ['heading' => $brandize($whychoose->card4_heading), 'description' => $brandize($whychoose->card4_description)],
        ];
    @endphp

    <section class="yk-home-why" aria-labelledby="yk-home-why-title">
        <div class="yk-home-why__layout yk-home-container">
            <div class="yk-home-why__visual" data-home-reveal>
                <div class="yk-home-why__image-frame">
                    <img
                        src="{{ asset('frontend/assets/img/professionals_photos/about-work-section.jpeg') }}"
                        alt="Professionals sharing ideas around a table"
                        width="2000"
                        height="1333"
                        loading="lazy"
                        decoding="async"
                    >
                </div>
                <div class="yk-home-why__image-accent" aria-hidden="true"></div>
                <div class="yk-home-why__image-note">
                    <span class="yk-home-why__note-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false">
                            <path d="M12 3 4 6v5c0 5 3.4 8.5 8 10 4.6-1.5 8-5 8-10V6l-8-3Z"/>
                            <path d="m8.5 12 2.2 2.2 4.8-5"/>
                        </svg>
                    </span>
                    <span>Support for your next step</span>
                </div>
            </div>

            <div class="yk-home-why__content">
                <header class="yk-home-why__heading" data-home-reveal>
                    <p class="yk-home-why__eyebrow">Why Choose {{ $brand }}</p>
                    <h2 id="yk-home-why-title">{{ $brandize($whychoose->section_heading) }}</h2>
                    <p>{{ $brandize($whychoose->section_sub_heading) }}</p>
                </header>

                <div class="yk-home-why__list">
                    @foreach($whyChooseItems as $item)
                        <article class="yk-home-why-item" data-home-reveal>
                            <span class="yk-home-why-item__number" aria-hidden="true">{{ sprintf('%02d', $loop->iteration) }}</span>
                            <span class="yk-home-why-item__icon" aria-hidden="true">
                                @if($loop->first)
                                    <svg viewBox="0 0 24 24" focusable="false"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m15.5 15.5 5 5"/></svg>
                                @elseif($loop->iteration === 2)
                                    <svg viewBox="0 0 24 24" focusable="false"><path d="M4 5h16v14H4zM4 9h16m-12 4h8m-8 3h5"/></svg>
                                @elseif($loop->iteration === 3)
                                    <svg viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg>
                                @else
                                    <svg viewBox="0 0 24 24" focusable="false"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4m8-4v4M4 10h16m-11 4h6"/></svg>
                                @endif
                            </span>
                            <div class="yk-home-why-item__copy">
                                <h3>{{ $item['heading'] }}</h3>
                                <p>{{ $item['description'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif
