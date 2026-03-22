@props([
    'schools' => [],
    'districts' => [],
    'municipalities' => [],
    'page' => 1,
    'perPage' => 5,
])

@php
    $districtOptions = $districts;
    $municipalityOptions = $municipalities;
@endphp

<div x-data='{
    allSchools: @json($schools),
    districts: @json($districtOptions),
    municipalities: @json($municipalityOptions),
    filteredMunicipalities: @json($municipalityOptions),

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

    editSchool: {
        school_id: "",
        name: "",
        district_id: "",
        municipality_id: "",
        logo: "",
        address: "",
        contact: "",
        email: ""
    },

    get total() {
        return this.allSchools.length;
    },

    get lastPage() {
        return Math.max(Math.ceil(this.total / this.perPage), 1);
    },

    get paginatedSchools() {
        const start = (this.page - 1) * this.perPage;
        const end = start + this.perPage;
        return this.allSchools.slice(start, end);
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
            ? this.paginatedSchools.map(s => String(s.school_id))
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
            this.paginatedSchools.length > 0 &&
            this.selectedRows.length === this.paginatedSchools.length;
    },

    filterMunicipalitiesByDistrict(districtId, mode = "create") {
        this.filteredMunicipalities = this.municipalities.filter(
            m => String(m.district_id) === String(districtId)
        );

        if (mode === "create") {
            if (this.$refs.createMunicipalitySelect) {
                this.$refs.createMunicipalitySelect.value = "";
            }
        } else {
            this.editSchool.municipality_id = "";
        }
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
            const response = await fetch("{{ route('district-admin.schools.destroy') }}", {
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
                this.allSchools = this.allSchools.filter(
                    school => !idsToDelete.includes(String(school.school_id))
                );

                if (this.page > this.lastPage) {
                    this.page = this.lastPage;
                }

                this.selectedRows = [];
                this.selectAll = false;
                this.deleteMessage = data.message || "Schools deleted successfully";
                this.deleteSuccessModal = true;
                this.deleteMode = null;
                this.deleteId = null;
            } else {
                alert(data.message || "Failed to delete schools.");
            }
        } catch (error) {
            console.error("Delete error:", error);
            alert("An error occurred while deleting schools.");
        }
    },

    submitForm() {
        this.confirmModal = false;
        this.addSchool();
    },

    async addSchool() {
        this.formErrors = {};

        const formData = new FormData(this.$refs.createForm);

        try {
            const response = await fetch("{{ route('district-admin.schools.store') }}", {
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
                alert(data.message || "Failed to create school.");
                return;
            }

            const selectedDistrict = this.districts.find(
                d => String(d.district_id) === String(data.school.district_id)
            );

            const selectedMunicipality = this.municipalities.find(
                m => String(m.municipality_id) === String(data.school.municipality_id)
            );

            const newSchool = {
                ...data.school,
                districts: selectedDistrict
                    ? { district_name: selectedDistrict.district_name }
                    : { district_name: "-" },
                municipalities: selectedMunicipality
                    ? { municipal_name: selectedMunicipality.municipal_name }
                    : { municipal_name: "-" }
            };

            this.allSchools.unshift(newSchool);
            this.page = 1;

            this.showAddForm = false;
            this.successMessage = data.message || "School created successfully.";
            this.successModal = true;
            this.$refs.createForm.reset();
            this.selectedRows = [];
            this.selectAll = false;
            this.filteredMunicipalities = this.municipalities;
        } catch (error) {
            console.error("Create error:", error);
            alert("An error occurred while creating school.");
        }
    },

    openEditForm(school) {
        this.editFormErrors = {};
        this.editSchool = {
            school_id: school.school_id,
            name: school.name || "",
            district_id: school.district_id || "",
            municipality_id: school.municipality_id || "",
            logo: school.logo || "",
            address: school.address || "",
            contact: school.contact || "",
            email: school.email || ""
        };

        this.filteredMunicipalities = this.municipalities.filter(
            m => String(m.district_id) === String(school.district_id)
        );

        this.showEditForm = true;
        this.showAddForm = false;
    },

    async updateSchool() {
        this.editFormErrors = {};

        const formData = new FormData(this.$refs.editForm);
        formData.append("_method", "PATCH");

        try {
            const response = await fetch(`/district-admin/schools-management/${this.editSchool.school_id}`, {
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
                alert(data.message || "Failed to update school.");
                return;
            }

            const selectedDistrict = this.districts.find(
                d => String(d.district_id) === String(data.school.district_id)
            );

            const selectedMunicipality = this.municipalities.find(
                m => String(m.municipality_id) === String(data.school.municipality_id)
            );

            const index = this.allSchools.findIndex(
                s => String(s.school_id) === String(data.school.school_id)
            );

            if (index !== -1) {
                this.allSchools[index] = {
                    ...this.allSchools[index],
                    ...data.school,
                    districts: selectedDistrict
                        ? { district_name: selectedDistrict.district_name }
                        : { district_name: "-" },
                    municipalities: selectedMunicipality
                        ? { municipal_name: selectedMunicipality.municipal_name }
                        : { municipal_name: "-" }
                };
            }

            this.showEditForm = false;
            this.successMessage = data.message || "School updated successfully.";
            this.successModal = true;
        } catch (error) {
            console.error("Update error:", error);
            alert("An error occurred while updating school.");
        }
    }
}'>
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white pt-4 dark:border-white/[0.05] dark:bg-white/[0.03]">

        <div class="mb-4 flex flex-col gap-4 px-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                    Schools
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
                        <span x-text="showAddForm ? 'Close Form' : 'Add School'"></span>
                    </x-ui.button>
                </template>
            </div>
        </div>

        <div x-show="showAddForm" x-transition class="mb-6 px-6">
            <form
                x-ref="createForm"
                enctype="multipart/form-data"
                @submit.prevent="confirmModal = true"
            >
                @csrf

                <x-common.component-card title="Add New School">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            School Name
                        </label>
                        <input
                            type="text"
                            name="name"
                            placeholder="Enter school name"
                            required
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                        />
                        <template x-if="formErrors.name">
                            <p class="mt-1 text-sm text-red-600" x-text="formErrors.name[0]"></p>
                        </template>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            District
                        </label>
                        <select
                            name="district_id"
                            required
                            @change="filterMunicipalitiesByDistrict($event.target.value, 'create')"
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
                            Municipality
                        </label>
                        <select
                            x-ref="createMunicipalitySelect"
                            name="municipality_id"
                            required
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        >
                            <option value="">Select Municipality</option>
                            <template x-for="municipality in filteredMunicipalities" :key="municipality.municipality_id">
                                <option :value="municipality.municipality_id" x-text="municipality.municipal_name"></option>
                            </template>
                        </select>
                        <template x-if="formErrors.municipality_id">
                            <p class="mt-1 text-sm text-red-600" x-text="formErrors.municipality_id[0]"></p>
                        </template>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Address
                        </label>
                        <input
                            type="text"
                            name="address"
                            placeholder="Enter school address"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                        />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Contact
                        </label>
                        <input
                            type="text"
                            name="contact"
                            placeholder="Enter contact number"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                        />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Email
                        </label>
                        <input
                            type="email"
                            name="email"
                            placeholder="Enter school email"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                        />
                        <template x-if="formErrors.email">
                            <p class="mt-1 text-sm text-red-600" x-text="formErrors.email[0]"></p>
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
                        Create School
                    </x-ui.button>
                </div>
            </form>
        </div>

        <div x-show="showEditForm" x-transition class="mb-6 px-6">
            <form
                x-ref="editForm"
                enctype="multipart/form-data"
                @submit.prevent="updateSchool()"
            >
                @csrf
                <input type="hidden" name="school_id" x-model="editSchool.school_id">

                <x-common.component-card title="Edit School">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            School Name
                        </label>
                        <input
                            type="text"
                            name="name"
                            x-model="editSchool.name"
                            required
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        />
                        <template x-if="editFormErrors.name">
                            <p class="mt-1 text-sm text-red-600" x-text="editFormErrors.name[0]"></p>
                        </template>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            District
                        </label>
                        <select
                            name="district_id"
                            x-model="editSchool.district_id"
                            required
                            @change="filterMunicipalitiesByDistrict(editSchool.district_id, 'edit')"
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
                            Municipality
                        </label>
                        <select
                            name="municipality_id"
                            x-model="editSchool.municipality_id"
                            required
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        >
                            <option value="">Select Municipality</option>
                            <template x-for="municipality in filteredMunicipalities" :key="municipality.municipality_id">
                                <option :value="municipality.municipality_id" x-text="municipality.municipal_name"></option>
                            </template>
                        </select>
                        <template x-if="editFormErrors.municipality_id">
                            <p class="mt-1 text-sm text-red-600" x-text="editFormErrors.municipality_id[0]"></p>
                        </template>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Address
                        </label>
                        <input
                            type="text"
                            name="address"
                            x-model="editSchool.address"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Contact
                        </label>
                        <input
                            type="text"
                            name="contact"
                            x-model="editSchool.contact"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Email
                        </label>
                        <input
                            type="email"
                            name="email"
                            x-model="editSchool.email"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Current Logo
                        </label>
                        <div class="mb-3">
                            <template x-if="editSchool.logo">
                                <img :src="editSchool.logo"
                                     alt="School Logo"
                                     class="h-16 w-16 rounded-full object-cover border border-gray-200 dark:border-gray-700" />
                            </template>

                            <template x-if="!editSchool.logo">
                                <div class="flex h-16 w-16 items-center justify-center rounded-full border border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-800">
                                    <svg class="h-7 w-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M3 9L12 4L21 9L12 14L3 9ZM6 11.5V16.5C6 16.5 8.5 19 12 19C15.5 19 18 16.5 18 16.5V11.5M21 9V15"/>
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
                        Update School
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
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 text-start dark:text-gray-400">School Name</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 text-start dark:text-gray-400">District</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 text-start dark:text-gray-400">Municipality</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 text-start dark:text-gray-400">Contact</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 text-start dark:text-gray-400">Email</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 text-start dark:text-gray-400">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <template x-for="school in paginatedSchools" :key="school.school_id">
                        <tr class="border-b border-gray-100 dark:border-white/[0.05]" :data-id="school.school_id">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div @click="handleRowSelect(String(school.school_id))"
                                         class="relative flex h-5 w-5 cursor-pointer items-center justify-center rounded-md border-[1.25px] transition-colors duration-200"
                                         :class="selectedRows.includes(String(school.school_id))
                                            ? 'border-brand-500 bg-brand-500'
                                            : 'border-gray-300 bg-transparent hover:border-brand-500 dark:border-gray-700 dark:hover:border-brand-500'">
                                        <span :class="selectedRows.includes(String(school.school_id)) ? '' : 'opacity-0'" class="flex items-center justify-center">
                                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M11.6666 3.5L5.24992 9.91667L2.33325 7" stroke="white" stroke-width="1.94437" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full border border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-800">
                                        <template x-if="school.logo">
                                            <img :src="school.logo"
                                                 :alt="school.name || 'School Logo'"
                                                 class="h-full w-full object-cover" />
                                        </template>

                                        <template x-if="!school.logo">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M3 9L12 4L21 9L12 14L3 9ZM6 11.5V16.5C6 16.5 8.5 19 12 19C15.5 19 18 16.5 18 16.5V11.5M21 9V15"/>
                                            </svg>
                                        </template>
                                    </div>
                                    <div>
                                        <span class="block text-sm font-medium text-gray-700 dark:text-gray-400" x-text="school.name || 'N/A'"></span>
                                        <span class="block text-xs text-gray-500 dark:text-gray-500" x-text="school.address || '-'"></span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-400" x-text="school.districts?.district_name || '-'"></td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-400" x-text="school.municipalities?.municipal_name || '-'"></td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-400" x-text="school.contact || '-'"></td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-400" x-text="school.email || '-'"></td>

                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <button
                                        @click="openEditForm(school)"
                                        class="text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                                        title="Edit School"
                                    >
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round"
                                                  stroke-linejoin="round"
                                                  stroke-width="2"
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L12 15l-4 1 1-4 8.586-8.586z"/>
                                        </svg>
                                    </button>

                                    <button
                                        @click="deleteRow(String(school.school_id))"
                                        class="text-gray-700 hover:text-red-500 dark:text-gray-400 dark:hover:text-red-400"
                                        title="Delete School"
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

                    <template x-if="paginatedSchools.length === 0">
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                No schools found.
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
                schools
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

    <div x-show="confirmModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                Confirm School Creation
            </h3>
            <p class="mt-2 text-sm text-gray-500">
                Are you sure you want to create this school?
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

    <div x-show="successModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 w-full max-w-md text-center">
            <div class="mb-4 flex justify-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-green-100">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                Success
            </h3>
            <p class="mt-2 text-sm text-gray-500" x-text="successMessage"></p>
            <div class="mt-6">
                <x-ui.button variant="primary" @click="successModal = false">
                    Close
                </x-ui.button>
            </div>
        </div>
    </div>

    <div x-show="bulkDeleteModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                Confirm Delete
            </h3>
            <p class="mt-2 text-sm text-gray-500">
                <template x-if="deleteMode === 'single'">
                    <span>Are you sure you want to delete this school?</span>
                </template>
                <template x-if="deleteMode !== 'single'">
                    <span>
                        Are you sure you want to delete
                        <strong x-text="selectedRows.length"></strong>
                        selected school(s)?
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
            <div class="mb-4 flex justify-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-green-100">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor"
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
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
