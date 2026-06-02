<?php

namespace App\View\Components\header;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;

class NotificationDropdown extends Component
{
    public array $notifications = [];

    public int $unreadCount = 0;

    public string $fallbackUrl = '#';

    public ?string $feedUrl = null;

    public ?string $readAllUrl = null;

    /**
     * Create a new component instance.
     *
     * Important for Render/production:
     * Do not call Supabase or any external service while rendering the header.
     * This component appears on every authenticated page, so notification loading
     * is done after page load through a small JSON route instead.
     */
    public function __construct()
    {
        $this->fallbackUrl = $this->defaultNotificationUrl();
        $this->feedUrl = Route::has('notifications.feed') ? route('notifications.feed', [], false) : null;
        $this->readAllUrl = Route::has('notifications.read-all') ? route('notifications.read-all', [], false) : null;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.header.notification-dropdown');
    }

    private function defaultNotificationUrl(): string
    {
        $roleName = strtolower((string) (session('active_designation.role_name') ?? ''));

        if (str_contains($roleName, 'evaluator') || str_contains($roleName, 'teacher')) {
            return $this->routeIfExists('evaluator.assignments');
        }

        if (str_contains($roleName, 'principal')) {
            return $this->routeIfExists('principal.dashboard');
        }

        if (str_contains($roleName, 'school')) {
            return $this->routeIfExists('school-admin.dashboard');
        }

        if (str_contains($roleName, 'district supervisor')) {
            return $this->routeIfExists('district-supervisor.dashboard');
        }

        if (str_contains($roleName, 'district')) {
            return $this->routeIfExists('district-admin.district-admin-dashboard');
        }

        return '#';
    }

    private function routeIfExists(string $name): string
    {
        return Route::has($name) ? route($name, [], false) : '#';
    }
}
