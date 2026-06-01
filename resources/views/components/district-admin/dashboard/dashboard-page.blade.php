@props(['dashboard' => []])

@php
    $dashboard = $dashboard ?? [];
    $stats = [
        ['label' => 'Schools Managed', 'value' => $dashboard['schools'] ?? 0, 'help' => 'Schools under this district', 'icon' => 'building'],
        ['label' => 'Municipalities', 'value' => $dashboard['municipalities'] ?? 0, 'help' => 'Municipal areas covered', 'icon' => 'map-pin'],
        ['label' => 'Assigned Users', 'value' => $dashboard['users'] ?? 0, 'help' => 'People with district access', 'icon' => 'users'],
        ['label' => 'School Years', 'value' => $dashboard['schoolYears'] ?? 0, 'help' => 'Configured academic years', 'icon' => 'calendar'],
    ];
    $recentSchools = $dashboard['recentSchools'] ?? [];

    $icon = function ($name, $class = 'h-5 w-5') {
        $icons = [
            'building' => '<svg class="'.$class.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/><path d="M9 9h1"/><path d="M9 13h1"/><path d="M9 17h1"/></svg>',
            'map-pin' => '<svg class="'.$class.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>',
            'users' => '<svg class="'.$class.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
            'calendar' => '<svg class="'.$class.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/></svg>',
        ];
        return $icons[$name] ?? $icons['building'];
    };
@endphp

