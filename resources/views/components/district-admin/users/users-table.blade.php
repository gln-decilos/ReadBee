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

    showDesignationModal: false,
    currentUser: null,
    userDesignations: [],
    loadingDesignations: false,

    designationMessage: "",
    designationMessageType: "",

    designationDeleteModal: false,
    designationDeleteSuccessModal: false,
    designationToDelete: null,
    designationIndex: null,

    showAddDesignationForm: false,
    roles: [],
    scopes: [],
    loadingScopes: false,

    showDistrict: false,
    showMunicipality: false,
    showSchool: false,
    districts: [],
    municipalities: [],
    schools: [],
    newDesignation: {
        role_id: "",
        scope_id: "",
        district_id: "",
        municipal_id: "",
        school_id: ""
    },

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

        const parts = name.trim().split(/\s+/).filter(Boolean);

        if (parts.length >= 2) {
            return (parts[0][0] + parts[1][0]).toUpperCase();
        }

        const first = parts[0] || "";
        return first.substring(0, 2).toUpperCase().padEnd(2, "X");
    },

    getInitialColor(identifier) {
        const colorPairs = [
            { bg: "bg-red-100", text: "text-red-600" },
            { bg: "bg-orange-100", text: "text-orange-600" },
            { bg: "bg-amber-100", text: "text-amber-600" },
            { bg: "bg-yellow-100", text: "text-yellow-600" },
            { bg: "bg-lime-100", text: "text-lime-600" },
            { bg: "bg-green-100", text: "text-green-600" },
            { bg: "bg-emerald-100", text: "text-emerald-600" },
            { bg: "bg-teal-100", text: "text-teal-600" },
            { bg: "bg-cyan-100", text: "text-cyan-600" },
            { bg: "bg-sky-100", text: "text-sky-600" },
            { bg: "bg-blue-100", text: "text-blue-600" },
            { bg: "bg-indigo-100", text: "text-indigo-600" },
            { bg: "bg-violet-100", text: "text-violet-600" },
            { bg: "bg-purple-100", text: "text-purple-600" },
            { bg: "bg-fuchsia-100", text: "text-fuchsia-600" },
            { bg: "bg-pink-100", text: "text-pink-600" },
            { bg: "bg-rose-100", text: "text-rose-600" },
        ];

        const value = String(identifier || "user");
        let hash = 0;

        for (let i = 0; i < value.length; i++) {
            hash = value.charCodeAt(i) + ((hash << 5) - hash);
        }

        const index = Math.abs(hash) % colorPairs.length;
        return colorPairs[index];
    },

    resetScopeSelection() {
        this.showDistrict = false;
        this.showMunicipality = false;
        this.showSchool = false;

        this.districts = [];
        this.municipalities = [];
        this.schools = [];

        this.newDesignation.district_id = "";
        this.newDesignation.municipal_id = "";
        this.newDesignation.school_id = "";
    },

    handleScopeChange() {
        this.resetScopeSelection();

        const selectedScope = this.scopes.find(
            s => String(s.id) === String(this.newDesignation.scope_id)
        );

        if (!selectedScope) return;

        if (selectedScope.scope_type === "district") {
            this.showDistrict = true;
            this.loadDistricts();
        }

        if (selectedScope.scope_type === "school") {
            this.showDistrict = true;
            this.showMunicipality = true;
            this.showSchool = true;
            this.loadDistricts();
        }
    },

    async loadDistricts() {
        try {
            const response = await fetch("/district-admin/districts", {
                headers: { Accept: "application/json" }
            });
            this.districts = await response.json() || [];
        } catch (error) {
            console.error("Error loading districts:", error);
            this.districts = [];
        }
    },

    async loadMunicipalities() {
        this.newDesignation.municipal_id = "";
        this.newDesignation.school_id = "";
        this.municipalities = [];
        this.schools = [];

        if (!this.newDesignation.district_id) return;

        try {
            const response = await fetch(
                `/district-admin/municipalities?district_id=${this.newDesignation.district_id}`,
                { headers: { Accept: "application/json" } }
            );

            this.municipalities = await response.json() || [];
        } catch (error) {
            console.error("Error loading municipalities:", error);
            this.municipalities = [];
        }
    },

    async loadSchools() {
        this.newDesignation.school_id = "";
        this.schools = [];

        if (!this.newDesignation.municipal_id) return;

        try {
            const response = await fetch(
                `/district-admin/schools?municipality_id=${this.newDesignation.municipal_id}`,
                { headers: { Accept: "application/json" } }
            );

            this.schools = await response.json() || [];
        } catch (error) {
            console.error("Error loading schools:", error);
            this.schools = [];
        }
    },

    async loadScopesByRole() {
        this.scopes = [];
        this.newDesignation.scope_id = "";
        this.resetScopeSelection();

        if (!this.newDesignation.role_id) return;

        this.loadingScopes = true;

        try {
            const response = await fetch(`/district-admin/scopes?role_id=${this.newDesignation.role_id}`, {
                headers: { Accept: "application/json" }
            });
            this.scopes = await response.json() || [];
        } catch (error) {
            console.error("Error loading scopes:", error);
            this.scopes = [];
        } finally {
            this.loadingScopes = false;
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
            const response = await fetch("{{ route('district-admin.users.destroy') }}", {
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

    async viewDesignations(userId, userName, userEmail) {
        this.loadingDesignations = true;
        this.showDesignationModal = true;
        this.showAddDesignationForm = false;

        this.scopes = [];
        this.newDesignation.role_id = "";
        this.newDesignation.scope_id = "";

        try {
            const [designationRes, rolesRes] = await Promise.all([
                fetch(`/district-admin/users/${userId}/designations`, { headers: { "Accept": "application/json" } }),
                fetch(`/district-admin/roles`, { headers: { "Accept": "application/json" } })
            ]);

            const data = await designationRes.json();
            this.roles = await rolesRes.json();

            if (data.user) {
                this.currentUser = data.user;
                this.userDesignations = data.designations || [];
            } else {
                this.currentUser = { id: userId, full_name: userName, email: userEmail };
                this.userDesignations = [];
            }
        } catch (error) {
            console.error("Error loading designations:", error);
            this.roles = [];
            this.scopes = [];
        } finally {
            this.loadingDesignations = false;
        }
    },

    async addDesignation() {
        if (!this.newDesignation.role_id || !this.newDesignation.scope_id || !this.currentUser) {
            alert("Please select role and scope.");
            return;
        }

        const selectedScope = this.scopes.find(
            s => String(s.id) === String(this.newDesignation.scope_id)
        );

        if (selectedScope?.scope_type === "district" && !this.newDesignation.district_id) {
            alert("Please select district.");
            return;
        }

        if (selectedScope?.scope_type === "school") {
            if (!this.newDesignation.district_id) {
                alert("Please select district.");
                return;
            }
            if (!this.newDesignation.municipal_id) {
                alert("Please select municipality.");
                return;
            }
            if (!this.newDesignation.school_id) {
                alert("Please select school.");
                return;
            }
        }

        const payload = {
            user_id: this.currentUser.id,
            role_id: this.newDesignation.role_id,
            scope_id: this.newDesignation.scope_id,
            district_id: this.newDesignation.district_id || null,
            municipal_id: this.newDesignation.municipal_id || null,
            school_id: this.newDesignation.school_id || null
        };

        try {
            const response = await fetch("/district-admin/designations", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Accept": "application/json"
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (data.success) {
                const roleObj = this.roles.find(r => String(r.id) === String(this.newDesignation.role_id));
                const scopeObj = this.scopes.find(s => String(s.id) === String(this.newDesignation.scope_id));

                this.userDesignations.push({
                    user_role_id: data.designation.user_role_id,
                    role: roleObj ? roleObj.name : "Unknown Role",
                    scope: scopeObj ? scopeObj.name : "Unknown Scope",
                    scope_description: scopeObj ? scopeObj.description || "" : "",
                    district_id: data.designation.district_id,
                    municipal_id: data.designation.municipal_id,
                    school_id: data.designation.school_id,
                    assigned_at: data.designation.assigned_at
                });

                this.newDesignation = {
                    role_id: "",
                    scope_id: "",
                    district_id: "",
                    municipal_id: "",
                    school_id: ""
                };

                this.resetScopeSelection();
                this.scopes = [];
                this.showAddDesignationForm = false;
            } else {
                alert(data.message || "Failed to add designation.");
                console.error(data);
            }
        } catch (error) {
            console.error("Add designation error:", error);
        }
    },

    confirmRemoveDesignation(designation, index) {
        this.designationToDelete = designation;
        this.designationIndex = index;
        this.designationDeleteModal = true;
    },

    async removeDesignation(designation, index) {
        try {
            const params = new URLSearchParams({ user_role_id: designation.user_role_id });
            const response = await fetch(`/district-admin/designations?${params.toString()}`, {
                method: "DELETE",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Accept": "application/json"
                }
            });

            const data = await response.json();

            if (data.success) {
                this.userDesignations.splice(index, 1);
                this.designationMessage = "Designation removed successfully.";
                this.designationMessageType = "success";
            } else {
                this.designationMessage = data.message || "Failed to remove designation.";
                this.designationMessageType = "error";
            }
        } catch (error) {
            console.error("Error removing designation:", error);
            this.designationMessage = "An error occurred while removing the designation.";
            this.designationMessageType = "error";
        }
    },

    proceedRemoveDesignation() {
        if (!this.designationToDelete || this.designationIndex === null) return;

        this.removeDesignation(this.designationToDelete, this.designationIndex);
        this.designationDeleteModal = false;
        this.designationToDelete = null;
        this.designationIndex = null;
        this.designationDeleteSuccessModal = true;
    },

    closeDesignationModal() {
        this.showDesignationModal = false;
        this.currentUser = null;
        this.userDesignations = [];
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
}'>
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white pt-4 dark:border-white/[0.05] dark:bg-white/[0.03]">

        <div class="flex flex-col gap-4 px-6 mb-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                    User Accounts
                </h3>
            </div>

            <div class="flex items-center gap-3">
                <template x-if="selectedRows.length > 0">
                    <x-ui.button
                        variant="outline"
                        className="text-red-600 ring-red-300 hover:bg-red-50"
                        @click="triggerBulkDelete()"
                    >
                        Delete Selected
                        (<span x-text="selectedRows.length"></span>)
                    </x-ui.button>
                </template>

                <template x-if="selectedRows.length === 0">
                    <x-ui.button
                        variant="primary"
                        size="md"
                        @click="showAddForm = !showAddForm"
                    >
                        <span x-text="showAddForm ? 'Close Form' : 'Add User'"></span>
                    </x-ui.button>
                </template>
            </div>
        </div>

        <div x-show="showAddForm" x-transition class="px-6 mb-6">
            <form x-ref="createForm" method="POST" action="{{ route('district-admin.users.store') }}" enctype="multipart/form-data" @submit.prevent="confirmModal = true">
                @csrf
                <x-common.component-card title="Add New User">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Full Name
                        </label>
                        <input type="text" name="full_name" placeholder="John Doe" required
                               class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Email
                        </label>
                        <input type="email" name="email" placeholder="info@gmail.com" required
                               class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
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
                        <th class="px-6 py-3 text-start w-12">
                            <div class="flex items-center gap-3">
                                <div @click="handleSelectAll()"
                                     class="flex h-5 w-5 cursor-pointer items-center justify-center rounded-md border-[1.25px] transition-colors duration-200"
                                     :class="selectAll
                                        ? 'border-brand-500 bg-brand-500'
                                        : 'border-gray-300 bg-transparent hover:border-brand-500 dark:border-gray-700 dark:hover:border-brand-500'">
                                    <svg x-show="selectAll" width="14" height="14" viewBox="0 0 14 14">
                                        <path d="M11.6668 3.5L5.25016 9.91667L2.3335 7"
                                              stroke="white"
                                              stroke-width="2"
                                              stroke-linecap="round"
                                              stroke-linejoin="round"/>
                                    </svg>
                                </div>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 text-start dark:text-gray-400">Full Name</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 text-start dark:text-gray-400">Email</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 text-start dark:text-gray-400">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <template x-for="user in paginatedUsers" :key="user.id">
                        <tr class="border-b border-gray-100 dark:border-white/[0.05]" :data-id="user.id">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div @click="handleRowSelect(String(user.id))"
                                         class="relative flex h-5 w-5 cursor-pointer items-center justify-center rounded-md border-[1.25px] transition-colors duration-200"
                                         :class="selectedRows.includes(String(user.id))
                                            ? 'border-brand-500 bg-brand-500'
                                            : 'border-gray-300 bg-transparent hover:border-brand-500 dark:border-gray-700 dark:hover:border-brand-500'">
                                        <span :class="selectedRows.includes(String(user.id)) ? '' : 'opacity-0'" class="flex items-center justify-center">
                                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M11.6666 3.5L5.24992 9.91667L2.33325 7" stroke="white" stroke-width="1.94437" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex items-center justify-center w-10 h-10 rounded-full font-medium text-sm"
                                        :class="[getInitialColor(user.email || user.id).bg, getInitialColor(user.email || user.id).text]"
                                        x-text="getInitials(user.full_name)"
                                    ></div>
                                    <div>
                                        <span class="block text-sm font-medium text-gray-700 dark:text-gray-400" x-text="user.full_name || 'N/A'"></span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-400" x-text="user.email || '-'"></td>

                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <button @click="viewDesignations(user.id, user.full_name, user.email)"
                                            class="text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                                            title="View Designations">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l5 5a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-5-5A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15 7h.01"/>
                                        </svg>
                                    </button>

                                    <button @click="deleteRow(String(user.id))"
                                            class="text-gray-700 hover:text-red-500 dark:text-gray-400 dark:hover:text-red-400"
                                            title="Delete User">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round"
                                                  stroke-linejoin="round"
                                                  stroke-width="2"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-if="paginatedUsers.length === 0">
                      <tr>
                          <td colspan="4" class="px-6 py-12">
                              <div class="flex flex-col items-center justify-center rounded-3xl border border-gray-200 bg-white px-6 py-12 text-center shadow-sm dark:border-white/[0.05] dark:bg-white/[0.03]">
                                  <div class="mb-5 flex h-20 w-20 items-center justify-center rounded-2xl bg-brand-50 ring-1 ring-brand-100 dark:bg-brand-500/10 dark:ring-brand-500/20">
                                      <svg class="h-10 w-10 text-brand-500" viewBox="0 0 24 24" fill="none">
                                          <path d="M16 21V19C16 17.3431 14.6569 16 13 16H7C5.34315 16 4 17.3431 4 19V21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                          <path d="M10 13C11.6569 13 13 11.6569 13 10C13 8.34315 11.6569 7 10 7C8.34315 7 7 8.34315 7 10C7 11.6569 8.34315 13 10 13Z" stroke="currentColor" stroke-width="1.5"/>
                                          <path d="M20 21V19C20 17.5994 19.0368 16.423 17.7373 16.0996" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                          <path d="M14.7373 7.09961C16.0368 7.42296 17 8.59935 17 10C17 11.4007 16.0368 12.577 14.7373 12.9004" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                      </svg>
                                  </div>

                                  <div class="max-w-md">
                                      <h4 class="text-lg font-semibold text-gray-800 dark:text-white">
                                          No users found
                                      </h4>

                                      <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">
                                          There are no user records to display right now. Add a user to start building your user list.
                                      </p>
                                  </div>

                                  <div class="mt-6 flex items-center gap-2 rounded-full bg-gray-50 px-4 py-2 text-xs font-medium text-gray-500 dark:bg-white/[0.04] dark:text-gray-400">
                                      <svg class="h-4 w-4 text-brand-500" viewBox="0 0 24 24" fill="none">
                                          <path d="M16 21V19C16 17.3431 14.6569 16 13 16H7C5.34315 16 4 17.3431 4 19V21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                          <path d="M10 13C11.6569 13 13 11.6569 13 10C13 8.34315 11.6569 7 10 7C8.34315 7 7 8.34315 7 10C7 11.6569 8.34315 13 10 13Z" stroke="currentColor" stroke-width="1.5"/>
                                          <path d="M20 21V19C20 17.5994 19.0368 16.423 17.7373 16.0996" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                          <path d="M14.7373 7.09961C16.0368 7.42296 17 8.59935 17 10C17 11.4007 16.0368 12.577 14.7373 12.9004" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                      </svg>
                                      Add your first user to get started
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
                    class="inline-flex items-center rounded-lg border px-3 py-2 text-sm disabled:pointer-events-none disabled:border-gray-200 disabled:text-gray-400 dark:disabled:border-gray-800 dark:disabled:text-gray-600 border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800"
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
                    class="inline-flex items-center rounded-lg border px-3 py-2 text-sm disabled:pointer-events-none disabled:border-gray-200 disabled:text-gray-400 dark:disabled:border-gray-800 dark:disabled:text-gray-600 border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800"
                >
                    Next
                </button>
            </div>
        </div>
    </div>

    <form x-ref="deleteForm"
          method="POST"
          action="{{ route('district-admin.users.destroy') }}"
          style="display: none;">
        @csrf
        @method('DELETE')
        <template x-for="id in selectedRows" :key="id">
            <input type="hidden" name="ids[]" :value="id">
        </template>
    </form>

    <div x-show="confirmModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                Confirm User Creation
            </h3>
            <p class="mt-2 text-sm text-gray-500">
                Are you sure you want to create this user?
            </p>
            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button variant="outline" @click="confirmModal = false">
                    Cancel
                </x-ui.button>
                <x-ui.button variant="primary" @click="submitForm()">
                    Yes, Create
                </x-ui.button>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div x-show="successModal"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 w-full max-w-md text-center">
                <div class="flex justify-center mb-4">
                    <div class="flex items-center justify-center w-14 h-14 rounded-full bg-green-100">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                    User Created Successfully
                </h3>
                <p class="mt-2 text-sm text-gray-500">
                    {{ session('success') }}
                </p>
                <div class="mt-6">
                    <x-ui.button variant="primary" @click="successModal = false">
                        Close
                    </x-ui.button>
                </div>
            </div>
        </div>
    @endif

    <div x-show="bulkDeleteModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                Confirm Delete
            </h3>
            <p class="mt-2 text-sm text-gray-500">
                <template x-if="deleteMode === 'single'">
                    <span>Are you sure you want to delete this user?</span>
                </template>
                <template x-if="deleteMode !== 'single'">
                    <span>
                        Are you sure you want to delete
                        <strong x-text="selectedRows.length"></strong>
                        selected user(s)?
                    </span>
                </template>
            </p>
            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button variant="outline" @click="bulkDeleteModal = false">
                    Cancel
                </x-ui.button>
                <x-ui.button variant="primary" @click="confirmBulkDelete()">
                    Yes, Delete
                </x-ui.button>
            </div>
        </div>
    </div>

    <div x-show="deleteSuccessModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 w-full max-w-md text-center">
            <div class="flex justify-center mb-4">
                <div class="flex items-center justify-center w-14 h-14 rounded-full bg-green-100">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                Deleted Successfully
            </h3>
            <p class="mt-2 text-sm text-gray-500" x-text="deleteMessage"></p>
            <div class="mt-6">
                <x-ui.button variant="primary" @click="deleteSuccessModal = false">
                    Close
                </x-ui.button>
            </div>
        </div>
    </div>

    <div x-show="showDesignationModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
         @click.away="closeDesignationModal()">

        <div class="bg-white dark:bg-gray-900 rounded-2xl w-full max-w-3xl max-h-[80vh] overflow-hidden">

            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3>User Designations</h3>
                    <div class="flex gap-2">
                        <x-ui.button variant="primary" size="sm" @click="showAddDesignationForm = !showAddDesignationForm">
                            Add Designation
                        </x-ui.button>
                        <button @click="closeDesignationModal()">✕</button>
                    </div>
                </div>

                <template x-if="currentUser">
                    <div class="mt-4 flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div
                            class="flex items-center justify-center w-12 h-12 rounded-full font-medium text-lg"
                            :class="[getInitialColor(currentUser?.email || currentUser?.id).bg, getInitialColor(currentUser?.email || currentUser?.id).text]"
                        >
                            <span x-text="getInitials(currentUser?.full_name)"></span>
                        </div>
                        <div>
                            <h4 class="text-base font-semibold text-gray-800 dark:text-white" x-text="currentUser.full_name || 'Unknown'"></h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="currentUser.email || 'No email'"></p>
                        </div>
                    </div>
                </template>
            </div>

            <div class="p-6 overflow-y-auto" style="max-height: calc(80vh - 180px);">
                <div x-show="showAddDesignationForm" class="mb-4 p-4 border rounded-lg bg-gray-50 dark:bg-gray-800">
                    <h4 class="text-sm font-semibold mb-3 text-gray-800 dark:text-white">Add Designation</h4>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1" for="role-select">
                                Role
                            </label>

                            <select
                                id="role-select"
                                x-model="newDesignation.role_id"
                                @change="loadScopesByRole(); resetScopeSelection()"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 px-3 py-2 text-sm"
                            >
                                <option value="">Select Role</option>

                                <template x-for="role in roles" :key="role.id">
                                    <option :value="role.id" x-text="role.name"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1" for="scope-select">
                                Scope
                            </label>

                            <select
                                id="scope-select"
                                x-model="newDesignation.scope_id"
                                @change="handleScopeChange()"
                                :disabled="loadingScopes || !newDesignation.role_id"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 px-3 py-2 text-sm"
                            >
                                <option value="">Select Scope</option>

                                <template x-if="loadingScopes">
                                    <option disabled>Loading scopes...</option>
                                </template>

                                <template x-if="!loadingScopes && scopes.length === 0 && newDesignation.role_id">
                                    <option disabled>No scopes available for this role</option>
                                </template>

                                <template x-for="scope in scopes" :key="scope.id">
                                    <option :value="scope.id" x-text="scope.name"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 mt-4">
                        <div x-show="showDistrict" x-transition>
                            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1" for="district-select">
                                District
                            </label>
                            <select
                                id="district-select"
                                x-model="newDesignation.district_id"
                                @change="showMunicipality ? loadMunicipalities() : null"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 px-3 py-2 text-sm"
                            >
                                <option value="">Select District</option>
                                <template x-for="district in districts" :key="district.district_id">
                                    <option :value="district.district_id" x-text="district.district_name"></option>
                                </template>
                            </select>
                        </div>

                        <div x-show="showMunicipality" x-transition>
                            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1" for="municipality-select">
                                Municipality
                            </label>
                            <select
                                id="municipality-select"
                                x-model="newDesignation.municipal_id"
                                @change="showSchool ? loadSchools() : null"
                                :disabled="!newDesignation.district_id"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 px-3 py-2 text-sm"
                            >
                                <option value="">Select Municipality</option>
                                <template x-for="muni in municipalities" :key="muni.municipality_id">
                                    <option :value="muni.municipality_id" x-text="muni.municipal_name"></option>
                                </template>
                            </select>
                        </div>

                        <div x-show="showSchool" x-transition>
                            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1" for="school-select">
                                School
                            </label>
                            <select
                                id="school-select"
                                x-model="newDesignation.school_id"
                                :disabled="!newDesignation.municipal_id"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 px-3 py-2 text-sm"
                            >
                                <option value="">Select School</option>
                                <template x-for="school in schools" :key="school.school_id">
                                    <option :value="school.school_id" x-text="school.name"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div class="mt-3 flex justify-end gap-2">
                        <x-ui.button variant="outline" @click="showAddDesignationForm = false">
                            Cancel
                        </x-ui.button>
                        <x-ui.button variant="primary" @click="addDesignation()">
                            Save
                        </x-ui.button>
                    </div>
                </div>

                <div x-show="loadingDesignations" class="flex justify-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-brand-500"></div>
                </div>

                <div x-show="!loadingDesignations">
                    <template x-if="userDesignations.length === 0">
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No designations found</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                This user hasn't been assigned any roles yet.
                            </p>
                        </div>
                    </template>

                    <template x-if="userDesignations.length > 0">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Scope</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Assigned To</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Assigned Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <template x-for="(designation, index) in userDesignations" :key="index">
                                    <tr>
                                      <td class="px-4 py-3">
                                          <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="designation.role"></div>
                                      </td>

                                      <td class="px-4 py-3">
                                          <div class="text-sm text-gray-900 dark:text-white" x-text="designation.scope"></div>
                                          <div class="text-xs text-gray-500 dark:text-gray-400" x-text="designation.scope_description"></div>
                                      </td>

                                      <td class="px-4 py-3">
                                          <div class="text-sm text-gray-900 dark:text-white" x-text="designation.assigned_to || '-'"></div>
                                          <template x-if="designation.scope_type === 'school'">
                                              <div class="text-xs text-gray-500 dark:text-gray-400">
                                                  School Assignment
                                              </div>
                                          </template>
                                          <template x-if="designation.scope_type === 'district'">
                                              <div class="text-xs text-gray-500 dark:text-gray-400">
                                                  District Assignment
                                              </div>
                                          </template>
                                      </td>

                                      <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                          <span x-text="formatDate(designation.assigned_at)"></span>
                                      </td>

                                      <td class="px-4 py-3">
                                          <button
                                              @click="confirmRemoveDesignation(
                                                  { user_role_id: designation.user_role_id },
                                                  index
                                              )"
                                              class="text-red-600 hover:text-red-500 text-sm font-medium">
                                              Remove
                                          </button>
                                      </td>
                                  </tr>
                                </template>
                            </tbody>
                        </table>
                    </template>
                </div>
            </div>

            <div class="p-6 border-t border-gray-200 dark:border-gray-700">
                <div class="flex justify-end gap-2">
                    <x-ui.button variant="outline" @click="closeDesignationModal()">
                        Close
                    </x-ui.button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="designationDeleteModal"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                Confirm Removal
            </h3>
            <p class="mt-2 text-sm text-gray-500">
                Are you sure you want to remove this designation?
            </p>
            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button variant="outline" @click="designationDeleteModal = false">
                    Cancel
                </x-ui.button>
                <x-ui.button variant="primary" @click="proceedRemoveDesignation()">
                    Yes, Remove
                </x-ui.button>
            </div>
        </div>
    </div>

    <div x-show="designationDeleteSuccessModal"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 w-full max-w-md text-center">
            <div class="flex justify-center mb-4">
                <div class="flex items-center justify-center w-14 h-14 rounded-full bg-green-100">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                Removed Successfully
            </h3>
            <p class="mt-2 text-sm text-gray-500" x-text="designationMessage"></p>
            <div class="mt-6">
                <x-ui.button variant="primary" @click="designationDeleteSuccessModal = false">
                    Close
                </x-ui.button>
            </div>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
