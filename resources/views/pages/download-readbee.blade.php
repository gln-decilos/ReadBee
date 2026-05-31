<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Download ReadBee' }}</title>
    <meta name="description" content="Download the ReadBee Evaluator App and ReadBee Offline Pupil App for reading assessment and practice.">
    <meta name="keywords" content="ReadBee, download ReadBee, evaluator app, pupil app, reading assessment app">

    <link href="{{ asset('landing-assets/images/ReadBeefavicon.png') }}" rel="icon">
    <link href="{{ asset('landing-assets/images/apple-touch-icon.png') }}" rel="apple-touch-icon">

    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">

    <link href="{{ asset('landing-assets/vendors/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('landing-assets/vendors/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('landing-assets/vendors/aos/aos.css') }}" rel="stylesheet">
    <link href="{{ asset('landing-assets/vendors/glightbox/css/glightbox.min.css') }}" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('landing-assets/vendors/swiper/swiper-bundle.min.css') }}" rel="stylesheet">
    <link href="{{ asset('landing-assets/css/main33.css') }}" rel="stylesheet">

    <style>
        :root {
            --readbee-yellow: #ffc107;
            --readbee-yellow-dark: #e0a800;
            --readbee-dark: #111827;
            --readbee-muted: #667085;
        }

        body.index-page { background: #ffffff; }

        .header,
        #header {
            padding-top: 8px !important;
            padding-bottom: 8px !important;
            min-height: auto !important;
            transition: background-color .25s ease, box-shadow .25s ease, border-color .25s ease, padding .25s ease;
        }

        .logo img,
        .navbar-brand img { max-height: 42px !important; }

        body.readbee-nav-scrolled .header,
        body.readbee-nav-scrolled #header,
        body.mobile-nav-active .header,
        body.mobile-nav-active #header {
            background-color: #ffffff !important;
            border-bottom: 1px solid rgba(229, 231, 235, .95) !important;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .07) !important;
            backdrop-filter: blur(10px);
        }

        body.readbee-nav-scrolled .header a,
        body.readbee-nav-scrolled #header a,
        body.mobile-nav-active .header a,
        body.mobile-nav-active #header a {
            color: var(--readbee-dark) !important;
        }

        .readbee-login-btn,
        #navmenu a.readbee-login-btn {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-height: 34px !important;
            padding: 7px 16px !important;
            border-radius: 25px !important;
            background-color: var(--readbee-yellow) !important;
            color: #000000 !important;
            border: 0 !important;
            font-weight: 350 !important;
            line-height: 1 !important;
            text-decoration: none !important;
            box-shadow: none !important;
            transition: background-color .2s ease, color .2s ease !important;
        }

        .readbee-login-btn:hover,
        .readbee-login-btn:focus,
        #navmenu a.readbee-login-btn:hover,
        #navmenu a.readbee-login-btn:focus {
            background-color: var(--readbee-yellow-dark) !important;
            color: #000000 !important;
            text-decoration: none !important;
            box-shadow: none !important;
        }

        .readbee-login-btn::before,
        .readbee-login-btn::after,
        #navmenu a.readbee-login-btn::before,
        #navmenu a.readbee-login-btn::after {
            display: none !important;
            content: none !important;
        }

        body.readbee-nav-scrolled .mobile-nav-toggle,
        body.readbee-nav-scrolled .mobile-nav-toggle i,
        body.mobile-nav-active .mobile-nav-toggle,
        body.mobile-nav-active .mobile-nav-toggle i {
            color: var(--readbee-dark) !important;
            fill: var(--readbee-dark) !important;
            stroke: var(--readbee-dark) !important;
        }

        .download-readbee-page .download-hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            color: #ffffff;
            background: var(--background-color);
            padding: 130px 0 120px;
        }

        .download-readbee-page .download-hero .hero-bg {
            position: absolute;
            inset: 0;
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 1;
        }

        .download-readbee-page .download-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: color-mix(in srgb, var(--background-color), transparent 10%);
            z-index: 2;
        }

        .download-readbee-page .download-hero .container {
            position: relative;
            z-index: 3;
        }

        .download-readbee-page .download-hero h1,
        .download-readbee-page .download-hero p {
            color: #ffffff;
        }

        .download-mascot {
            width: 90px;
            height: 90px;
            object-fit: contain;
        }

        .readbee-pill-btn {
            border-radius: 25px;
            font-weight: 700;
            padding: .68rem 1.35rem;
        }

        .download-card {
            border: 1px solid rgba(17, 24, 39, .08);
            border-radius: 24px;
            background: #ffffff;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .08);
            overflow: hidden;
        }

        .download-card .badge-soft {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            background: rgba(255, 193, 7, .16);
            color: #6b4f00;
            padding: 6px 12px;
            font-size: .78rem;
            font-weight: 700;
        }

        .app-image-wrapper {
            max-width: 300px;
            margin: 0 auto;
        }

        .app-image {
            width: 100%;
            max-height: 450px;
            object-fit: contain;
            border: 0;
            box-shadow: none;
            border-radius: 0;
            background: transparent;
            transition: transform .35s ease;
        }

        .download-card:hover .app-image { transform: translateY(-4px) scale(1.02); }

        .app-feature-list {
            list-style: none;
            padding-left: 0;
            margin-bottom: 0;
        }

        .app-feature-list li {
            margin-bottom: 12px;
            padding-left: 30px;
            position: relative;
            color: #475467;
        }

        .app-feature-list li::before {
            content: "✓";
            position: absolute;
            left: 0;
            top: 0;
            color: var(--readbee-yellow);
            font-weight: 900;
        }

        .readbee-section-title {
            font-family: Poppins, sans-serif;
            font-weight: 800;
            color: var(--readbee-dark);
            letter-spacing: -.025em;
        }

        .download-info-strip {
            border: 1px solid rgba(229, 231, 235, .95);
            border-radius: 20px;
            background: #f8fafc;
        }

        .footer .sitename,
        footer .sitename { color: var(--readbee-yellow) !important; }

        @media (max-width: 1199px) {
            body.index-page.mobile-nav-active { overflow: hidden; }

            body.index-page.mobile-nav-active .navmenu,
            body.index-page.mobile-nav-active #navmenu {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                pointer-events: auto !important;
                position: fixed !important;
                top: 64px !important;
                left: 14px !important;
                right: 14px !important;
                bottom: auto !important;
                width: auto !important;
                max-height: calc(100vh - 84px) !important;
                overflow-y: auto !important;
                z-index: 99999 !important;
                padding: 12px !important;
                border-radius: 16px !important;
                background: #ffffff !important;
                border: 1px solid rgba(229, 231, 235, .95) !important;
                box-shadow: 0 18px 45px rgba(15, 23, 42, .18) !important;
                transform: none !important;
            }

            body.index-page.mobile-nav-active .navmenu > ul,
            body.index-page.mobile-nav-active #navmenu > ul {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                max-height: none !important;
                background: #ffffff !important;
                margin: 0 !important;
                padding: 4px !important;
                width: 100% !important;
            }

            body.index-page.mobile-nav-active .navmenu li,
            body.index-page.mobile-nav-active #navmenu li { width: 100% !important; }

            body.index-page.mobile-nav-active .navmenu a,
            body.index-page.mobile-nav-active #navmenu a { color: var(--readbee-dark) !important; }

            #navmenu a.readbee-login-btn { width: fit-content !important; margin-top: .35rem; }
        }

        @media (max-width: 1199px) {
            body.mobile-nav-active { overflow: hidden; }

            body.mobile-nav-active #navmenu,
            body.mobile-nav-active .navmenu {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                pointer-events: auto !important;
                position: fixed !important;
                top: 64px !important;
                left: 14px !important;
                right: 14px !important;
                bottom: auto !important;
                width: auto !important;
                max-height: calc(100vh - 84px) !important;
                overflow-y: auto !important;
                z-index: 99999 !important;
                padding: 12px !important;
                border-radius: 16px !important;
                background: #ffffff !important;
                border: 1px solid rgba(229, 231, 235, .95) !important;
                box-shadow: 0 18px 45px rgba(15, 23, 42, .18) !important;
                transform: none !important;
            }

            body.mobile-nav-active #navmenu > ul,
            body.mobile-nav-active .navmenu > ul {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                position: static !important;
                inset: auto !important;
                width: 100% !important;
                max-height: none !important;
                margin: 0 !important;
                padding: 4px !important;
                overflow: visible !important;
                background: #ffffff !important;
                border: 0 !important;
                box-shadow: none !important;
            }

            body.mobile-nav-active #navmenu li,
            body.mobile-nav-active .navmenu li { width: 100% !important; }

            body.mobile-nav-active #navmenu a,
            body.mobile-nav-active .navmenu a {
                color: var(--readbee-dark) !important;
                justify-content: flex-start !important;
            }

            body.mobile-nav-active #navmenu a.readbee-login-btn {
                display: inline-flex !important;
                width: fit-content !important;
                margin-top: .35rem !important;
            }
        }



        /* Download page mobile menu fix: force options to show like landing page. */
        @media (max-width: 1199px) {
            body.download-readbee-page.mobile-nav-active {
                overflow: hidden !important;
            }

            body.download-readbee-page.mobile-nav-active #navmenu,
            body.download-readbee-page.mobile-nav-active .navmenu {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                pointer-events: auto !important;
                position: fixed !important;
                top: 64px !important;
                left: 14px !important;
                right: 14px !important;
                bottom: auto !important;
                width: auto !important;
                height: auto !important;
                max-width: none !important;
                max-height: calc(100vh - 84px) !important;
                overflow-y: auto !important;
                z-index: 99999 !important;
                padding: 12px !important;
                border-radius: 16px !important;
                background: #ffffff !important;
                border: 1px solid rgba(229, 231, 235, .95) !important;
                box-shadow: 0 18px 45px rgba(15, 23, 42, .18) !important;
                transform: none !important;
            }

            body.download-readbee-page.mobile-nav-active #navmenu > ul,
            body.download-readbee-page.mobile-nav-active .navmenu > ul {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                position: static !important;
                inset: auto !important;
                width: 100% !important;
                height: auto !important;
                max-height: none !important;
                overflow: visible !important;
                margin: 0 !important;
                padding: 4px !important;
                background: #ffffff !important;
                border: 0 !important;
                box-shadow: none !important;
                transform: none !important;
            }

            body.download-readbee-page.mobile-nav-active #navmenu li,
            body.download-readbee-page.mobile-nav-active .navmenu li {
                display: block !important;
                width: 100% !important;
                margin: 0 !important;
            }

            body.download-readbee-page.mobile-nav-active #navmenu a,
            body.download-readbee-page.mobile-nav-active .navmenu a {
                display: flex !important;
                align-items: center !important;
                justify-content: flex-start !important;
                width: 100% !important;
                padding: 10px 12px !important;
                color: var(--readbee-dark) !important;
                border-radius: 10px !important;
            }

            body.download-readbee-page.mobile-nav-active #navmenu a.readbee-login-btn,
            body.download-readbee-page.mobile-nav-active .navmenu a.readbee-login-btn {
                display: inline-flex !important;
                width: fit-content !important;
                margin: 8px 12px 4px !important;
                padding: 9px 16px !important;
            }
        }

        @media (max-width: 991px) {
            .download-readbee-page .download-hero { min-height: 92vh; padding: 120px 0 90px; }
            .app-image-wrapper { max-width: 245px; }
            .app-image { max-height: 240px; }
        }

        @media (max-width: 576px) {
            .download-readbee-page .download-hero { min-height: 88vh; padding: 105px 0 76px; }
            .download-mascot { width: 76px; height: 76px; }
            .readbee-pill-btn { width: 100%; }
            .app-image-wrapper { max-width: 205px; }
        }
    </style>
