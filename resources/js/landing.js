const { createElement: h, useEffect, useMemo, useState } = window.React;
const { createRoot } = window.ReactDOM;

function asset(baseUrl, path) {
    return `${baseUrl}/${path}`;
}

function FeatureCard({ icon, title, text }) {
    return h('div', { className: 'd-flex align-items-start mb-3 p-3 bg-light shadow-sm rounded readbee-feature-card' },
        h('div', { className: 'me-3 flex-shrink-0' }, h('i', { className: `${icon} fs-2`, style: { color: '#FFC300' } })),
        h('div', null, h('h5', { className: 'mb-1' }, title), h('p', { className: 'mb-0' }, text))
    );
}

function Header({ signinUrl }) {
    const [open, setOpen] = useState(false);
    const closeMenu = () => setOpen(false);

    useEffect(() => {
        const onScroll = () => {
            const header = document.getElementById('header');
            if (!header) return;
            window.scrollY > 100 ? header.classList.add('scrolled') : header.classList.remove('scrolled');
        };
        onScroll();
        window.addEventListener('scroll', onScroll);
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    return h('header', { id: 'header', className: 'header d-flex align-items-center fixed-top' },
        h('div', { className: 'container-fluid container-xl position-relative d-flex align-items-center justify-content-between' },
            h('a', { href: '#hero', className: 'logo d-flex align-items-center', onClick: closeMenu },
                h('img', { src: asset(window.readBeeLanding.assetsUrl, 'images/ReadBee-Logo-Light.png'), alt: 'ReadBee Logo Dark', className: 'logo-dark' }),
                h('img', { src: asset(window.readBeeLanding.assetsUrl, 'images/ReadBee-Logo-Dark.png'), alt: 'ReadBee Logo Light', className: 'logo-light' })
            ),
            h('nav', { id: 'navmenu', className: `navmenu ${open ? 'mobile-nav-active' : ''}` },
                h('ul', null,
                    h('li', null, h('a', { href: '#hero', className: 'active', onClick: closeMenu }, 'Home')),
                    h('li', null, h('a', { href: '#about', onClick: closeMenu }, 'About')),
                    h('li', null, h('a', { href: '#features', onClick: closeMenu }, 'Features')),
                    h('li', null, h('a', { href: '#team', onClick: closeMenu }, 'Team')),
                    h('li', null, h('a', { href: '#contact', onClick: closeMenu }, 'Contact')),
                    h('li', null, h('a', { href: '#download', onClick: closeMenu }, 'Download ReadBee')),
                    h('li', null, h('a', { href: signinUrl, className: 'btn readbee-login-btn' }, 'Login'))
                ),
                h('i', { className: `mobile-nav-toggle d-xl-none bi ${open ? 'bi-x' : 'bi-list'}`, onClick: () => setOpen(!open), role: 'button', 'aria-label': 'Toggle navigation' })
            )
        )
    );
}

function Hero({ evaluatorApkUrl }) {
    return h('section', { id: 'hero', className: 'hero section dark-background' },
        h('img', { src: asset(window.readBeeLanding.assetsUrl, 'images/hero-bg-2.jpg'), alt: '', className: 'hero-bg' }),
        h('div', { className: 'container' },
            h('div', { className: 'row gy-4 justify-content-between' },
                h('div', { className: 'col-lg-5 order-lg-last hero-img d-flex align-items-center' },
                    h('div', { className: 'me-2' }, h('img', { src: asset(window.readBeeLanding.assetsUrl, 'images/CuteBee.png'), className: 'img-fluid animated', alt: 'ReadBee mascot' })),
                    h('div', null, h('img', { src: asset(window.readBeeLanding.assetsUrl, 'images/FiloChild.png'), style: { width: '350px' }, className: 'img-fluid', alt: 'Learner using ReadBee' }))
                ),
                h('div', { className: 'col-lg-6 d-flex flex-column justify-content-center' },
                    h('h1', null, 'Read, Track, Improve. ', h('span', null, 'Just Like a Busy Bee')),
                    h('p', null, 'ReadBee streamlines reading assessments with real-time scoring, progress tracking, and smart insights — empowering educators to better support each learner.'),
                    h('div', { className: 'd-flex flex-wrap gap-2' },
                        h('a', { href: evaluatorApkUrl, className: 'btn-get-started' }, 'Download App'),
                        h('a', { href: '#features', className: 'btn-watch-video d-flex align-items-center' }, h('i', { className: 'bi bi-play-circle' }), h('span', null, 'Watch Video'))
                    )
                )
            )
        ),
        h('svg', { className: 'hero-waves', xmlns: 'http://www.w3.org/2000/svg', viewBox: '0 24 150 28', preserveAspectRatio: 'none' },
            h('defs', null, h('path', { id: 'wave-path', d: 'M-160 44c30 0 58-18 88-18s 58 18 88 18 58-18 88-18 58 18 88 18 v44h-352z' })),
            h('g', { className: 'wave1' }, h('use', { href: '#wave-path', x: '50', y: '3' })),
            h('g', { className: 'wave2' }, h('use', { href: '#wave-path', x: '50', y: '0' })),
            h('g', { className: 'wave3' }, h('use', { href: '#wave-path', x: '50', y: '9' }))
        )
    );
}

function About() {
    return h('section', { id: 'about', className: 'about section' },
        h('div', { className: 'container' },
            h('div', { className: 'row align-items-xl-center gx-5 gy-5' },
                h('div', { className: 'col-lg-6 order-lg-first hero-img d-flex align-items-center' },
                    h('img', { src: asset(window.readBeeLanding.assetsUrl, 'images/ReadBee-App.png'), className: 'img-fluid', alt: 'ReadBee app preview' })
                ),
                h('div', { className: 'col-lg-6 content gy-5 icon-boxes' },
                    h('h3', null, 'About ReadBee'),
                    h('h2', null, 'Empowering Digital Reading Assessment'),
                    h('p', null, 'ReadBee is a voice-based reading and scoring platform designed for elementary learners. It simplifies oral reading assessments by using voice recognition and smart analytics, helping teachers track reading progress, identify gaps, and support students with data-driven insights — anytime, anywhere.'),
                    h('a', { href: '#features', className: 'read-more' }, h('span', null, 'Read More'), h('i', { className: 'bi bi-arrow-right' }))
                )
            )
        )
    );
}

function Features() {
    const left = [
        ['fa-solid fa-microphone', 'Voice Recognition', 'Automatically detects and processes oral reading for real-time scoring.'],
        ['fa-solid fa-pen-to-square', 'Digital Scoring', 'Instantly evaluates reading performance and tracks scores digitally.'],
        ['fa-solid fa-book-open-reader', 'Reading Materials', 'Provides accessible reading content tailored for different grade levels.'],
    ];
    const right = [
        ['fa-solid fa-chart-line', 'Analytics Dashboard', 'Visualizes progress, trends, and performance metrics over time.'],
        ['fa-solid fa-file-alt', 'Report Generation', 'Generates detailed reports to track student progress and performance.'],
        ['fa-solid fa-user-gear', 'Account Management', 'Manages user roles, access, and system settings with ease.'],
    ];

    return h('section', { id: 'features', className: 'features section py-5' },
        h('div', { className: 'container' },
            h('div', { className: 'row g-4 align-items-stretch' },
                h('div', { className: 'col-md-4 d-flex flex-column justify-content-between' }, left.map(([icon, title, text]) => h(FeatureCard, { key: title, icon, title, text }))),
                h('div', { className: 'col-md-4 d-flex align-items-center justify-content-center' },
                    h('img', { src: asset(window.readBeeLanding.assetsUrl, 'images/ReadBee-Phone.png'), alt: 'ReadBee mobile app', className: 'img-fluid', style: { maxHeight: '400px' } })
                ),
                h('div', { className: 'col-md-4 d-flex flex-column justify-content-between' }, right.map(([icon, title, text]) => h(FeatureCard, { key: title, icon, title, text })))
            )
        )
    );
}


function TeamCard({ image, name, role, code }) {
    return h('div', { className: 'col-xl-3 col-lg-4 col-sm-6 d-flex' },
        h('article', { className: 'readbee-id-card w-100' },
            h('div', { className: 'readbee-id-card-top' },
                h('span', { className: 'readbee-id-card-label' }, 'ReadBee'),
                h('span', { className: 'readbee-id-card-code' }, code)
            ),
            h('div', { className: 'readbee-id-photo-wrap' },
                h('img', { src: asset(window.readBeeLanding.assetsUrl, image), alt: `${name} portrait`, className: 'readbee-id-photo' })
            ),
            h('div', { className: 'readbee-id-card-body' },
                h('h3', null, name),
                h('p', null, role)
            )
        )
    );
}

function Team() {
    const members = [
        ['images/team1.png', 'Dr. Noelyn M. De Jesus', 'Capstone Adviser', 'ADVISER'],
        ['images/team2.png', 'Glenmor A. Decilos', 'Web App Developer / Business Analyst', 'Web'],
        ['images/team4.png', 'Cindy V. Certeza', 'Quality Assurance Specialist', 'QA'],
        ['images/team3.png', 'Carl Justine B. Butiong', 'Mobile App Developer', 'MOBILE'],

    ];

    return h('section', { id: 'team', className: 'team section readbee-team-section py-5' },
        h('div', { className: 'container section-title' },
            h('h2', null, 'Our Team'),
            h('div', null, h('span', null, 'Meet the people behind '), h('span', { className: 'description-title' }, 'ReadBee'))
        ),
        h('div', { className: 'container' },
            h('div', { className: 'row gy-4 justify-content-center' },
                members.map(([image, name, role, code]) => h(TeamCard, { key: name, image, name, role, code }))
            )
        )
    );
}

function Download({ evaluatorApkUrl, pupilApkUrl }) {
    return h('section', { id: 'download', className: 'about section readbee-download-section' },
        h('div', { className: 'container text-center' },
            h('div', { className: 'section-title' }, h('h2', null, 'Download'), h('div', null, h('span', null, 'Get the '), h('span', { className: 'description-title' }, 'ReadBee Apps'))),
            h('img', { src: asset(window.readBeeLanding.assetsUrl, 'images/CuteBee3.png'), alt: 'ReadBee mascot', className: 'mb-3', style: { width: '90px' } }),
            h('p', { className: 'lead mx-auto mb-4', style: { maxWidth: '760px' } }, 'Empower reading assessment with the ReadBee Evaluator App and help pupils practice anytime with the Offline Pupil App.'),
            h('div', { className: 'd-flex justify-content-center gap-3 flex-wrap' },
                h('a', { href: evaluatorApkUrl, className: 'btn btn-warning text-dark fw-semibold px-4 py-2 readbee-pill-btn' }, h('i', { className: 'fa-solid fa-download me-2' }), 'Evaluator App'),
                h('a', { href: pupilApkUrl, className: 'btn btn-outline-dark fw-semibold px-4 py-2 readbee-pill-btn' }, h('i', { className: 'fa-solid fa-download me-2' }), 'Pupil App')
            )
        )
    );
}

function Contact() {
    const [sent, setSent] = useState(false);

    return h('section', { id: 'contact', className: 'contact section' },
        h('div', { className: 'container section-title' }, h('h2', null, 'Contact'), h('div', null, h('span', null, 'Check Our '), h('span', { className: 'description-title' }, 'Contact'))),
        h('div', { className: 'container' },
            h('div', { className: 'row gy-4' },
                h('div', { className: 'col-lg-4' },
                    h('div', { className: 'info-item d-flex' }, h('i', { className: 'bi bi-geo-alt flex-shrink-0' }), h('div', null, h('h3', null, 'Address'), h('p', null, 'Nasugbu, Batangas 4321'))),
                    h('div', { className: 'info-item d-flex' }, h('i', { className: 'bi bi-telephone flex-shrink-0' }), h('div', null, h('h3', null, 'Call Us'), h('p', null, '+63 930492039'))),
                    h('div', { className: 'info-item d-flex' }, h('i', { className: 'bi bi-envelope flex-shrink-0' }), h('div', null, h('h3', null, 'Email Us'), h('p', null, 'readbee@gmail.com')))
                ),
                h('div', { className: 'col-lg-8' },
                    h('form', { className: 'php-email-form', onSubmit: (event) => { event.preventDefault(); setSent(true); } },
                        h('div', { className: 'row gy-4' },
                            h('div', { className: 'col-md-6' }, h('input', { type: 'text', name: 'name', className: 'form-control', placeholder: 'Your Name', required: true })),
                            h('div', { className: 'col-md-6' }, h('input', { type: 'email', name: 'email', className: 'form-control', placeholder: 'Your Email', required: true })),
                            h('div', { className: 'col-md-12' }, h('input', { type: 'text', name: 'subject', className: 'form-control', placeholder: 'Subject', required: true })),
                            h('div', { className: 'col-md-12' }, h('textarea', { name: 'message', rows: '6', className: 'form-control', placeholder: 'Message', required: true })),
                            h('div', { className: 'col-md-12 text-center' },
                                sent && h('div', { className: 'sent-message d-block mb-3' }, 'Your message has been prepared. Please connect this form to your Laravel mail endpoint to send it.'),
                                h('button', { type: 'submit' }, 'Send Message')
                            )
                        )
                    )
                )
            )
        )
    );
}

function Footer() {
    const year = new Date().getFullYear();
    return h('footer', { id: 'footer', className: 'footer dark-background' },
        h('div', { className: 'container footer-top' },
            h('div', { className: 'row gy-4' },
                h('div', { className: 'col-lg-4 col-md-6 footer-about' },
                    h('a', { href: '#hero', className: 'logo d-flex align-items-center' }, h('img', { src: asset(window.readBeeLanding.assetsUrl, 'images/ReadBee-Logo-Dark.png'), alt: 'ReadBee logo' })),
                    h('div', { className: 'social-links d-flex mt-4' },
                        h('a', { href: '#' }, h('i', { className: 'bi bi-twitter-x' })),
                        h('a', { href: '#' }, h('i', { className: 'bi bi-facebook' })),
                        h('a', { href: '#' }, h('i', { className: 'bi bi-instagram' })),
                        h('a', { href: '#' }, h('i', { className: 'bi bi-linkedin' }))
                    )
                ),
                h('div', { className: 'col-lg-2 col-md-3 footer-links' }, h('h4', null, 'Useful Links'), h('ul', null,
                    h('li', null, h('a', { href: '#hero' }, 'Home')),
                    h('li', null, h('a', { href: '#about' }, 'About')),
                    h('li', null, h('a', { href: '#features' }, 'Features')),
                    h('li', null, h('a', { href: '#team' }, 'Team')),
                    h('li', null, h('a', { href: '#contact' }, 'Contact'))
                )),
                h('div', { className: 'col-lg-4 col-md-12 footer-newsletter' }, h('p', null, 'ReadBee is dedicated to transforming reading assessment through innovative technology solutions that empower educators and engage students in their literacy journey.'))
            )
        ),
        h('div', { className: 'container copyright text-center mt-4' },
            h('p', null, '© ', h('span', null, `Copyright ${year}`), h('strong', { className: 'px-1 sitename' }, 'ReadBee.'), h('span', null, ' Batangas State University TNEU ARASOF-Nasugbu. All Rights Reserved'))
        )
    );
}

function LandingPage() {
    const cfg = window.readBeeLanding;
    const [loaded, setLoaded] = useState(false);

    useEffect(() => {
        setLoaded(true);
    }, []);

    return h(window.React.Fragment, null,
        h(Header, { signinUrl: cfg.signinUrl }),
        h('main', { className: 'main' },
            h(Hero, { evaluatorApkUrl: cfg.evaluatorApkUrl }),
            h(About),
            h(Features),
            h(Team),
            h(Download, { evaluatorApkUrl: cfg.evaluatorApkUrl, pupilApkUrl: cfg.pupilApkUrl }),
            h(Contact)
        ),
        h(Footer),
        h('a', { href: '#', id: 'scroll-top', className: 'scroll-top d-flex align-items-center justify-content-center' }, h('i', { className: 'bi bi-arrow-up-short' })),
        !loaded && h('div', { id: 'preloader' }, h('img', { src: asset(cfg.assetsUrl, 'images/LoadingBee.gif'), alt: 'Loading...' }))
    );
}

const rootElement = document.getElementById('readbee-landing-root');
if (rootElement) {
    window.readBeeLanding = {
        signinUrl: rootElement.dataset.signinUrl,
        dashboardUrl: rootElement.dataset.dashboardUrl,
        evaluatorApkUrl: rootElement.dataset.evaluatorApkUrl,
        pupilApkUrl: rootElement.dataset.pupilApkUrl,
        assetsUrl: rootElement.dataset.assetsUrl,
    };

    createRoot(rootElement).render(h(LandingPage));
}
