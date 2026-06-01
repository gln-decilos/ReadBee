@props(['dashboard' => []])

@php
    $dashboard = $dashboard ?? [];
    $stats = [
        ['label' => 'School Users', 'value' => $dashboard['users'] ?? 0, 'help' => 'Staff with assigned access', 'icon' => 'users'],
        ['label' => 'Active Classes', 'value' => $dashboard['classes'] ?? 0, 'help' => 'Sections ready for use', 'icon' => 'tag'],
        ['label' => 'Grade Levels', 'value' => $dashboard['gradeLevels'] ?? 0, 'help' => 'Grades set for this school', 'icon' => 'book'],
        ['label' => 'Pupils', 'value' => $dashboard['students'] ?? 0, 'help' => 'Enrolled learners in school records', 'icon' => 'student'],
    ];
    $recentSections = $dashboard['recentSections'] ?? [];
    $schoolLogo = $dashboard['schoolLogo'] ?? null;
    $schoolName = $dashboard['schoolName'] ?? 'your school';

    $icon = function ($name, $class = 'h-5 w-5') {
        $icons = [
            'users' => '<svg class="'.$class.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
            'tag' => '<svg class="'.$class.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41 11 3.83A2 2 0 0 0 9.59 3H4a1 1 0 0 0-1 1v5.59A2 2 0 0 0 3.59 11l9.59 9.59a2 2 0 0 0 2.82 0L20.59 16a2 2 0 0 0 0-2.59Z"/><path d="M7 7h.01"/></svg>',
            'book' => '<svg class="'.$class.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/></svg>',
            'student' => '<svg class="'.$class.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/><path d="M22 10v6"/></svg>',
            'upload' => '<svg class="'.$class.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/></svg>',
        ];
        return $icons[$name] ?? $icons['users'];
    };
@endphp