</head>
<body class="index-page download-readbee-page">
    <header id="header" class="header d-flex align-items-center fixed-top">
        <div class="container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
            <a href="{{ url('/') }}" class="logo d-flex align-items-center">
                <img src="{{ asset('landing-assets/images/ReadBee-Logo-Light.png') }}" alt="ReadBee logo" class="logo-dark">
                <img src="{{ asset('landing-assets/images/ReadBee-Logo-Dark.png') }}" alt="ReadBee logo" class="logo-light">
            </a>

            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="{{ url('/') }}">Home</a></li>
                    <li><a href="{{ url('/#about') }}">About</a></li>
                    <li><a href="{{ url('/#features') }}">Features</a></li>
                    <li><a href="{{ url('/#team') }}">Team</a></li>
                    <li><a href="{{ url('/#contact') }}">Contact</a></li>
                    <li><a href="{{ url('/download-readbee') }}" class="active">Download ReadBee</a></li>
                    <li><a href="{{ route('signin') }}" class="readbee-login-btn">Login</a></li>
                </ul>

                <i class="mobile-nav-toggle d-xl-none bi bi-list" role="button" aria-label="Toggle navigation" aria-expanded="false"></i>
            </nav>
        </div>
    </header>

    <main class="main">
        <section id="hero" class="hero download-hero section dark-background">
            <img src="{{ asset('landing-assets/images/hero-bg-2.jpg') }}" alt="" class="hero-bg">

            <div class="container">
                <div class="row justify-content-center align-items-center text-center text-white py-5" data-aos="fade-up">
                    <div class="col-lg-8">
                        <img src="{{ asset('landing-assets/images/CuteBee3.png') }}" alt="ReadBee mascot" class="download-mascot mb-3">
                        <h1 class="fw-bold mb-3">Download ReadBee App</h1>
                        <p class="lead mb-4">
                            Empower reading assessment with the <strong>ReadBee Evaluator App</strong> and help pupils practice anytime with the <strong>Offline Pupil App</strong>.
                        </p>

                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                            <a href="{{ asset('landing-apk/readbee-evaluator-app-2025.apk') }}" class="btn btn-warning text-dark readbee-pill-btn">
                                <i class="fa-solid fa-download me-2"></i>Evaluator App
                            </a>
                            <a href="{{ asset('landing-apk/readbee-pupil-app-2025.apk') }}" class="btn btn-outline-light readbee-pill-btn">
                                <i class="fa-solid fa-download me-2"></i>Pupil App
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <svg class="hero-waves" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 24 150 28" preserveAspectRatio="none">
                <defs>
                    <path id="wave-path" d="M-160 44c30 0 58-18 88-18s58 18 88 18s58-18 88-18s58 18 88 18v44h-352z"></path>
                </defs>
                <g class="wave1"><use xlink:href="#wave-path" x="50" y="3"></use></g>
                <g class="wave2"><use xlink:href="#wave-path" x="50" y="0"></use></g>
                <g class="wave3"><use xlink:href="#wave-path" x="50" y="9"></use></g>
            </svg>
        </section>

        <section id="about" class="about section py-5">
            <div class="container" data-aos="fade-up">
                <div class="text-center mb-5">
                    <h2 class="readbee-section-title display-6 mb-2">About ReadBee Applications</h2>
                    <p class="text-muted fs-5 mb-0">Transforming reading assessment with practical technology solutions for educators and pupils.</p>
                </div>

                <div class="download-info-strip p-4 p-lg-5 mb-5">
                    <div class="row g-4 text-center">
                        <div class="col-md-4">
                            <div class="fw-bold text-dark">2 Applications</div>
                            <div class="text-muted small">Evaluator and pupil app</div>
                        </div>
                        <div class="col-md-4">
                            <div class="fw-bold text-dark">Offline Ready</div>
                            <div class="text-muted small">Pupil practice can work offline</div>
                        </div>
                        <div class="col-md-4">
                            <div class="fw-bold text-dark">Assessment Support</div>
                            <div class="text-muted small">Designed for reading monitoring</div>
                        </div>
                    </div>
                </div>

                <div class="download-card p-4 p-lg-5 mb-5">
                    <div class="row align-items-center gy-5">
                        <div class="col-lg-6 order-2 order-lg-1" data-aos="fade-right">
                            <span class="badge-soft mb-3">For Evaluators / Teachers</span>
                            <h3 class="fw-bold mb-3">📱 ReadBee Evaluator App</h3>
                            <p class="lead mb-4">
                                A tool for educators to assess pupils, record reading performance, and support progress monitoring.
                            </p>

                            <ul class="app-feature-list mb-4">
                                <li>Reading assessment support with scoring</li>
                                <li>Miscue and performance tracking</li>
                                <li>Assessment calendar support</li>
                                <li>Multi-pupil management</li>
                                <li>Progress monitoring for assigned sections</li>
                            </ul>

                            <a href="{{ asset('landing-apk/readbee-evaluator-app-2025.apk') }}" class="btn btn-warning text-dark fw-semibold px-4 py-2 readbee-pill-btn">
                                <i class="fa-solid fa-download me-2"></i>Download Evaluator App
                            </a>
                        </div>

                        <div class="col-lg-6 order-1 order-lg-2 text-center" data-aos="fade-left">
                            <div class="app-image-wrapper">
                                <img src="{{ asset('landing-assets/images/3D-character1.png') }}" alt="ReadBee Evaluator App" class="img-fluid app-image">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="download-card p-4 p-lg-5">
                    <div class="row align-items-center gy-5">
                        <div class="col-lg-6 text-center" data-aos="fade-right">
                            <div class="app-image-wrapper">
                                <img src="{{ asset('landing-assets/images/3D-character2.png') }}" alt="ReadBee Offline Pupil App" class="img-fluid app-image">
                            </div>
                        </div>

                        <div class="col-lg-6" data-aos="fade-left">
                            <span class="badge-soft mb-3">For Pupils</span>
                            <h3 class="fw-bold mb-3">🐝 ReadBee Offline Pupil App</h3>
                            <p class="lead mb-4">
                                A student-friendly app for independent reading practice, designed to help pupils practice even without internet connection.
                            </p>

                            <ul class="app-feature-list mb-4">
                                <li>Offline reading practice</li>
                                <li>Interactive reading exercises</li>
                                <li>Comprehension quizzes</li>
                                <li>Age-appropriate reading materials</li>
                                <li>Audio support for reading materials</li>
                            </ul>

                            <a href="{{ asset('landing-apk/readbee-pupil-app-2025.apk') }}" class="btn btn-warning text-dark fw-semibold px-4 py-2 readbee-pill-btn">
                                <i class="fa-solid fa-download me-2"></i>Download Pupil App
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer id="footer" class="footer dark-background">
        <div class="container footer-top">
            <div class="row gy-4">
                <div class="col-lg-4 col-md-6 footer-about">
                    <a href="{{ url('/') }}" class="logo d-flex align-items-center">
                        <img src="{{ asset('landing-assets/images/ReadBee-Logo-Dark.png') }}" alt="ReadBee logo">
                    </a>

                    <div class="social-links d-flex mt-4">
                        <a href="#"><i class="bi bi-twitter-x"></i></a>
                        <a href="#"><i class="bi bi-facebook"></i></a>
                        <a href="#"><i class="bi bi-instagram"></i></a>
                        <a href="#"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>

                <div class="col-lg-2 col-md-3 footer-links">
                    <h4>Useful Links</h4>
                    <ul>
                        <li><a href="{{ url('/') }}">Home</a></li>
                        <li><a href="{{ url('/#about') }}">About</a></li>
                        <li><a href="{{ url('/#features') }}">Features</a></li>
                        <li><a href="{{ url('/#team') }}">Team</a></li>
                        <li><a href="{{ url('/#contact') }}">Contact</a></li>
                    </ul>
                </div>

                <div class="col-lg-4 col-md-12 footer-newsletter">
                    <p>ReadBee is dedicated to transforming reading assessment through innovative technology solutions that empower educators and engage pupils in their literacy journey.</p>
                </div>
            </div>
        </div>

        <div class="container copyright text-center mt-4">
            <p>© <span>Copyright 2025</span> <strong class="px-1 sitename">ReadBee.</strong> <span>Batangas State University TNEU ARASOF-Nasugbu. All Rights Reserved</span></p>
        </div>
    </footer>

    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <div id="preloader">
        <img src="{{ asset('landing-assets/images/LoadingBee.gif') }}" alt="Loading...">
    </div>

    <script src="{{ asset('landing-assets/vendors/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('landing-assets/vendors/php-email-form/validate.js') }}"></script>
    <script src="{{ asset('landing-assets/vendors/aos/aos.js') }}"></script>
    <script src="{{ asset('landing-assets/vendors/glightbox/js/glightbox.min.js') }}"></script>
    <script src="{{ asset('landing-assets/vendors/purecounter/purecounter_vanilla.js') }}"></script>
    <script src="{{ asset('landing-assets/vendors/swiper/swiper-bundle.min.js') }}"></script>
    <script src="{{ asset('landing-assets/js/main.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggleScrolledNavbar = function () {
                document.body.classList.toggle('readbee-nav-scrolled', window.scrollY > 20);
            };

            const scrollTop = document.getElementById('scroll-top');
            const toggleScrollTop = function () {
                if (!scrollTop) return;
                scrollTop.classList.toggle('active', window.scrollY > 100);
            };
            if (scrollTop) {
                scrollTop.addEventListener('click', function (event) {
                    event.preventDefault();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            }

            const getMobileMenu = function () {
                return document.querySelector('#navmenu, .navmenu');
            };

            const setMobileMenuOpen = function (open, toggle) {
                const menu = getMobileMenu();
                document.body.classList.toggle('mobile-nav-active', open);

                if (menu) {
                    menu.classList.toggle('show', open);
                    menu.classList.toggle('active', open);
                    menu.style.display = open && window.innerWidth < 1200 ? 'block' : '';
                    menu.style.visibility = open && window.innerWidth < 1200 ? 'visible' : '';
                    menu.style.opacity = open && window.innerWidth < 1200 ? '1' : '';
                    menu.style.pointerEvents = open && window.innerWidth < 1200 ? 'auto' : '';

                    const list = menu.querySelector('ul');
                    if (list) {
                        list.style.display = open && window.innerWidth < 1200 ? 'block' : '';
                        list.style.visibility = open && window.innerWidth < 1200 ? 'visible' : '';
                        list.style.opacity = open && window.innerWidth < 1200 ? '1' : '';
                        list.style.position = open && window.innerWidth < 1200 ? 'static' : '';
                        list.style.inset = open && window.innerWidth < 1200 ? 'auto' : '';
                        list.style.maxHeight = open && window.innerWidth < 1200 ? 'none' : '';
                        list.style.overflow = open && window.innerWidth < 1200 ? 'visible' : '';
                    }

                    if (open && window.innerWidth < 1200) {
                        const header = document.querySelector('.header, #header');
                        const headerBottom = header ? Math.ceil(header.getBoundingClientRect().bottom) : 58;
                        menu.style.position = 'fixed';
                        menu.style.top = `${Math.max(headerBottom + 8, 58)}px`;
                        menu.style.left = '14px';
                        menu.style.right = '14px';
                        menu.style.bottom = 'auto';
                        menu.style.width = 'auto';
                        menu.style.height = 'auto';
                        menu.style.zIndex = '99999';
                    } else {
                        menu.style.position = '';
                        menu.style.top = '';
                        menu.style.left = '';
                        menu.style.right = '';
                        menu.style.bottom = '';
                        menu.style.width = '';
                        menu.style.height = '';
                        menu.style.zIndex = '';
                    }
                }

                const icon = toggle?.matches?.('.mobile-nav-toggle') ? toggle : document.querySelector('.mobile-nav-toggle');
                if (icon) {
                    icon.classList.toggle('bi-list', !open);
                    icon.classList.toggle('bi-x', open);
                    icon.setAttribute('aria-expanded', open ? 'true' : 'false');
                }
            };

            const handleMobileToggleClick = function (event) {
                const toggle = event.target.closest('.mobile-nav-toggle, .navbar-toggler, [data-bs-toggle="collapse"], [data-readbee-mobile-toggle]');

                if (toggle) {
                    event.preventDefault();
                    event.stopPropagation();
                    if (event.stopImmediatePropagation) event.stopImmediatePropagation();
                    setMobileMenuOpen(!document.body.classList.contains('mobile-nav-active'), toggle);
                    return;
                }

                const navLink = event.target.closest('#navmenu a, .navmenu a');
                if (navLink && document.body.classList.contains('mobile-nav-active')) {
                    setMobileMenuOpen(false);
                }
            };

            document.addEventListener('click', handleMobileToggleClick, true);
            document.addEventListener('click', handleMobileToggleClick);

            window.addEventListener('resize', function () {
                if (window.innerWidth >= 1200) {
                    setMobileMenuOpen(false);
                }
            });

            window.addEventListener('load', function () {
                const preloader = document.getElementById('preloader');
                if (preloader) preloader.style.display = 'none';
            });

            toggleScrolledNavbar();
            toggleScrollTop();
            window.addEventListener('scroll', function () {
                toggleScrolledNavbar();
                toggleScrollTop();
            }, { passive: true });

            if (window.AOS) {
                AOS.init({ duration: 700, easing: 'ease-in-out', once: true });
            }
        });
    </script>
</body>
</html>
