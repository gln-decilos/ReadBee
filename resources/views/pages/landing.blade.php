<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'ReadBee' }}</title>
    <meta name="description" content="ReadBee streamlines reading assessments with real-time scoring, progress tracking, and smart insights.">
    <meta name="keywords" content="ReadBee, reading assessment, literacy, education technology">

    <link href="{{ asset('landing-assets/images/ReadBeefavicon.png') }}" rel="icon">
    <link href="{{ asset('landing-assets/images/apple-touch-icon.png') }}" rel="apple-touch-icon">

    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">

    <link href="{{ asset('landing-assets/vendors/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('landing-assets/vendors/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('landing-assets/vendors/aos/aos.css') }}" rel="stylesheet">
    <link href="{{ asset('landing-assets/vendors/glightbox/css/glightbox.min.css') }}" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('landing-assets/vendors/swiper/swiper-bundle.min.css') }}" rel="stylesheet">
    <link href="{{ asset('landing-assets/css/main33.css') }}" rel="stylesheet">

    <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>


    <style>
        .readbee-feature-card { transition: background-color 0.3s ease, transform 0.3s ease; }
        .readbee-feature-card:hover { background-color: #000 !important; cursor: pointer; transform: scale(1.05); }
        .readbee-feature-card:hover h5, .readbee-feature-card:hover p { color: #fff !important; }
        .readbee-login-btn { background-color: #ffc107; color: #000; padding: 6px 16px; border-radius: 4px; }
        .readbee-pill-btn { border-radius: 25px; }
        .readbee-download-section { background: #fff; }

        /* Hide the old embedded Download ReadBee section on the landing page.
           Download ReadBee now has its own page at /download-readbee. */
        #download,
        #downloads,
        .readbee-download-section,
        [data-section="download"],
        [data-readbee-section="download"] {
            display: none !important;
        }

        /* Landing navbar: compact, white background after scroll */
        body.index-page .header,
        body.index-page #header,
        body.index-page .navbar,
        body.index-page .readbee-navbar,
        #readbee-landing-root .header,
        #readbee-landing-root #header,
        #readbee-landing-root .navbar,
        #readbee-landing-root .readbee-navbar {
            transition: background-color .25s ease, box-shadow .25s ease, border-color .25s ease, padding .25s ease;
        }

        body.index-page .header,
        body.index-page #header,
        #readbee-landing-root .header,
        #readbee-landing-root #header {
            padding-top: 8px !important;
            padding-bottom: 8px !important;
            min-height: auto !important;
        }

        body.index-page .navbar,
        body.index-page .readbee-navbar,
        #readbee-landing-root .navbar,
        #readbee-landing-root .readbee-navbar {
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            min-height: auto !important;
        }

        body.index-page .logo img,
        body.index-page .navbar-brand img,
        #readbee-landing-root .logo img,
        #readbee-landing-root .navbar-brand img {
            max-height: 42px !important;
        }

        body.readbee-nav-scrolled .header,
        body.readbee-nav-scrolled #header,
        body.readbee-nav-scrolled .navbar,
        body.readbee-nav-scrolled .readbee-navbar,
        #readbee-landing-root.readbee-nav-scrolled .header,
        #readbee-landing-root.readbee-nav-scrolled #header,
        #readbee-landing-root.readbee-nav-scrolled .navbar,
        #readbee-landing-root.readbee-nav-scrolled .readbee-navbar {
            background-color: #ffffff !important;
            border-bottom: 1px solid rgba(229, 231, 235, .95) !important;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .07) !important;
            backdrop-filter: blur(10px);
        }

        body.readbee-nav-scrolled .header a,
        body.readbee-nav-scrolled #header a,
        body.readbee-nav-scrolled .navbar a,
        body.readbee-nav-scrolled .readbee-navbar a,
        #readbee-landing-root.readbee-nav-scrolled .header a,
        #readbee-landing-root.readbee-nav-scrolled #header a,
        #readbee-landing-root.readbee-nav-scrolled .navbar a,
        #readbee-landing-root.readbee-nav-scrolled .readbee-navbar a {
            color: #111827 !important;
        }

        body.readbee-nav-scrolled .header .logo,
        body.readbee-nav-scrolled .header .sitename,
        body.readbee-nav-scrolled #header .logo,
        body.readbee-nav-scrolled #header .sitename,
        #readbee-landing-root.readbee-nav-scrolled .logo,
        #readbee-landing-root.readbee-nav-scrolled .sitename {
            color: #111827 !important;
        }

        /* Login button: same landing-page style, compact, no hover underline/line */
        .readbee-login-btn,
        body.index-page a[href*="signin"],
        #readbee-landing-root a[href*="signin"] {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-height: 34px !important;
            padding: 7px 16px !important;
            border-radius: 25px !important;
            background-color: #ffc107 !important;
            color: #000000 !important;
            border: 0 !important;
            font-weight: 600 !important;
            line-height: 1 !important;
            text-decoration: none !important;
            box-shadow: none !important;
            transform: none !important;
            transition: background-color .2s ease, color .2s ease, transform .2s ease !important;
        }

        .readbee-login-btn:hover,
        .readbee-login-btn:focus,
        body.index-page a[href*="signin"]:hover,
        body.index-page a[href*="signin"]:focus,
        #readbee-landing-root a[href*="signin"]:hover,
        #readbee-landing-root a[href*="signin"]:focus {
            background-color: #e0a800 !important;
            color: #000000 !important;
            text-decoration: none !important;
            box-shadow: none !important;
            transform: none !important;
        }

        .readbee-login-btn::before,
        .readbee-login-btn::after,
        body.index-page a[href*="signin"]::before,
        body.index-page a[href*="signin"]::after,
        #readbee-landing-root a[href*="signin"]::before,
        #readbee-landing-root a[href*="signin"]::after {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
            opacity: 0 !important;
            content: none !important;
        }



        /* Mobile menu icon: black when navbar turns white on scroll. */
        body.readbee-nav-scrolled .mobile-nav-toggle,
        body.readbee-nav-scrolled .mobile-nav-toggle i,
        body.readbee-nav-scrolled .mobile-nav-toggle svg,
        body.readbee-nav-scrolled .navbar-toggler,
        body.readbee-nav-scrolled .navbar-toggler i,
        body.readbee-nav-scrolled .navbar-toggler svg,
        #readbee-landing-root.readbee-nav-scrolled .mobile-nav-toggle,
        #readbee-landing-root.readbee-nav-scrolled .mobile-nav-toggle i,
        #readbee-landing-root.readbee-nav-scrolled .mobile-nav-toggle svg,
        #readbee-landing-root.readbee-nav-scrolled .navbar-toggler,
        #readbee-landing-root.readbee-nav-scrolled .navbar-toggler i,
        #readbee-landing-root.readbee-nav-scrolled .navbar-toggler svg {
            color: #111827 !important;
            fill: #111827 !important;
            stroke: #111827 !important;
        }

        body.readbee-nav-scrolled .navbar-toggler,
        #readbee-landing-root.readbee-nav-scrolled .navbar-toggler {
            border-color: rgba(17, 24, 39, .18) !important;
        }

        body.readbee-nav-scrolled .navbar-toggler-icon,
        #readbee-landing-root.readbee-nav-scrolled .navbar-toggler-icon {
            filter: brightness(0) saturate(100%) !important;
        }

        /* Footer brand: make only the ReadBee brand text yellow. */
        .readbee-footer-brand-yellow,
        body.index-page footer .sitename,
        body.index-page .footer .sitename,
        body.index-page footer .footer-brand,
        body.index-page .footer .footer-brand,
        #readbee-landing-root footer .sitename,
        #readbee-landing-root .footer .sitename,
        #readbee-landing-root footer .footer-brand,
        #readbee-landing-root .footer .footer-brand {
            color: #ffc107 !important;
        }


        /* Mobile menu fallback: keeps options visible when the menu icon is clicked. */
        @media (max-width: 1199px) {
            body.index-page.mobile-nav-active {
                overflow: hidden;
            }

            body.index-page.mobile-nav-active .header,
            body.index-page.mobile-nav-active #header,
            #readbee-landing-root.readbee-mobile-menu-open .header,
            #readbee-landing-root.readbee-mobile-menu-open #header {
                background-color: #ffffff !important;
                border-bottom: 1px solid rgba(229, 231, 235, .95) !important;
                box-shadow: 0 8px 22px rgba(15, 23, 42, .07) !important;
            }

            body.index-page.mobile-nav-active .navmenu,
            body.index-page.mobile-nav-active #navmenu,
            body.index-page.mobile-nav-active .navbar-collapse,
            body.index-page.mobile-nav-active .readbee-mobile-menu,
            #readbee-landing-root.readbee-mobile-menu-open .navmenu,
            #readbee-landing-root.readbee-mobile-menu-open #navmenu,
            #readbee-landing-root.readbee-mobile-menu-open .navbar-collapse,
            #readbee-landing-root.readbee-mobile-menu-open .readbee-mobile-menu {
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

            body.index-page.mobile-nav-active .navmenu > ul,
            body.index-page.mobile-nav-active #navmenu > ul,
            body.index-page.mobile-nav-active .navbar-collapse.show,
            body.index-page.mobile-nav-active .readbee-mobile-menu > ul,
            #readbee-landing-root.readbee-mobile-menu-open .navmenu > ul,
            #readbee-landing-root.readbee-mobile-menu-open #navmenu > ul,
            #readbee-landing-root.readbee-mobile-menu-open .navbar-collapse.show,
            #readbee-landing-root.readbee-mobile-menu-open .readbee-mobile-menu > ul {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                max-height: none !important;
                overflow: visible !important;
                background: #ffffff !important;
                color: #111827 !important;
                margin: 0 !important;
                padding: 4px !important;
                position: static !important;
                transform: none !important;
                width: 100% !important;
            }

            body.index-page.mobile-nav-active .navmenu li,
            body.index-page.mobile-nav-active #navmenu li,
            body.index-page.mobile-nav-active .readbee-mobile-menu li,
            #readbee-landing-root.readbee-mobile-menu-open .navmenu li,
            #readbee-landing-root.readbee-mobile-menu-open #navmenu li,
            #readbee-landing-root.readbee-mobile-menu-open .readbee-mobile-menu li {
                width: 100% !important;
            }

            body.index-page.mobile-nav-active .navmenu a,
            body.index-page.mobile-nav-active #navmenu a,
            body.index-page.mobile-nav-active .navbar-collapse a,
            body.index-page.mobile-nav-active .readbee-mobile-menu a,
            #readbee-landing-root.readbee-mobile-menu-open .navmenu a,
            #readbee-landing-root.readbee-mobile-menu-open #navmenu a,
            #readbee-landing-root.readbee-mobile-menu-open .navbar-collapse a,
            #readbee-landing-root.readbee-mobile-menu-open .readbee-mobile-menu a {
                color: #111827 !important;
            }

            body.index-page.mobile-nav-active .mobile-nav-toggle,
            #readbee-landing-root.readbee-mobile-menu-open .mobile-nav-toggle {
                color: #111827 !important;
            }
        }

        @media (max-width: 991px) {
            body.index-page .header,
            body.index-page #header,
            #readbee-landing-root .header,
            #readbee-landing-root #header {
                padding-top: 7px !important;
                padding-bottom: 7px !important;
            }

            body.index-page a[href*="signin"],
            #readbee-landing-root a[href*="signin"] {
                width: fit-content !important;
                margin-top: .35rem;
            }
        }
    </style>

    @vite(['resources/js/landing.js'])