<style>
    .readbee-admin-dashboard { --rb-yellow:#f2c94c; --rb-ink:#111827; --rb-muted:#667085; --rb-line:#e5e7eb; }
    .readbee-admin-dashboard .rb-hero { position:relative; overflow:hidden; border-radius:18px; background:radial-gradient(circle at 94% 0%, rgba(242,201,76,.12), transparent 34%), linear-gradient(135deg,#ffffff 0%,#fffdf7 48%,#f8fafc 100%); border:1px solid rgba(229,231,235,.95); box-shadow:0 12px 28px rgba(15,23,42,.05); }
    .readbee-admin-dashboard .rb-hero:after { content:''; position:absolute; width:210px; height:210px; border-radius:999px; right:-72px; top:-86px; background:rgba(242,201,76,.08); filter:blur(5px); }
    .readbee-admin-dashboard .rb-card { border:1px solid rgba(229,231,235,.95); border-radius:20px; background:#fff; box-shadow:0 12px 30px rgba(15,23,42,.06); transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
    .readbee-admin-dashboard .rb-card:hover { transform:translateY(-2px); box-shadow:0 18px 40px rgba(15,23,42,.09); border-color:rgba(242,201,76,.55); }
    .readbee-admin-dashboard .rb-icon { display:inline-flex; align-items:center; justify-content:center; flex:0 0 auto; width:44px; height:44px; border-radius:16px; background:rgba(242,201,76,.2); color:#1f2937; }
    .readbee-admin-dashboard .rb-school-list-logo { display:flex; align-items:center; justify-content:center; flex:0 0 auto; width:44px; height:44px; overflow:hidden; border-radius:14px; background:#f9fafb; border:1px solid #e5e7eb; color:#6b7280; }
    .readbee-admin-dashboard .rb-school-list-logo img { width:100%; height:100%; object-fit:cover; }
    .readbee-admin-dashboard .rb-action { display:flex; align-items:center; gap:.75rem; padding:1rem; border-radius:16px; border:1px solid #eef2f7; background:#f8fafc; transition:background .2s ease, border-color .2s ease; }
    .readbee-admin-dashboard .rb-action:hover { background:#fff9e6; border-color:rgba(242,201,76,.6); }
    .dark .readbee-admin-dashboard .rb-hero { background:radial-gradient(circle at 92% 0%, rgba(242,201,76,.08), transparent 34%), linear-gradient(135deg,rgba(255,255,255,.055) 0%,rgba(255,255,255,.03) 58%,rgba(148,163,184,.07) 100%); border-color:rgba(255,255,255,.09); box-shadow:none; }
    .dark .readbee-admin-dashboard .rb-card { background:rgba(17,24,39,.96); border-color:rgba(255,255,255,.08); box-shadow:none; }
    .dark .readbee-admin-dashboard .rb-action { background:rgba(255,255,255,.04); border-color:rgba(255,255,255,.08); }
    .dark .readbee-admin-dashboard .rb-action:hover { background:rgba(242,201,76,.10); border-color:rgba(242,201,76,.28); }
    .dark .readbee-admin-dashboard .rb-icon { background:rgba(242,201,76,.16); color:#f9fafb; }
    .dark .readbee-admin-dashboard .rb-school-list-logo { background:rgba(255,255,255,.08); border-color:rgba(255,255,255,.14); color:#d1d5db; }
</style>

<div class="readbee-admin-dashboard space-y-5 sm:space-y-6">
    <section class="rb-hero p-5 text-gray-900 shadow-theme-md dark:text-white sm:p-6 xl:p-7">
        <div class="relative z-10 grid gap-5 lg:grid-cols-[1fr_320px] lg:items-center">
            <div class="flex items-center gap-3">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gray-50 ring-1 ring-gray-200 dark:bg-white/[0.04] dark:ring-white/10 sm:h-16 sm:w-16">
                    <img src="{{ asset('landing-assets/images/CuteBee3.png') }}" alt="ReadBee district dashboard" class="h-11 w-11 object-contain sm:h-12 sm:w-12">
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-gray-500 dark:text-gray-400">District Admin Dashboard</p>
                    <h1 class="mt-1 text-2xl font-semibold leading-tight text-gray-950 dark:text-white sm:text-3xl">{{ $dashboard['districtName'] ?? 'Your District' }}</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-300">See schools, municipalities, users, and school year setup in one simple page. Use the quick actions below for common district tasks.</p>
                </div>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white/80 p-4 shadow-theme-xs dark:border-white/10 dark:bg-white/[0.06] sm:p-5">
                <p class="text-sm text-gray-600 dark:text-gray-200">Recommended next step</p>
                <p class="mt-2 text-base font-semibold text-gray-950 dark:text-white">Keep school records complete and updated.</p>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Start with missing school details, then check user assignments.</p>
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
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recently Added Schools</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Latest schools in your district records.</p>
                </div>
                <a href="{{ route('district-admin.schools.index') }}" class="text-sm font-semibold text-gray-900 underline decoration-yellow-400 underline-offset-4 dark:text-white">View all schools</a>
            </div>
            <div class="mt-5 overflow-hidden rounded-2xl border border-gray-100 dark:border-white/10">
                @forelse ($recentSchools as $school)
                    <div class="flex flex-col gap-3 border-b border-gray-100 p-4 last:border-b-0 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="rb-school-list-logo" aria-hidden="true">
                                @if (! empty($school['logo']))
                                    <img src="{{ $school['logo'] }}" alt="{{ $school['name'] ?? 'School' }} logo">
                                @else
                                    {!! $icon('building', 'h-5 w-5') !!}
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-gray-900 dark:text-white">{{ $school['name'] ?? 'Unnamed School' }}</p>
                                <p class="truncate text-sm text-gray-500 dark:text-gray-400">{{ $school['municipalities']['municipal_name'] ?? ($school['address'] ?? 'Municipality not set') }}</p>
                            </div>
                        </div>
                        <span class="w-fit shrink-0 rounded-full bg-yellow-50 px-3 py-1 text-xs font-semibold text-gray-700 dark:bg-yellow-500/10 dark:text-yellow-100">School record</span>
                    </div>
                @empty
                    <div class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">No school records found yet. Add schools to begin organizing the district.</div>
                @endforelse
            </div>
        </div>

        <div class="rb-card p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Quick Actions</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Common tasks, written in plain language.</p>
            <div class="mt-5 space-y-3">
                <a class="rb-action" href="{{ route('district-admin.schools.index') }}"><span class="rb-icon">{!! $icon('building') !!}</span><span><strong class="block text-gray-900 dark:text-white">Add or update a school</strong><small class="text-gray-500 dark:text-gray-400">Manage school name, address, and contacts.</small></span></a>
                <a class="rb-action" href="{{ route('district-admin.municipalities.index') }}"><span class="rb-icon">{!! $icon('map-pin') !!}</span><span><strong class="block text-gray-900 dark:text-white">Manage municipalities</strong><small class="text-gray-500 dark:text-gray-400">Keep district locations organized.</small></span></a>
                <a class="rb-action" href="{{ route('district-admin.district-admin-users') }}"><span class="rb-icon">{!! $icon('users') !!}</span><span><strong class="block text-gray-900 dark:text-white">Assign users</strong><small class="text-gray-500 dark:text-gray-400">Give the right access to staff.</small></span></a>
                <a class="rb-action" href="{{ route('district-admin.school-year.index') }}"><span class="rb-icon">{!! $icon('calendar') !!}</span><span><strong class="block text-gray-900 dark:text-white">Set school year</strong><small class="text-gray-500 dark:text-gray-400">Prepare quarters and academic dates.</small></span></a>
            </div>
        </div>
    </section>
</div>
