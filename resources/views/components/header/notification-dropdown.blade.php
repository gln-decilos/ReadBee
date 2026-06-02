{{-- Notification Dropdown Component --}}
<div class="relative" x-data="{
    dropdownOpen: false,
    loading: false,
    notifications: @js($notifications ?? []),
    unreadCount: @js($unreadCount ?? 0),
    fallbackUrl: @js($fallbackUrl ?? '#'),
    feedUrl: @js($feedUrl ?? null),
    readAllUrl: @js($readAllUrl ?? null),
    csrfToken: @js(csrf_token()),

    get notifying() {
        return this.unreadCount > 0;
    },

    init() {
        this.loadNotifications();
    },

    toggleDropdown() {
        this.dropdownOpen = !this.dropdownOpen;
    },

    closeDropdown() {
        this.dropdownOpen = false;
    },

    async loadNotifications() {
        if (!this.feedUrl) {
            return;
        }

        this.loading = true;

        try {
            const response = await fetch(this.feedUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            this.notifications = Array.isArray(data.notifications) ? data.notifications : [];
            this.unreadCount = Number(data.unread_count || 0);
            this.fallbackUrl = data.fallback_url || this.fallbackUrl || '#';
        } catch (error) {
            console.warn('Notifications could not be loaded.', error);
        } finally {
            this.loading = false;
        }
    },

    async openNotification(notification) {
        const redirectTo = notification.link || this.fallbackUrl || '#';
        this.closeDropdown();

        if (!notification.read_url) {
            if (redirectTo !== '#') {
                window.location.href = redirectTo;
            }
            return;
        }

        try {
            await fetch(notification.read_url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ redirect_to: redirectTo })
            });

            notification.is_read = true;
            this.unreadCount = this.notifications.filter((item) => !item.is_read).length;
        } catch (error) {
            console.warn('Notification could not be marked as read.', error);
        }

        if (redirectTo !== '#') {
            window.location.href = redirectTo;
        }
    },

    async markAllAsRead() {
        this.closeDropdown();

        if (!this.readAllUrl) {
            return;
        }

        try {
            await fetch(this.readAllUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            this.notifications = this.notifications.map((item) => ({ ...item, is_read: true }));
            this.unreadCount = 0;
        } catch (error) {
            console.warn('Notifications could not be marked as read.', error);
        }
    },

    viewAll() {
        this.closeDropdown();

        if (this.fallbackUrl && this.fallbackUrl !== '#') {
            window.location.href = this.fallbackUrl;
        }
    }
}" @click.away="closeDropdown()">
    <!-- Notification Button -->
    <button
        class="relative flex items-center justify-center text-gray-500 transition-colors bg-white border border-gray-200 rounded-full hover:text-dark-900 h-11 w-11 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
        @click="toggleDropdown()"
        type="button"
        aria-label="Open notifications"
    >
        <!-- Notification Badge -->
        <span
            x-show="unreadCount > 0"
            x-cloak
            class="absolute right-0 top-0.5 z-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-orange-400 px-1 text-[10px] font-semibold leading-none text-white"
        >
            <span x-text="unreadCount > 9 ? '9+' : unreadCount"></span>
            <span
                class="absolute inline-flex w-full h-full bg-orange-400 rounded-full opacity-75 -z-1 animate-ping"
            ></span>
        </span>

        <!-- Bell Icon -->
        <svg
            class="fill-current"
            width="20"
            height="20"
            viewBox="0 0 20 20"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path
                fill-rule="evenodd"
                clip-rule="evenodd"
                d="M10.75 2.29248C10.75 1.87827 10.4143 1.54248 10 1.54248C9.58583 1.54248 9.25004 1.87827 9.25004 2.29248V2.83613C6.08266 3.20733 3.62504 5.9004 3.62504 9.16748V14.4591H3.33337C2.91916 14.4591 2.58337 14.7949 2.58337 15.2091C2.58337 15.6234 2.91916 15.9591 3.33337 15.9591H4.37504H15.625H16.6667C17.0809 15.9591 17.4167 15.6234 17.4167 15.2091C17.4167 14.7949 17.0809 14.4591 16.6667 14.4591H16.375V9.16748C16.375 5.9004 13.9174 3.20733 10.75 2.83613V2.29248ZM14.875 14.4591V9.16748C14.875 6.47509 12.6924 4.29248 10 4.29248C7.30765 4.29248 5.12504 6.47509 5.12504 9.16748V14.4591H14.875ZM8.00004 17.7085C8.00004 18.1228 8.33583 18.4585 8.75004 18.4585H11.25C11.6643 18.4585 12 18.1228 12 17.7085C12 17.2943 11.6643 16.9585 11.25 16.9585H8.75004C8.33583 16.9585 8.00004 17.2943 8.00004 17.7085Z"
                fill=""
            />
        </svg>
    </button>

    <!-- Dropdown Start -->
    <div
        x-show="dropdownOpen"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute -right-[240px] mt-[17px] flex h-[480px] w-[350px] flex-col rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark sm:w-[361px] lg:right-0"
        style="display: none;"
    >
        <!-- Dropdown Header -->
        <div class="flex items-center justify-between pb-3 mb-3 border-b border-gray-100 dark:border-gray-800">
            <div>
                <h5 class="text-lg font-semibold text-gray-800 dark:text-white/90">Notification</h5>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    <span x-text="unreadCount"></span>
                    unread
                    <span x-text="unreadCount === 1 ? 'notification' : 'notifications'"></span>
                </p>
            </div>

            <button @click="closeDropdown()" class="text-gray-500 dark:text-gray-400" type="button" aria-label="Close notifications">
                <svg
                    class="fill-current"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        fill-rule="evenodd"
                        clip-rule="evenodd"
                        d="M6.21967 7.28131C5.92678 6.98841 5.92678 6.51354 6.21967 6.22065C6.51256 5.92775 6.98744 5.92775 7.28033 6.22065L11.999 10.9393L16.7176 6.22078C17.0105 5.92789 17.4854 5.92788 17.7782 6.22078C18.0711 6.51367 18.0711 6.98855 17.7782 7.28144L13.0597 12L17.7782 16.7186C18.0711 17.0115 18.0711 17.4863 17.7782 17.7792C17.4854 18.0721 17.0105 18.0721 16.7176 17.7792L11.999 13.0607L7.28033 17.7794C6.98744 18.0722 6.51256 18.0722 6.21967 17.7794C5.92678 17.4865 5.92678 17.0116 6.21967 16.7187L10.9384 12L6.21967 7.28131Z"
                        fill=""
                    />
                </svg>
            </button>
        </div>

        <!-- Notification List -->
        <ul class="flex flex-col h-auto overflow-y-auto custom-scrollbar">
            <li x-show="loading" x-cloak class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                Loading notifications...
            </li>

            <template x-for="notification in notifications" :key="notification.notification_id || notification.created_at">
                <li>
                    <button
                        type="button"
                        class="flex w-full gap-3 rounded-lg border-b border-gray-100 p-3 px-4.5 py-3 text-left transition hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-white/5"
                        :class="notification.is_read ? '' : 'bg-orange-50/70 dark:bg-orange-500/10'"
                        @click="openNotification(notification)"
                    >
                        <span
                            class="relative flex h-10 w-full max-w-10 items-center justify-center rounded-full z-1"
                            :class="notification.is_read ? 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400' : 'bg-orange-100 text-orange-600 dark:bg-orange-500/15 dark:text-orange-300'"
                        >
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 22C13.1046 22 14 21.1046 14 20H10C10 21.1046 10.8954 22 12 22Z" fill="currentColor"/>
                                <path d="M18 16V11C18 7.68629 15.3137 5 12 5C8.68629 5 6 7.68629 6 11V16L4 18V19H20V18L18 16Z" fill="currentColor"/>
                                <path d="M13 3C13 2.44772 12.5523 2 12 2C11.4477 2 11 2.44772 11 3V4H13V3Z" fill="currentColor"/>
                            </svg>

                            <span
                                x-show="!notification.is_read"
                                class="absolute bottom-0 right-0 z-10 h-2.5 w-full max-w-2.5 rounded-full border-[1.5px] border-white bg-orange-400 dark:border-gray-900"
                            ></span>
                        </span>

                        <span class="block min-w-0 flex-1">
                            <span class="mb-1 block text-sm font-semibold text-gray-800 dark:text-white/90" x-text="notification.title"></span>
                            <span class="mb-1.5 block line-clamp-2 text-theme-sm text-gray-500 dark:text-gray-400" x-text="notification.message"></span>

                            <span class="flex items-center gap-2 text-gray-500 text-theme-xs dark:text-gray-400">
                                <span x-text="notification.type_label"></span>
                                <span class="w-1 h-1 bg-gray-400 rounded-full"></span>
                                <span x-text="notification.time_ago"></span>
                            </span>
                        </span>
                    </button>
                </li>
            </template>

            <li x-show="!loading && notifications.length === 0" class="flex flex-col items-center justify-center px-5 py-12 text-center">
                <span class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 22C13.1046 22 14 21.1046 14 20H10C10 21.1046 10.8954 22 12 22Z" fill="currentColor"/>
                        <path d="M18 16V11C18 7.68629 15.3137 5 12 5C8.68629 5 6 7.68629 6 11V16L4 18V19H20V18L18 16Z" fill="currentColor"/>
                        <path d="M13 3C13 2.44772 12.5523 2 12 2C11.4477 2 11 2.44772 11 3V4H13V3Z" fill="currentColor"/>
                    </svg>
                </span>
                <p class="text-sm font-medium text-gray-800 dark:text-white/90">No notifications yet</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">New evaluator assignments and updates will appear here.</p>
            </li>
        </ul>

        <!-- View All Button -->
        <button
            x-show="unreadCount > 0"
            x-cloak
            type="button"
            class="mt-3 flex w-full justify-center rounded-lg border border-gray-300 bg-white p-3 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200"
            @click="markAllAsRead()"
        >
            Mark all as read
        </button>

        <button
            x-show="unreadCount === 0"
            type="button"
            class="mt-3 flex w-full justify-center rounded-lg border border-gray-300 bg-white p-3 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200"
            @click="viewAll()"
        >
            View All Notification
        </button>
    </div>
    <!-- Dropdown End -->
</div>