</head>
<body class="index-page">
    <div id="readbee-landing-root"
        data-signin-url="{{ route('signin') }}"
        data-dashboard-url="{{ route('dashboard') }}"
        data-download-url="{{ url('/download-readbee') }}"
        data-evaluator-apk-url="{{ asset('landing-apk/readbee-evaluator-app-2025.apk') }}"
        data-pupil-apk-url="{{ asset('landing-apk/readbee-pupil-app-2025.apk') }}"
        data-assets-url="{{ asset('landing-assets') }}">
    </div>

    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center" aria-label="Back to top">
        <i class="bi bi-arrow-up-short"></i>
    </a>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const root = document.getElementById('readbee-landing-root');

            const toggleScrolledNavbar = function () {
                const isScrolled = window.scrollY > 20;
                document.body.classList.toggle('readbee-nav-scrolled', isScrolled);
                if (root) {
                    root.classList.toggle('readbee-nav-scrolled', isScrolled);
                }
            };

            const getMobileMenuTarget = function (toggle) {
                const explicitTarget = toggle.getAttribute('data-bs-target') || toggle.getAttribute('data-target');
                const ariaControls = toggle.getAttribute('aria-controls');
                const selector = explicitTarget || (ariaControls ? `#${ariaControls}` : '');

                if (selector && selector !== '#') {
                    try {
                        return document.querySelector(selector);
                    } catch (error) {
                        return null;
                    }
                }

                return document.querySelector('#navmenu, .navmenu, .navbar-collapse, .readbee-mobile-menu');
            };

            const setMobileMenuOpen = function (open, toggle) {
                document.body.classList.toggle('mobile-nav-active', open);
                if (root) {
                    root.classList.toggle('readbee-mobile-menu-open', open);
                }

                const target = toggle ? getMobileMenuTarget(toggle) : document.querySelector('#navmenu, .navmenu, .navbar-collapse, .readbee-mobile-menu');
                if (target) {
                    target.classList.toggle('show', open);
                    target.classList.toggle('active', open);
                    target.style.display = open ? 'block' : '';
                    target.style.visibility = open ? 'visible' : '';
                    target.style.opacity = open ? '1' : '';

                    if (open && window.innerWidth < 1200) {
                        const header = document.querySelector('.header, #header, #readbee-landing-root .header, #readbee-landing-root #header');
                        const headerBottom = header ? Math.ceil(header.getBoundingClientRect().bottom) : 58;
                        target.style.position = 'fixed';
                        target.style.top = `${Math.max(headerBottom + 8, 58)}px`;
                        target.style.left = '14px';
                        target.style.right = '14px';
                        target.style.zIndex = '99999';
                    } else {
                        target.style.position = '';
                        target.style.top = '';
                        target.style.left = '';
                        target.style.right = '';
                        target.style.zIndex = '';
                    }
                }

                const icon = toggle?.querySelector?.('i') || (toggle?.matches?.('i') ? toggle : document.querySelector('.mobile-nav-toggle'));
                if (icon) {
                    icon.classList.toggle('bi-list', !open);
                    icon.classList.toggle('bi-x', open);
                }

                if (toggle) {
                    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                }
            };

            document.addEventListener('click', function (event) {
                const toggle = event.target.closest('.mobile-nav-toggle, .navbar-toggler, [data-bs-toggle="collapse"], [data-readbee-mobile-toggle]');

                if (toggle) {
                    event.preventDefault();
                    event.stopPropagation();
                    const isOpen = document.body.classList.contains('mobile-nav-active') || getMobileMenuTarget(toggle)?.classList.contains('show');
                    setMobileMenuOpen(!isOpen, toggle);
                    return;
                }

                const navLink = event.target.closest('#navmenu a, .navmenu a, .navbar-collapse a, .readbee-mobile-menu a');
                if (navLink && document.body.classList.contains('mobile-nav-active')) {
                    setMobileMenuOpen(false);
                }
            });

            window.addEventListener('resize', function () {
                if (window.innerWidth >= 1200) {
                    setMobileMenuOpen(false);
                }
            });


            const removeEmbeddedDownloadSection = function () {
                document.querySelectorAll('#download, #downloads, .readbee-download-section, [data-section="download"], [data-readbee-section="download"]').forEach(function (section) {
                    section.remove();
                });

                document.querySelectorAll('section').forEach(function (section) {
                    const text = (section.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
                    const hasDownloadHeading = text.includes('download readbee app') || text.includes('download readbee');
                    const hasBothApps = text.includes('evaluator app') && text.includes('pupil app');

                    if (hasDownloadHeading && hasBothApps) {
                        section.remove();
                    }
                });
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

            const markFooterReadBeeBrand = function () {
                document.querySelectorAll('footer, .footer, #footer, #readbee-landing-root footer, #readbee-landing-root .footer').forEach(function (footer) {
                    footer.querySelectorAll('a, span, strong, h1, h2, h3, h4, h5, p').forEach(function (element) {
                        if (element.children.length === 0 && element.textContent.trim() === 'ReadBee') {
                            element.classList.add('readbee-footer-brand-yellow');
                        }
                    });
                });
            };


            const updateDownloadReadBeeLinks = function () {
                const downloadUrl = root?.dataset?.downloadUrl || '/download-readbee';
                document.querySelectorAll('a').forEach(function (link) {
                    const text = (link.textContent || '').trim().toLowerCase();
                    const href = (link.getAttribute('href') || '').trim().toLowerCase();

                    if (text.includes('download readbee') || href === '#download' || href === '#downloads' || href.includes('download-readbee')) {
                        link.setAttribute('href', downloadUrl);
                        link.classList.toggle('active', window.location.pathname.replace(/\/$/, '') === '/download-readbee');
                    }
                });
            };

            removeEmbeddedDownloadSection();
            setTimeout(removeEmbeddedDownloadSection, 150);
            setTimeout(removeEmbeddedDownloadSection, 600);

            updateDownloadReadBeeLinks();
            setTimeout(updateDownloadReadBeeLinks, 150);
            setTimeout(updateDownloadReadBeeLinks, 600);

            markFooterReadBeeBrand();
            setTimeout(markFooterReadBeeBrand, 150);
            setTimeout(markFooterReadBeeBrand, 600);

            if (root) {
                const landingObserver = new MutationObserver(function () {
                    removeEmbeddedDownloadSection();
                    markFooterReadBeeBrand();
                    updateDownloadReadBeeLinks();
                });
                landingObserver.observe(root, { childList: true, subtree: true });
            }

            toggleScrolledNavbar();
            toggleScrollTop();
            window.addEventListener('scroll', function () {
                toggleScrolledNavbar();
                toggleScrollTop();
            }, { passive: true });
        });
    </script>
</body>
</html>
