{{-- Accordion FAQ (port FaqHome). items = [['q' => ..., 'a' => ..., 'link' => ['label' => ..., 'href' => ...]?], ...]
     surface = warna latar section ("white" atau "mist"), supaya pertanyaan yang tertutup tetap terlihat. --}}
@props(['items', 'surface' => 'white'])
@php
    $uid = 'faq-' . substr(md5(json_encode($items)), 0, 6);
    $closed = $surface === 'mist'
        ? 'bg-white/60 hover:bg-white hover:-translate-y-0.5 has-focus-visible:bg-white has-focus-visible:-translate-y-0.5'
        : 'bg-brand-softmist hover:bg-brand-mist hover:-translate-y-0.5 has-focus-visible:bg-brand-mist has-focus-visible:-translate-y-0.5';
@endphp
<ul x-data="faq" @keydown="keys($event)" {{ $attributes->class('space-y-3') }}>
    @foreach ($items as $i => $faq)
        <li>
            {{-- Efek hover juga muncul saat pertanyaan difokus lewat keyboard (has-focus-visible) --}}
            <div
                class="group rounded-card transition duration-300 {{ $i === 0 ? 'bg-white shadow-softpill ring-1 ring-brand-ink/5' : $closed }}"
                :class="{ 'bg-white shadow-softpill ring-1 ring-brand-ink/5': open === {{ $i }}, '{{ $closed }}': open !== {{ $i }} }"
            >
                <h3>
                    <button
                        id="{{ $uid }}-{{ $i }}-button"
                        type="button"
                        @click="toggle({{ $i }})"
                        :aria-expanded="(open === {{ $i }}).toString()"
                        aria-controls="{{ $uid }}-{{ $i }}-panel"
                        class="flex w-full items-center gap-4 p-5 md:p-6 text-left rounded-card focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-darkred"
                    >
                        <span class="w-7 shrink-0 font-display text-lg font-bold tracking-wide text-brand-darkred" aria-hidden="true">{{ sprintf('%02d', $i + 1) }}</span>
                        <span class="flex-1 text-base md:text-lg font-semibold leading-snug">
                            {{-- Coretan bawah dibuat ulang setiap pertanyaan dibuka, jadi selalu tergambar dari awal --}}
                            <span class="relative inline-block">{{ $faq['q'] }}<template x-if="open === {{ $i }}"><span data-sketch="underline" data-scale="0.55" data-duration="450" data-stagger="130" aria-hidden="true" class="pointer-events-none absolute text-brand-darkred -left-1 -right-1 -bottom-2 h-2"></span></template></span>
                        </span>
                        <span
                            class="w-8 h-8 shrink-0 rounded-full flex items-center justify-center transition-colors duration-300"
                            :class="open === {{ $i }} ? 'bg-brand-darkred text-white' : 'bg-white text-brand-ink group-hover:text-brand-darkred group-has-focus-visible:text-brand-darkred'"
                        >
                            {{-- Tanda + berputar jadi × saat terbuka --}}
                            <svg
                                class="w-4 h-4 transition-transform duration-300"
                                :class="open === {{ $i }} ? 'rotate-45' : 'group-hover:rotate-90 group-has-focus-visible:rotate-90'"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"
                            ><path d="M12 5v14M5 12h14"/></svg>
                        </span>
                    </button>
                </h3>

                {{-- Animasi buka-tutup pakai grid-rows 0fr -> 1fr; inert supaya link di jawaban tertutup tidak bisa di-tab --}}
                <div
                    id="{{ $uid }}-{{ $i }}-panel"
                    role="region"
                    aria-labelledby="{{ $uid }}-{{ $i }}-button"
                    class="grid transition-[grid-template-rows] duration-300 ease-out motion-reduce:transition-none {{ $i === 0 ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]' }}"
                    :class="{ 'grid-rows-[1fr]': open === {{ $i }}, 'grid-rows-[0fr]': open !== {{ $i }} }"
                    :inert="open !== {{ $i }}"
                >
                    <div class="overflow-hidden">
                        <div class="pl-16 pr-5 pb-6 md:pl-17 md:pr-8">
                            <p class="text-sm md:text-base leading-relaxed text-brand-ink/70">{{ $faq['a'] }}</p>
                            @isset($faq['link'])
                                <a href="{{ $faq['link']['href'] }}" class="group mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-darkred">
                                    {{ $faq['link']['label'] }}
                                    <x-sketch.arrow class="w-6 h-3 transition-transform group-hover:translate-x-1" />
                                </a>
                            @endisset
                        </div>
                    </div>
                </div>
            </div>
        </li>
    @endforeach
</ul>
