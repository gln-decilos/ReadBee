<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Assignment Confirmation' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-100 px-4 py-10">
    <div class="fixed inset-0 flex items-center justify-center bg-gray-900/60 px-4">
        <div class="w-full max-w-lg rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-2xl">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full {{ ($status ?? '') === 'confirmed' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' }}">
                @if (($status ?? '') === 'confirmed')
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                @else
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none"><path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" /></svg>
                @endif
            </div>
            <h1 class="mt-5 text-2xl font-bold text-gray-900">{{ $title ?? 'Assignment Updated' }}</h1>
            <p class="mt-3 text-gray-600">{{ $message ?? 'Your assignment confirmation has been recorded.' }}</p>
            <p class="mt-6 text-sm text-gray-400">You may now close this page.</p>
        </div>
    </div>
</body>
</html>
