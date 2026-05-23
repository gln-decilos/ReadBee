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
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

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
    </style>

    @vite(['resources/js/landing.js'])
</head>
<body class="index-page">
    <div id="readbee-landing-root"
        data-signin-url="{{ route('signin') }}"
        data-dashboard-url="{{ route('dashboard') }}"
        data-evaluator-apk-url="{{ asset('landing-apk/readbee-evaluator-app-2025.apk') }}"
        data-pupil-apk-url="{{ asset('landing-apk/readbee-pupil-app-2025.apk') }}"
        data-assets-url="{{ asset('landing-assets') }}">
    </div>
</body>
</html>
