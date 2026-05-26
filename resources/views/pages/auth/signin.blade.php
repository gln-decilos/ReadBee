@extends('layouts.fullscreen-layout')

@section('content')
    <style>
        [x-cloak] { display: none !important; }

        .readbee-login-page {
            --readbee-card: #ffffff;
            --readbee-card-border: rgba(229, 231, 235, .95);
            --readbee-text: #111827;
            --readbee-muted: #667085;
            min-height: 100vh;
            position: relative;
            overflow: hidden;
            background: #060606;
        }

        .readbee-login-page::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: var(--readbee-login-bg);
            background-size: cover;
            background-position: center;
            opacity: .22;
            filter: grayscale(.12) saturate(.75);
            transform: scale(1.04);
            z-index: 0;
        }

        .readbee-login-page::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(6, 6, 6, .92) 0%, rgba(6, 6, 6, .82) 48%, rgba(6, 6, 6, .92) 100%);
            z-index: 1;
        }

        .readbee-login-card {
            background: var(--readbee-card);
            border: 1px solid var(--readbee-card-border);
            box-shadow: 0 22px 55px rgba(0, 0, 0, .24);
        }

        .readbee-login-logo {
            width: min(176px, 68%);
            height: auto;
            max-height: 64px;
            object-fit: contain;
        }

        .readbee-login-waves {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 2;
            width: 100%;
            height: 74px;
            pointer-events: none;
        }

        .readbee-login-wave1 use {
            animation: readbee-login-wave-one 10s linear infinite;
            animation-delay: -2s;
            fill: rgba(255, 255, 255, .56);
        }

        .readbee-login-wave2 use {
            animation: readbee-login-wave-two 8s linear infinite;
            animation-delay: -2s;
            fill: rgba(255, 255, 255, .38);
        }

        .readbee-login-wave3 use {
            animation: readbee-login-wave-three 6s linear infinite;
            animation-delay: -2s;
            fill: rgba(255, 255, 255, .92);
        }

        .dark .readbee-login-card {
            background: #111827;
            border-color: rgba(255, 255, 255, .10);
            box-shadow: 0 24px 60px rgba(0, 0, 0, .50);
        }

        .dark .readbee-login-wave1 use { fill: rgba(17, 24, 39, .72); }
        .dark .readbee-login-wave2 use { fill: rgba(17, 24, 39, .54); }
        .dark .readbee-login-wave3 use { fill: rgba(17, 24, 39, .96); }

        @keyframes readbee-login-wave-one {
            0% { transform: translate(85px, 0%); }
            100% { transform: translate(-90px, 0%); }
        }

        @keyframes readbee-login-wave-two {
            0% { transform: translate(-90px, 0%); }
            100% { transform: translate(85px, 0%); }
        }

        @keyframes readbee-login-wave-three {
            0% { transform: translate(-90px, 0%); }
            100% { transform: translate(85px, 0%); }
        }

        @media (max-height: 760px) and (min-width: 640px) {
            .readbee-login-logo {
                max-height: 56px;
                width: min(158px, 62%);
            }
        }

        @media (max-width: 640px) {
            .readbee-login-waves { height: 54px; }
            .readbee-login-logo {
                max-height: 58px;
                width: min(160px, 64%);
            }
        }
    </style>

    <main
        class="readbee-login-page flex min-h-screen items-center justify-center px-4 py-6 sm:px-6 lg:px-8"
        style="--readbee-login-bg: url('{{ asset('landing-assets/images/hero-bg-2.jpg') }}');"
    >
        <div class="relative z-[3] w-full max-w-[372px]">
            <div class="readbee-login-card rounded-3xl p-5 sm:p-6">
                <a href="/"
                    class="mb-5 inline-flex items-center gap-2 text-sm font-medium text-gray-500 transition-colors hover:text-gray-800 dark:text-gray-400 dark:hover:text-white">
                    <svg class="h-5 w-5 stroke-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none">
                        <path d="M12.7083 5L7.5 10.2083L12.7083 15.4167" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Back to dashboard
                </a>

                <div class="mb-6 text-center">
                    <img src="{{ asset('landing-assets/images/ReadBee-Logo-Light.png') }}" alt="ReadBee logo" class="readbee-login-logo mx-auto mb-4 block dark:hidden">
                    <img src="{{ asset('landing-assets/images/ReadBee-Logo-Dark.png') }}" alt="ReadBee logo" class="readbee-login-logo mx-auto mb-4 hidden dark:block">
                    <h1 class="mb-2 text-2xl font-semibold leading-tight text-gray-900 dark:text-white/95">
                        Sign In
                    </h1>
                    <p class="text-sm leading-6 text-gray-500 dark:text-gray-400">
                        Enter your email and password to sign in!
                    </p>
                </div>

                <form method="POST" action="{{ route('signin.login') }}">
                    @csrf

                    <div class="space-y-4">
                        <div>
                            <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Email<span class="text-error-500">*</span>
                            </label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="info@gmail.com"
                                class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-10 w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                autocomplete="email"
                            />
                            @error('email')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Password<span class="text-error-500">*</span>
                            </label>
                            <div x-data="{ showPassword: false }" class="relative">
                                <input
                                    :type="showPassword ? 'text' : 'password'"
                                    id="password"
                                    name="password"
                                    placeholder="Enter your password"
                                    class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-10 w-full rounded-xl border border-gray-300 bg-white py-2.5 pr-11 pl-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                    autocomplete="current-password"
                                />

                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute top-1/2 right-3 z-30 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/10 dark:hover:text-white"
                                    :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                >
                                    <svg x-show="!showPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                                        <path d="M2.75 12S5.75 5.75 12 5.75S21.25 12 21.25 12S18.25 18.25 12 18.25S2.75 12 2.75 12Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M12 14.75A2.75 2.75 0 1 0 12 9.25A2.75 2.75 0 0 0 12 14.75Z" stroke="currentColor" stroke-width="1.7" />
                                    </svg>
                                    <svg x-show="showPassword" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                                        <path d="M3.75 3.75L20.25 20.25" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                                        <path d="M9.88 5.96A8.78 8.78 0 0 1 12 5.75C18.25 5.75 21.25 12 21.25 12A14.62 14.62 0 0 1 18.3 15.92" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M6.38 7.55C3.98 9.18 2.75 12 2.75 12S5.75 18.25 12 18.25C13.2 18.25 14.29 18.02 15.27 17.64" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>
                            </div>

                            @error('password')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <button
                            type="submit"
                            class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 flex w-full items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition">
                            Sign In
                        </button>
                    </div>
                </form>

                <p class="mt-5 text-center text-sm font-normal text-gray-600 dark:text-gray-400">
                    Don't have an account?
                    <a href="/signup" class="font-semibold text-brand-500 hover:text-brand-600 dark:text-brand-400">Sign Up</a>
                </p>
            </div>
        </div>

        <svg class="readbee-login-waves" xmlns="http://www.w3.org/2000/svg" viewBox="0 24 150 28" preserveAspectRatio="none">
            <defs>
                <path id="readbee-login-wave-path" d="M-160 44c30 0 58-18 88-18s58 18 88 18 58-18 88-18 58 18 88 18v44h-352z" />
            </defs>
            <g class="readbee-login-wave1"><use href="#readbee-login-wave-path" x="50" y="3" /></g>
            <g class="readbee-login-wave2"><use href="#readbee-login-wave-path" x="50" y="0" /></g>
            <g class="readbee-login-wave3"><use href="#readbee-login-wave-path" x="50" y="9" /></g>
        </svg>

        <div class="fixed right-6 bottom-6 z-50">
            <button
                class="bg-brand-500 hover:bg-brand-600 inline-flex size-14 items-center justify-center rounded-full text-white shadow-theme-md transition-colors"
                @click.prevent="$store.theme.toggle()"
                aria-label="Toggle theme">
                <svg class="hidden fill-current dark:block" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"> <path fill-rule="evenodd" clip-rule="evenodd" d="M9.99998 1.5415C10.4142 1.5415 10.75 1.87729 10.75 2.2915V3.5415C10.75 3.95572 10.4142 4.2915 9.99998 4.2915C9.58577 4.2915 9.24998 3.95572 9.24998 3.5415V2.2915C9.24998 1.87729 9.58577 1.5415 9.99998 1.5415ZM10.0009 6.79327C8.22978 6.79327 6.79402 8.22904 6.79402 10.0001C6.79402 11.7712 8.22978 13.207 10.0009 13.207C11.772 13.207 13.2078 11.7712 13.2078 10.0001C13.2078 8.22904 11.772 6.79327 10.0009 6.79327ZM5.29402 10.0001C5.29402 7.40061 7.40135 5.29327 10.0009 5.29327C12.6004 5.29327 14.7078 7.40061 14.7078 10.0001C14.7078 12.5997 12.6004 14.707 10.0009 14.707C7.40135 14.707 5.29402 12.5997 5.29402 10.0001ZM15.9813 5.08035C16.2742 4.78746 16.2742 4.31258 15.9813 4.01969C15.6884 3.7268 15.2135 3.7268 14.9207 4.01969L14.0368 4.90357C13.7439 5.19647 13.7439 5.67134 14.0368 5.96423C14.3297 6.25713 14.8045 6.25713 15.0974 5.96423L15.9813 5.08035ZM18.4577 10.0001C18.4577 10.4143 18.1219 10.7501 17.7077 10.7501H16.4577C16.0435 10.7501 15.7077 10.4143 15.7077 10.0001C15.7077 9.58592 16.0435 9.25013 16.4577 9.25013H17.7077C18.1219 9.25013 18.4577 9.58592 18.4577 10.0001ZM14.9207 15.9806C15.2135 16.2735 15.6884 16.2735 15.9813 15.9806C16.2742 15.6877 16.2742 15.2128 15.9813 14.9199L15.0974 14.036C14.8045 13.7431 14.3297 13.7431 14.0368 14.036C13.7439 14.3289 13.7439 14.8038 14.0368 15.0967L14.9207 15.9806ZM9.99998 15.7088C10.4142 15.7088 10.75 16.0445 10.75 16.4588V17.7088C10.75 18.123 10.4142 18.4588 9.99998 18.4588C9.58577 18.4588 9.24998 18.123 9.24998 17.7088V16.4588C9.24998 16.0445 9.58577 15.7088 9.99998 15.7088ZM5.96356 15.0972C6.25646 14.8043 6.25646 14.3295 5.96356 14.0366C5.67067 13.7437 5.1958 13.7437 4.9029 14.0366L4.01902 14.9204C3.72613 15.2133 3.72613 15.6882 4.01902 15.9811C4.31191 16.274 4.78679 16.274 5.07968 15.9811L5.96356 15.0972ZM4.29224 10.0001C4.29224 10.4143 3.95645 10.7501 3.54224 10.7501H2.29224C1.87802 10.7501 1.54224 10.4143 1.54224 10.0001C1.54224 9.58592 1.87802 9.25013 2.29224 9.25013H3.54224C3.95645 9.25013 4.29224 9.58592 4.29224 10.0001ZM4.9029 5.9637C5.1958 6.25659 5.67067 6.25659 5.96356 5.9637C6.25646 5.6708 6.25646 5.19593 5.96356 4.90303L5.07968 4.01915C4.78679 3.72626 4.31191 3.72626 4.01902 4.01915C3.72613 4.31204 3.72613 4.78692 4.01902 5.07981L4.9029 5.9637Z" fill="" /></svg>
                <svg class="fill-current dark:hidden" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17.4547 11.97L18.1799 12.1611C18.265 11.8383 18.1265 11.4982 17.8401 11.3266C17.5538 11.1551 17.1885 11.1934 16.944 11.4207L17.4547 11.97ZM8.0306 2.5459L8.57989 3.05657C8.80718 2.81209 8.84554 2.44682 8.67398 2.16046C8.50243 1.8741 8.16227 1.73559 7.83948 1.82066L8.0306 2.5459ZM12.9154 13.0035C9.64678 13.0035 6.99707 10.3538 6.99707 7.08524H5.49707C5.49707 11.1823 8.81835 14.5035 12.9154 14.5035V13.0035ZM16.944 11.4207C15.8869 12.4035 14.4721 13.0035 12.9154 13.0035V14.5035C14.8657 14.5035 16.6418 13.7499 17.9654 12.5193L16.944 11.4207ZM16.7295 11.7789C15.9437 14.7607 13.2277 16.9586 10.0003 16.9586V18.4586C13.9257 18.4586 17.2249 15.7853 18.1799 12.1611L16.7295 11.7789ZM10.0003 16.9586C6.15734 16.9586 3.04199 13.8433 3.04199 10.0003H1.54199C1.54199 14.6717 5.32892 18.4586 10.0003 18.4586V16.9586ZM3.04199 10.0003C3.04199 6.77289 5.23988 4.05695 8.22173 3.27114L7.83948 1.82066C4.21532 2.77574 1.54199 6.07486 1.54199 10.0003H3.04199ZM6.99707 7.08524C6.99707 5.52854 7.5971 4.11366 8.57989 3.05657L7.48132 2.03522C6.25073 3.35885 5.49707 5.13487 5.49707 7.08524H6.99707Z" fill="" /></svg>
            </button>
        </div>
    </main>
@endsection
