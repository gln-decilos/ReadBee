@props([
    'municipalities' => [],
    'districts' => [],
    'page' => 1,
    'perPage' => 5,
])

@php
    $districtOptions = $districts;
@endphp

<div x-data='{
    allMunicipalities: @json($municipalities),
    districts: @json($districtOptions),

    page: {{ (int) $page }},
    perPage: {{ (int) $perPage }},

    selectedRows: [],
    selectAll: false,
    showAddForm: false,
    showEditForm: false,

    confirmModal: false,
    successModal: false,

    bulkDeleteModal: false,
    deleteSuccessModal: false,
    deleteMessage: "",
    successMessage: "",
    deleteMode: null,
    deleteId: null,

    formErrors: {},
    editFormErrors: {},

    editMunicipality: {
        municipality_id: "",
        municipal_name: "",
        district_id: "",
        logo: ""
    },

    get total() {
        return this.allMunicipalities.length;
    },

    get lastPage() {
        return Math.max(Math.ceil(this.total / this.perPage), 1);
    },

    get paginatedMunicipalities() {
        const start = (this.page - 1) * this.perPage;
        const end = start + this.perPage;
        return this.allMunicipalities.slice(start, end);
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

    handleSelectAll() {
        this.selectAll = !this.selectAll;
        this.selectedRows = this.selectAll
            ? this.paginatedMunicipalities.map(m => String(m.municipality_id))
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
            this.paginatedMunicipalities.length > 0 &&
            this.selectedRows.length === this.paginatedMunicipalities.length;
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
            const response = await fetch("{{ route('district-admin.municipalities.destroy') }}", {
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
                this.allMunicipalities = this.allMunicipalities.filter(
                    municipality => !idsToDelete.includes(String(municipality.municipality_id))
                );

                if (this.page > this.lastPage) {
                    this.page = this.lastPage;
                }

                this.selectedRows = [];
                this.selectAll = false;
                this.deleteMessage = data.message || "Municipalities deleted successfully";
                this.deleteSuccessModal = true;
                this.deleteMode = null;
                this.deleteId = null;
            } else {
                alert(data.message || "Failed to delete municipalities.");
            }
        } catch (error) {
            console.error("Delete error:", error);
            alert("An error occurred while deleting municipalities.");
        }
    },

    submitForm() {
        this.confirmModal = false;
        this.addMunicipality();
    },

    async addMunicipality() {
        this.formErrors = {};

        const formData = new FormData(this.$refs.createForm);

        try {
            const response = await fetch("{{ route('district-admin.municipalities.store') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Accept": "application/json"
                },
                body: formData
            });

            const data = await response.json();

            if (!response.ok) {
                this.formErrors = data.errors || {};
                alert(data.message || "Failed to create municipality.");
                return;
            }

            const selectedDistrict = this.districts.find(
                d => String(d.district_id) === String(data.municipality.district_id)
            );

            const newMunicipality = {
                ...data.municipality,
                districts: selectedDistrict
                    ? { district_name: selectedDistrict.district_name }
                    : { district_name: "-" }
            };

            this.allMunicipalities.unshift(newMunicipality);
            this.page = 1;

            this.showAddForm = false;
            this.successMessage = data.message || "Municipality created successfully.";
            this.successModal = true;
            this.$refs.createForm.reset();
            this.selectedRows = [];
            this.selectAll = false;
        } catch (error) {
            console.error("Create error:", error);
            alert("An error occurred while creating municipality.");
        }
    },

    openEditForm(municipality) {
        this.editFormErrors = {};
        this.editMunicipality = {
            municipality_id: municipality.municipality_id,
            municipal_name: municipality.municipal_name || "",
            district_id: municipality.district_id || "",
            logo: municipality.logo || ""
        };

        this.showEditForm = true;
        this.showAddForm = false;
    },

    async updateMunicipality() {
        this.editFormErrors = {};

        const formData = new FormData(this.$refs.editForm);
        formData.append("_method", "PATCH");

        try {
            const response = await fetch(`/district-admin/municipality/${this.editMunicipality.municipality_id}`, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Accept": "application/json"
                },
                body: formData
            });

            const data = await response.json();

            if (!response.ok) {
                this.editFormErrors = data.errors || {};
                alert(data.message || "Failed to update municipality.");
                return;
            }

            const selectedDistrict = this.districts.find(
                d => String(d.district_id) === String(data.municipality.district_id)
            );

            const index = this.allMunicipalities.findIndex(
                m => String(m.municipality_id) === String(data.municipality.municipality_id)
            );

            if (index !== -1) {
                this.allMunicipalities[index] = {
                    ...this.allMunicipalities[index],
                    ...data.municipality,
                    districts: selectedDistrict
                        ? { district_name: selectedDistrict.district_name }
                        : { district_name: "-" }
                };
            }

            this.showEditForm = false;
            this.successMessage = data.message || "Municipality updated successfully.";
            this.successModal = true;
        } catch (error) {
            console.error("Update error:", error);
            alert("An error occurred while updating municipality.");
        }
    }
}'>
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white pt-4 dark:border-white/[0.05] dark:bg-white/[0.03]">

        <div class="flex flex-col gap-4 px-6 mb-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                    Municipalities
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
                        @click="showAddForm = !showAddForm; showEditForm = false"
                    >
                        <span x-text="showAddForm ? 'Close Form' : 'Add Municipality'"></span>
                    </x-ui.button>
                </template>
            </div>
        </div>

        <div x-show="showAddForm" x-transition class="px-6 mb-6">
            <form x-ref="createForm" enctype="multipart/form-data" @submit.prevent="confirmModal = true">
                @csrf

                <x-common.component-card title="Add New Municipality">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Municipality Name
                        </label>
                        <input
                            type="text"
                            name="municipal_name"
                            placeholder="Enter municipality name"
                            required
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                        />
                        <template x-if="formErrors.municipal_name">
                            <p class="mt-1 text-sm text-red-600" x-text="formErrors.municipal_name[0]"></p>
                        </template>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            District
                        </label>
                        <select
                            name="district_id"
                            required
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        >
                            <option value="">Select District</option>
                            @foreach($districts as $district)
                                <option value="{{ $district['district_id'] }}">
                                    {{ $district['district_name'] }}
                                </option>
                            @endforeach
                        </select>
                        <template x-if="formErrors.district_id">
                            <p class="mt-1 text-sm text-red-600" x-text="formErrors.district_id[0]"></p>
                        </template>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Logo
                        </label>
                        <input
                            type="file"
                            name="logo"
                            accept="image/*"
                            class="dark:bg-dark-900 shadow-theme-xs block w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        />
                        <template x-if="formErrors.logo">
                            <p class="mt-1 text-sm text-red-600" x-text="formErrors.logo[0]"></p>
                        </template>
                    </div>
                </x-common.component-card>

                <div class="mt-4 flex justify-end">
                    <x-ui.button type="submit" variant="outline">
                        Create Municipality
                    </x-ui.button>
                </div>
            </form>
        </div>

        <div x-show="showEditForm" x-transition class="px-6 mb-6">
            <form x-ref="editForm" enctype="multipart/form-data" @submit.prevent="updateMunicipality()">
                @csrf
                <input type="hidden" name="municipality_id" x-model="editMunicipality.municipality_id">

                <x-common.component-card title="Edit Municipality">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Municipality Name
                        </label>
                        <input
                            type="text"
                            name="municipal_name"
                            x-model="editMunicipality.municipal_name"
                            required
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        />
                        <template x-if="editFormErrors.municipal_name">
                            <p class="mt-1 text-sm text-red-600" x-text="editFormErrors.municipal_name[0]"></p>
                        </template>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            District
                        </label>
                        <select
                            name="district_id"
                            x-model="editMunicipality.district_id"
                            required
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        >
                            <option value="">Select District</option>
                            @foreach($districts as $district)
                                <option value="{{ $district['district_id'] }}">
                                    {{ $district['district_name'] }}
                                </option>
                            @endforeach
                        </select>
                        <template x-if="editFormErrors.district_id">
                            <p class="mt-1 text-sm text-red-600" x-text="editFormErrors.district_id[0]"></p>
                        </template>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Current Logo
                        </label>
                        <div class="mb-3">
                            <template x-if="editMunicipality.logo">
                                <img :src="editMunicipality.logo"
                                     alt="Municipality Logo"
                                     class="w-16 h-16 rounded-full object-cover border border-gray-200 dark:border-gray-700" />
                            </template>

                            <template x-if="!editMunicipality.logo">
                                <div class="flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                                    <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M3 21H21M5 21V7.8C5 7.35817 5.35817 7 5.8 7H10.2C10.6418 7 11 7.35817 11 7.8V21M13 21V3.8C13 3.35817 13.3582 3 13.8 3H18.2C18.6418 3 19 3.35817 19 3.8V21M7 10H9M7 13H9M15 6H17M15 9H17M15 12H17"/>
                                    </svg>
                                </div>
                            </template>
                        </div>

                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Change Logo
                        </label>
                        <input
                            type="file"
                            name="logo"
                            accept="image/*"
                            class="dark:bg-dark-900 shadow-theme-xs block w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        />
                        <template x-if="editFormErrors.logo">
                            <p class="mt-1 text-sm text-red-600" x-text="editFormErrors.logo[0]"></p>
                        </template>
                    </div>
                </x-common.component-card>

                <div class="mt-4 flex justify-end gap-3">
                    <x-ui.button type="button" variant="outline" @click="showEditForm = false">
                        Cancel
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Update Municipality
                    </x-ui.button>
                </div>
            </form>
        </div>

        <div x-show="!showAddForm && !showEditForm" x-transition class="max-w-full overflow-x-auto px-6 pb-6">
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
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 text-start dark:text-gray-400">Municipality Name</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 text-start dark:text-gray-400">District</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 text-start dark:text-gray-400">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <template x-for="municipality in paginatedMunicipalities" :key="municipality.municipality_id">
                        <tr class="border-b border-gray-100 dark:border-white/[0.05]" :data-id="municipality.municipality_id">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div @click="handleRowSelect(String(municipality.municipality_id))"
                                         class="relative flex h-5 w-5 cursor-pointer items-center justify-center rounded-md border-[1.25px] transition-colors duration-200"
                                         :class="selectedRows.includes(String(municipality.municipality_id))
                                            ? 'border-brand-500 bg-brand-500'
                                            : 'border-gray-300 bg-transparent hover:border-brand-500 dark:border-gray-700 dark:hover:border-brand-500'">
                                        <span :class="selectedRows.includes(String(municipality.municipality_id)) ? '' : 'opacity-0'" class="flex items-center justify-center">
                                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M11.6666 3.5L5.24992 9.91667L2.33325 7" stroke="white" stroke-width="1.94437" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center justify-center w-10 h-10 rounded-full overflow-hidden bg-gray-100 border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                                        <template x-if="municipality.logo">
                                            <img :src="municipality.logo"
                                                 :alt="municipality.municipal_name || 'Municipality Logo'"
                                                 class="w-full h-full object-cover" />
                                        </template>

                                        <template x-if="!municipality.logo">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M3 21H21M5 21V7.8C5 7.35817 5.35817 7 5.8 7H10.2C10.6418 7 11 7.35817 11 7.8V21M13 21V3.8C13 3.35817 13.3582 3 13.8 3H18.2C18.6418 3 19 3.35817 19 3.8V21M7 10H9M7 13H9M15 6H17M15 9H17M15 12H17"/>
                                            </svg>
                                        </template>
                                    </div>
                                    <div>
                                        <span class="block text-sm font-medium text-gray-700 dark:text-gray-400" x-text="municipality.municipal_name || 'N/A'"></span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-400" x-text="municipality.districts?.district_name || '-'"></td>

                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <button
                                        @click="openEditForm(municipality)"
                                        class="text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                                        title="Edit Municipality"
                                    >
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round"
                                                  stroke-linejoin="round"
                                                  stroke-width="2"
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L12 15l-4 1 1-4 8.586-8.586z"/>
                                        </svg>
                                    </button>

                                    <button
                                        @click="deleteRow(String(municipality.municipality_id))"
                                        class="text-gray-700 hover:text-red-500 dark:text-gray-400 dark:hover:text-red-400"
                                        title="Delete Municipality"
                                    >
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

                    <template x-if="paginatedMunicipalities.length === 0">
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                No municipalities found.
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div x-show="!showAddForm && !showEditForm" x-transition class="flex items-center justify-between px-6 pb-6">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Showing
                <span class="font-medium" x-text="startItem"></span>
                to
                <span class="font-medium" x-text="endItem"></span>
                of
                <span class="font-medium" x-text="total"></span>
                municipalities
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

    <div x-show="confirmModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                Confirm Municipality Creation
            </h3>
            <p class="mt-2 text-sm text-gray-500">
                Are you sure you want to create this municipality?
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

    <div x-show="successModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 w-full max-w-md text-center">
            <div class="flex justify-center mb-4">
                <div class="flex items-center justify-center w-14 h-14 rounded-full bg-green-100">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Success</h3>
            <p class="mt-2 text-sm text-gray-500" x-text="successMessage"></p>
            <div class="mt-6">
                <x-ui.button variant="primary" @click="successModal = false">
                    Close
                </x-ui.button>
            </div>
        </div>
    </div>

    <div x-show="bulkDeleteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Confirm Delete</h3>
            <p class="mt-2 text-sm text-gray-500">
                <template x-if="deleteMode === 'single'">
                    <span>Are you sure you want to delete this municipality?</span>
                </template>
                <template x-if="deleteMode !== 'single'">
                    <span>
                        Are you sure you want to delete
                        <strong x-text="selectedRows.length"></strong>
                        selected municipality(s)?
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

    <div x-show="deleteSuccessModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 w-full max-w-md text-center">
            <div class="flex justify-center mb-4">
                <div class="flex items-center justify-center w-14 h-14 rounded-full bg-green-100">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
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
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