<style>
    .readbee-admin-dashboard { --rb-yellow:#f2c94c; --rb-ink:#111827; --rb-muted:#667085; --rb-line:#e5e7eb; }
    .readbee-admin-dashboard .rb-hero { position:relative; overflow:hidden; border-radius:18px; background:radial-gradient(circle at 94% 0%, rgba(242,201,76,.12), transparent 34%), linear-gradient(135deg,#ffffff 0%,#fffdf7 48%,#f8fafc 100%); border:1px solid rgba(229,231,235,.95); box-shadow:0 12px 28px rgba(15,23,42,.05); }
    .readbee-admin-dashboard .rb-hero:after { content:''; position:absolute; width:210px; height:210px; border-radius:999px; right:-72px; top:-86px; background:rgba(242,201,76,.08); filter:blur(5px); }
    .readbee-admin-dashboard .rb-card { border:1px solid rgba(229,231,235,.95); border-radius:20px; background:#fff; box-shadow:0 12px 30px rgba(15,23,42,.06); transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
    .readbee-admin-dashboard .rb-card:hover { transform:translateY(-2px); box-shadow:0 18px 40px rgba(15,23,42,.09); border-color:rgba(242,201,76,.55); }
    .readbee-admin-dashboard .rb-icon { display:inline-flex; align-items:center; justify-content:center; flex:0 0 auto; width:44px; height:44px; border-radius:16px; background:rgba(242,201,76,.2); color:#1f2937; }
    .readbee-admin-dashboard .rb-school-logo { display:flex; align-items:center; justify-content:center; width:56px; height:56px; flex:0 0 auto; overflow:hidden; border-radius:16px; background:#f9fafb; border:1px solid #e5e7eb; box-shadow:none; }
    .readbee-admin-dashboard .rb-school-logo img { width:100%; height:100%; object-fit:cover; }
    @media (min-width:640px){ .readbee-admin-dashboard .rb-school-logo { width:64px; height:64px; border-radius:18px; } }
    .readbee-admin-dashboard .rb-action { display:flex; align-items:center; gap:.75rem; padding:1rem; border-radius:16px; border:1px solid #eef2f7; background:#f8fafc; transition:background .2s ease, border-color .2s ease; }
    .readbee-admin-dashboard .rb-action:hover { background:#fff9e6; border-color:rgba(242,201,76,.6); }
    .dark .readbee-admin-dashboard .rb-hero { background:radial-gradient(circle at 92% 0%, rgba(242,201,76,.08), transparent 34%), linear-gradient(135deg,rgba(255,255,255,.055) 0%,rgba(255,255,255,.03) 58%,rgba(148,163,184,.07) 100%); border-color:rgba(255,255,255,.09); box-shadow:none; }
    .dark .readbee-admin-dashboard .rb-card { background:rgba(17,24,39,.96); border-color:rgba(255,255,255,.08); box-shadow:none; }
    .dark .readbee-admin-dashboard .rb-action { background:rgba(255,255,255,.04); border-color:rgba(255,255,255,.08); }
    .dark .readbee-admin-dashboard .rb-action:hover { background:rgba(242,201,76,.10); border-color:rgba(242,201,76,.28); }
    .dark .readbee-admin-dashboard .rb-icon { background:rgba(242,201,76,.16); color:#f9fafb; }
    .dark .readbee-admin-dashboard .rb-school-logo { background:rgba(255,255,255,.08); border-color:rgba(255,255,255,.14); box-shadow:none; }
</style>

<div class="readbee-admin-dashboard space-y-5 sm:space-y-6">
    <section class="rb-hero p-5 text-gray-900 shadow-theme-md dark:text-white sm:p-6 xl:p-7">
        <div class="relative z-10 grid gap-5 lg:grid-cols-[1fr_320px] lg:items-center">
            <div class="flex items-center gap-3">
                <div class="rb-school-logo" aria-label="School logo">
                    @if ($schoolLogo)
                        <img src="{{ $schoolLogo }}" alt="{{ $schoolName }} logo">
                    @else
                        {!! $icon('book', 'h-9 w-9 text-yellow-700 dark:text-yellow-100') !!}
                    @endif
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-gray-500 dark:text-gray-400">School Admin Dashboard</p>
                    <h1 class="mt-1 text-2xl font-semibold leading-tight text-gray-950 dark:text-white sm:text-3xl">{{ $schoolName }}</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-300">See users, classes, grade levels, and student records in one simple page. The layout is designed for quick checking, not technical analysis.</p>
                </div>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white/80 p-4 shadow-theme-xs dark:border-white/10 dark:bg-white/[0.06] sm:p-5">
                <p class="text-sm text-gray-600 dark:text-gray-200">Recommended next step</p>
                <p class="mt-2 text-base font-semibold text-gray-950 dark:text-white">Confirm users and class sections before encoding learner data.</p>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">This helps teachers and evaluators access the right records.</p>
            </div>
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $stat)
            <div class="rb-card p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                        <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format((int) $stat['value']) }}</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $stat['help'] }}</p>
                    </div>
                    <span class="rb-icon">{!! $icon($stat['icon']) !!}</span>
                </div>
            </div>
        @endforeach
    </section>

    <section class="grid gap-6 xl:grid-cols-[1fr_380px]">
        <div class="rb-card p-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recently Created Classes</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">A quick view of the newest sections in your school.</p>
                </div>
                <a href="{{ route('school-admin.classes.index') }}" class="text-sm font-semibold text-gray-900 underline decoration-yellow-400 underline-offset-4 dark:text-white">View all classes</a>
            </div>
            <div class="mt-5 overflow-hidden rounded-2xl border border-gray-100 dark:border-white/10">
                @forelse ($recentSections as $section)
                    <div class="flex flex-col gap-2 border-b border-gray-100 p-4 last:border-b-0 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $section['section_name'] ?? 'Unnamed Section' }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Grade {{ $section['grade_levels']['grade_number'] ?? 'not set' }} • Adviser: {{ $section['adviser_name'] ?? 'Not assigned' }}</p>
                        </div>
                        <span class="rounded-full bg-yellow-50 px-3 py-1 text-xs font-semibold text-gray-700 dark:bg-yellow-500/10 dark:text-yellow-100">{{ ucfirst($section['status'] ?? 'active') }}</span>
                    </div>
                @empty
                    <div class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">No classes found yet. Create classes so teachers can manage learners properly.</div>
                @endforelse
            </div>
        </div>

        <div class="rb-card p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Quick Actions</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Most-used tasks for school setup.</p>
            <div class="mt-5 space-y-3">
                <a class="rb-action" href="{{ route('school-admin.users.index') }}"><span class="rb-icon">{!! $icon('users') !!}</span><span><strong class="block text-gray-900 dark:text-white">Add or update users</strong><small class="text-gray-500 dark:text-gray-400">Manage teachers, evaluators, and staff.</small></span></a>
                <a class="rb-action" href="{{ route('school-admin.users.import.index') }}"><span class="rb-icon">{!! $icon('upload') !!}</span><span><strong class="block text-gray-900 dark:text-white">Import users</strong><small class="text-gray-500 dark:text-gray-400">Upload many users using a file.</small></span></a>
                <a class="rb-action" href="{{ route('school-admin.classes.index') }}"><span class="rb-icon">{!! $icon('tag') !!}</span><span><strong class="block text-gray-900 dark:text-white">Manage classes</strong><small class="text-gray-500 dark:text-gray-400">Create sections and assign advisers.</small></span></a>
            </div>
        </div>
    </section>
</div>
