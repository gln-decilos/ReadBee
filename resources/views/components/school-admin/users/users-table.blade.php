@props([
    'users' => [],
    'page' => 1,
    'perPage' => 10,
])

<div x-data='{
    allUsers: @json($users),
    page: {{ (int) $page }},
    perPage: {{ (int) $perPage }},

    selectedRows: [],
    selectAll: false,
    showAddForm: false,

    confirmModal: false,
    successModal: {{ session("success") ? "true" : "false" }},

    bulkDeleteModal: false,
    deleteSuccessModal: false,
    deleteMessage: "",
    deleteMode: null,
    deleteId: null,

    roles: [],
    selectedRoleId: "",

    get total() {
        return this.allUsers.length;
    },

    get lastPage() {
        return Math.max(Math.ceil(this.total / this.perPage), 1);
    },

    get paginatedUsers() {
        const start = (this.page - 1) * this.perPage;
        return this.allUsers.slice(start, start + this.perPage);
    },

    get startItem() {
        if (this.total === 0) return 0;
        return (this.page - 1) * this.perPage + 1;
    },

    get endItem() {
        return Math.min(this.page * this.perPage, this.total);
    },

    visiblePages() {
        let start = Math.max(1, this.page - 2);
        let end = Math.min(this.lastPage, this.page + 2);

        const pages = [];
        for (let i = start; i <= end; i++) {
            pages.push(i);
        }
        return pages;
    },

    goToPage(pageNumber) {
        if (pageNumber >= 1 && pageNumber <= this.lastPage) {
            this.page = pageNumber;
            this.selectedRows = [];
            this.selectAll = false;
        }
    },

    getInitials(name) {
        if (!name) return "US";
        const parts = name.trim().split(/\\s+/).filter(Boolean);

        if (parts.length >= 2) {
            return (parts[0][0] + parts[1][0]).toUpperCase();
        }

        const first = parts[0] || "";
        return first.substring(0, 2).toUpperCase().padEnd(2, "X");
    },

    getInitialColor(identifier) {
        const colorPairs = [
            { bg: "bg-red-100 dark:bg-red-500/10", text: "text-red-600 dark:text-red-400" },
            { bg: "bg-orange-100 dark:bg-orange-500/10", text: "text-orange-600 dark:text-orange-400" },
            { bg: "bg-amber-100 dark:bg-amber-500/10", text: "text-amber-600 dark:text-amber-400" },
            { bg: "bg-yellow-100 dark:bg-yellow-500/10", text: "text-yellow-600 dark:text-yellow-400" },
            { bg: "bg-lime-100 dark:bg-lime-500/10", text: "text-lime-600 dark:text-lime-400" },
            { bg: "bg-green-100 dark:bg-green-500/10", text: "text-green-600 dark:text-green-400" },
            { bg: "bg-emerald-100 dark:bg-emerald-500/10", text: "text-emerald-600 dark:text-emerald-400" },
            { bg: "bg-teal-100 dark:bg-teal-500/10", text: "text-teal-600 dark:text-teal-400" },
            { bg: "bg-cyan-100 dark:bg-cyan-500/10", text: "text-cyan-600 dark:text-cyan-400" },
            { bg: "bg-sky-100 dark:bg-sky-500/10", text: "text-sky-600 dark:text-sky-400" },
            { bg: "bg-blue-100 dark:bg-blue-500/10", text: "text-blue-600 dark:text-blue-400" },
            { bg: "bg-indigo-100 dark:bg-indigo-500/10", text: "text-indigo-600 dark:text-indigo-400" },
            { bg: "bg-violet-100 dark:bg-violet-500/10", text: "text-violet-600 dark:text-violet-400" },
            { bg: "bg-purple-100 dark:bg-purple-500/10", text: "text-purple-600 dark:text-purple-400" },
            { bg: "bg-fuchsia-100 dark:bg-fuchsia-500/10", text: "text-fuchsia-600 dark:text-fuchsia-400" },
            { bg: "bg-pink-100 dark:bg-pink-500/10", text: "text-pink-600 dark:text-pink-400" },
            { bg: "bg-rose-100 dark:bg-rose-500/10", text: "text-rose-600 dark:text-rose-400" },
        ];

        const value = String(identifier || "user");
        let hash = 0;

        for (let i = 0; i < value.length; i++) {
            hash = value.charCodeAt(i) + ((hash << 5) - hash);
        }

        return colorPairs[Math.abs(hash) % colorPairs.length];
    },

    async loadRoles() {
        try {
            const response = await fetch("{{ route('school-admin.roles.index') }}", {
                headers: { Accept: "application/json" }
            });
            this.roles = await response.json() || [];
        } catch (error) {
            console.error("Error loading roles:", error);
            this.roles = [];
        }
    },

    handleSelectAll() {
        this.selectAll = !this.selectAll;
        this.selectedRows = this.selectAll
            ? this.paginatedUsers.map(user => String(user.id))
            : [];
    },

    handleRowSelect(id) {
        id = String(id);

        if (this.selectedRows.includes(id)) {
            this.selectedRows = this.selectedRows.filter(rowId => rowId !== id);
        } else {
            this.selectedRows.push(id);
        }

        this.selectAll =
            this.paginatedUsers.length > 0 &&
            this.selectedRows.length === this.paginatedUsers.length;
    },

    deleteRow(id) {
        this.deleteMode = "single";
        this.deleteId = String(id);
        this.bulkDeleteModal = true;
    },

    triggerBulkDelete() {
        if (this.selectedRows.length > 0) {
            this.deleteMode = "bulk";
            this.bulkDeleteModal = true;
        }
    },

    async confirmBulkDelete() {
        const idsToDelete = this.deleteMode === "single" ? [this.deleteId] : this.selectedRows;

        this.bulkDeleteModal = false;

        try {
            const response = await fetch("{{ route('school-admin.users.destroy') }}", {
                method: "DELETE",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Accept": "application/json"
                },
                body: JSON.stringify({ ids: idsToDelete })
            });

            const data = await response.json();

            if (data.success) {
                this.allUsers = this.allUsers.filter(
                    user => !idsToDelete.includes(String(user.id))
                );

                if (this.page > this.lastPage) {
                    this.page = this.lastPage;
                }

                this.selectedRows = [];
                this.selectAll = false;
                this.deleteMessage = data.message || "Users deleted successfully";
                this.deleteSuccessModal = true;

                this.deleteMode = null;
                this.deleteId = null;
            } else {
                alert(data.message || "Failed to delete users.");
            }
        } catch (error) {
            console.error("Delete error:", error);
            alert("An error occurred while deleting users.");
        }
    },

    submitForm() {
        this.confirmModal = false;
        this.$refs.createForm.submit();
    },

    formatDate(dateString) {
        if (!dateString) return "N/A";
        const date = new Date(dateString);
        return date.toLocaleDateString("en-US", {
            year: "numeric",
            month: "short",
            day: "numeric"
        });
    }
}' x-init="loadRoles()">

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white pt-4 dark:border-white/[0.05] dark:bg-white/[0.03]">
        <div class="mb-4 flex flex-col gap-4 px-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                    User Accounts
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Manage principal and teacher accounts for your school.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('school-admin.users.import.index') }}">
                    <x-ui.button variant="outline">
                        Import CSV
                    </x-ui.button>
                </a>

                <template x-if="selectedRows.length > 0">
                    <x-ui.button
                        variant="outline"
                        className="text-red-600 ring-red-300 hover:bg-red-50 dark:text-red-400 dark:ring-red-500/30 dark:hover:bg-red-500/10"
                        @click="triggerBulkDelete()"
                    >
                        Delete Selected
                        (<span x-text="selectedRows.length"></span>)
                    </x-ui.button>
                </template>

                <template x-if="selectedRows.length === 0">
                    <button
                        type="button"
                        @click="showAddForm = !showAddForm"
                        class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600"
                    >
                        <span x-text="showAddForm ? 'Close Form' : 'Add User'"></span>
                    </button>
                </template>
            </div>
        </div>

        <div x-show="showAddForm" x-transition class="mb-6 px-6">
            <form
                x-ref="createForm"
                method="POST"
                action="{{ route('school-admin.users.store') }}"
                @submit.prevent="confirmModal = true"
            >
                @csrf

                <x-common.component-card title="Add New User">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Full Name
                        </label>
                        <input
                            type="text"
                            name="full_name"
                            value="{{ old('full_name') }}"
                            placeholder="John Doe"
                            required
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                        />
                        @error('full_name')
                            <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Email
                        </label>
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="info@gmail.com"
                            required
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                        />
                        @error('email')
                            <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Role
                        </label>
                        <select
                            name="role_id"
                            x-model="selectedRoleId"
                            required
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        >
                            <option value="">Select role</option>
                            <template x-for="role in roles" :key="role.id">
                                <option :value="role.id" x-text="role.name"></option>
                            </template>
                        </select>
                        @error('role_id')
                            <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </x-common.component-card>

                <div class="mt-4 flex justify-end">
                    <x-ui.button type="submit" variant="outline">
                        Create User
                    </x-ui.button>
                </div>
            </form>
        </div>

        <div x-show="!showAddForm" x-transition class="max-w-full overflow-x-auto px-6 pb-6">
            <table class="w-full">
                <thead class="border-t border-y bg-gray-50 dark:border-white/[0.05] dark:bg-gray-900">
                    <tr>
                        <th class="w-12 px-6 py-3 text-start">
                            <div
                                @click="handleSelectAll()"
                                class="flex h-5 w-5 cursor-pointer items-center justify-center rounded-md border-[1.25px] transition-colors duration-200"
                                :class="selectAll
                                    ? 'border-brand-500 bg-brand-500'
                                    : 'border-gray-300 bg-transparent hover:border-brand-500 dark:border-gray-700 dark:hover:border-brand-500'"
                            >
                                <svg x-show="selectAll" width="14" height="14" viewBox="0 0 14 14">
                                    <path d="M11.6668 3.5L5.25016 9.91667L2.3335 7"
                                          stroke="white"
                                          stroke-width="2"
                                          stroke-linecap="round"
                                          stroke-linejoin="round"/>
                                </svg>
                            </div>
                        </th>

                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400">Full Name</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400">Email</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400">Role</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400">School</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400">Assigned Date</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <template x-for="user in paginatedUsers" :key="user.id">
                        <tr class="border-b border-gray-100 dark:border-white/[0.05]">
                            <td class="px-6 py-4">
                                <div
                                    @click="handleRowSelect(String(user.id))"
                                    class="relative flex h-5 w-5 cursor-pointer items-center justify-center rounded-md border-[1.25px] transition-colors duration-200"
                                    :class="selectedRows.includes(String(user.id))
                                        ? 'border-brand-500 bg-brand-500'
                                        : 'border-gray-300 bg-transparent hover:border-brand-500 dark:border-gray-700 dark:hover:border-brand-500'"
                                >
                                    <span :class="selectedRows.includes(String(user.id)) ? '' : 'opacity-0'" class="flex items-center justify-center">
                                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                                            <path d="M11.6666 3.5L5.24992 9.91667L2.33325 7"
                                                  stroke="white"
                                                  stroke-width="1.94437"
                                                  stroke-linecap="round"
                                                  stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-full text-sm font-medium"
                                        :class="[getInitialColor(user.email || user.id).bg, getInitialColor(user.email || user.id).text]"
                                        x-text="getInitials(user.full_name)"
                                    ></div>
                                    <span class="block text-sm font-medium text-gray-700 dark:text-gray-400" x-text="user.full_name || 'N/A'"></span>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-400" x-text="user.email || '-'"></td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-400" x-text="user.role || '-'"></td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-400" x-text="user.school_name || '-'"></td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-400" x-text="formatDate(user.assigned_at)"></td>
                            <td class="px-6 py-4">
                                <button
                                    @click="deleteRow(String(user.id))"
                                    class="text-gray-700 hover:text-red-500 dark:text-gray-400 dark:hover:text-red-400"
                                    title="Delete User"
                                >
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round"
                                              stroke-linejoin="round"
                                              stroke-width="2"
                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </template>

                    <template x-if="paginatedUsers.length === 0">
                        <tr>
                            <td colspan="7" class="px-6 py-12">
                                <div class="flex flex-col items-center justify-center rounded-3xl border border-gray-200 bg-white px-6 py-12 text-center shadow-sm dark:border-white/[0.05] dark:bg-white/[0.03]">
                                    <div class="mb-5 flex h-20 w-20 items-center justify-center rounded-2xl bg-brand-50 ring-1 ring-brand-100 dark:bg-brand-500/10 dark:ring-brand-500/20">
                                        <svg class="h-10 w-10 text-brand-500" viewBox="0 0 24 24" fill="none">
                                            <path d="M16 21V19C16 17.3431 14.6569 16 13 16H7C5.34315 16 4 17.3431 4 19V21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M10 13C11.6569 13 13 11.6569 13 10C13 8.34315 11 7C8.34315 7 7 8.34315 7 10C7 11.6569 8.34315 13 10 13Z" stroke="currentColor" stroke-width="1.5"/>
                                            <path d="M20 21V19C20 17.5994 19.0368 16.423 17.7373 16.0996" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                            <path d="M14.7373 7.09961C16.0368 7.42296 17 8.59935 17 10C17 11.4007 16.0368 12.577 14.7373 12.9004" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                        </svg>
                                    </div>

                                    <div class="max-w-md">
                                        <h4 class="text-lg font-semibold text-gray-800 dark:text-white">
                                            No users found
                                        </h4>
                                        <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">
                                            There are no user records to display right now. Add a user or import a CSV file to start building your user list.
                                        </p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div x-show="!showAddForm" x-transition class="flex items-center justify-between px-6 pb-6">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Showing
                <span class="font-medium" x-text="startItem"></span>
                to
                <span class="font-medium" x-text="endItem"></span>
                of
                <span class="font-medium" x-text="total"></span>
                users
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="goToPage(page - 1)"
                    :disabled="page <= 1"
                    class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 disabled:pointer-events-none disabled:border-gray-200 disabled:text-gray-400 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800 dark:disabled:border-gray-800 dark:disabled:text-gray-600"
                >
                    Previous
                </button>

                <template x-for="pageNumber in visiblePages()" :key="pageNumber">
                    <button
                        type="button"
                        @click="goToPage(pageNumber)"
                        class="inline-flex min-w-[40px] items-center justify-center rounded-lg border px-3 py-2 text-sm"
                        :class="pageNumber === page
                            ? 'border-brand-500 bg-brand-500 text-white'
                            : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800'"
                        x-text="pageNumber"
                    ></button>
                </template>

                <button
                    type="button"
                    @click="goToPage(page + 1)"
                    :disabled="page >= lastPage"
                    class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 disabled:pointer-events-none disabled:border-gray-200 disabled:text-gray-400 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800 dark:disabled:border-gray-800 dark:disabled:text-gray-600"
                >
                    Next
                </button>
            </div>
        </div>
    </div>

    <div x-show="confirmModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 dark:bg-gray-900">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                Confirm User Creation
            </h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Are you sure you want to create this user?
            </p>
            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button variant="outline" @click="confirmModal = false">Cancel</x-ui.button>
                <x-ui.button variant="primary" @click="submitForm()">Yes, Create</x-ui.button>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div x-show="successModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 text-center dark:bg-gray-900">
                <div class="mb-4 flex justify-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-green-100 dark:bg-green-500/10">
                        <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                    Success
                </h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ session('success') }}
                </p>
                <div class="mt-6">
                    <x-ui.button variant="primary" @click="successModal = false">Close</x-ui.button>
                </div>
            </div>
        </div>
    @endif

    <div x-show="bulkDeleteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 dark:bg-gray-900">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                Confirm Delete
            </h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                <template x-if="deleteMode === 'single'">
                    <span>Are you sure you want to delete this user?</span>
                </template>
                <template x-if="deleteMode !== 'single'">
                    <span>Are you sure you want to delete <strong x-text="selectedRows.length"></strong> selected user(s)?</span>
                </template>
            </p>
            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button variant="outline" @click="bulkDeleteModal = false">Cancel</x-ui.button>
                <x-ui.button variant="primary" @click="confirmBulkDelete()">Yes, Delete</x-ui.button>
            </div>
        </div>
    </div>

    <div x-show="deleteSuccessModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 text-center dark:bg-gray-900">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                Deleted Successfully
            </h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400" x-text="deleteMessage"></p>
            <div class="mt-6">
                <x-ui.button variant="primary" @click="deleteSuccessModal = false">Close</x-ui.button>
            </div>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
