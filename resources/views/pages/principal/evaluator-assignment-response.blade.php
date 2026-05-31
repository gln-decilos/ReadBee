<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Assignment Confirmation' }}</title>

    <link rel="icon" type="image/png" href="{{ asset('landing-assets/images/ReadBeefavicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('landing-assets/images/ReadBeefavicon.png') }}">

    <style>
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #fff7d6, #f3f4f6);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: #111827;
        }

        .card {
            width: 100%;
            max-width: 520px;
            background: #ffffff;
            border-radius: 22px;
            padding: 36px 30px;
            text-align: center;
            border: 1px solid #e5e7eb;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
        }

        .brand {
            color: #facc15;
            font-weight: 800;
            font-size: 15px;
            letter-spacing: .12em;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        .icon {
            width: 76px;
            height: 76px;
            margin: 0 auto;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .confirmed {
            background: #dcfce7;
            color: #16a34a;
        }

        .failed {
            background: #fee2e2;
            color: #dc2626;
        }

        h1 {
            margin: 22px 0 10px;
            font-size: 26px;
            font-weight: 800;
        }

        p {
            margin: 0;
            color: #4b5563;
            font-size: 15px;
            line-height: 1.6;
        }

        .note {
            margin-top: 24px;
            font-size: 13px;
            color: #9ca3af;
        }

        .button {
            display: inline-block;
            margin-top: 26px;
            padding: 12px 20px;
            border-radius: 999px;
            background: #facc15;
            color: #111827;
            font-weight: 700;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="brand">ReadBee</div>

        <div class="icon {{ ($status ?? '') === 'confirmed' ? 'confirmed' : 'failed' }}">
            @if (($status ?? '') === 'confirmed')
                <svg width="38" height="38" viewBox="0 0 24 24" fill="none">
                    <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            @else
                <svg width="38" height="38" viewBox="0 0 24 24" fill="none">
                    <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
            @endif
        </div>

        <h1>{{ $title ?? 'Assignment Updated' }}</h1>

        <p>{{ $message ?? 'Your assignment confirmation has been recorded.' }}</p>

        <a href="{{ url('/') }}" class="button">Back to ReadBee</a>

        <p class="note">You may now close this page.</p>
    </div>
</body>
</html>
