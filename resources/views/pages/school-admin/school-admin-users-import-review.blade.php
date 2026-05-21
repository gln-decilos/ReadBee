@extends('layouts.school-admin-layout')

@section('content')
    <x-common.page-breadcrumb pageTitle="Validate Imported Users" />

    <div
        x-data='importValidation(@json($rows))'
        class="space-y-6"
    >
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-3">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/[0.05] dark:bg-white/[0.03]">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Rows</p>
                <h3 class="mt-2 text-2xl font-semibold text-gray-800 dark:text-white" x-text="totalRows"></h3>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/[0.05] dark:bg-white/[0.03]">
                <p class="text-sm text-gray-500 dark:text-gray-400">Valid Rows</p>
                <h3 class="mt-2 text-2xl font-semibold text-green-600 dark:text-green-400" x-text="validRows"></h3>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/[0.05] dark:bg-white/[0.03]">
                <p class="text-sm text-gray-500 dark:text-gray-400">Invalid Rows</p>
                <h3 class="mt-2 text-2xl font-semibold text-red-600 dark:text-red-400" x-text="invalidRows"></h3>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white pt-4 dark:border-white/[0.05] dark:bg-white/[0.03]">
            <div class="mb-4 px-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                    Review and Fix Data
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Edit the rows directly below. Validation updates automatically as you type.
                </p>
            </div>

            <div class="px-6 pb-6">
                <form action="{{ route('school-admin.users.import.commit') }}" method="POST" @submit="prepareSubmit()">
                    @csrf

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="border-t border-y bg-gray-50 dark:border-white/[0.05] dark:bg-gray-900">
                                <tr>
                                    <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400">Row</th>
                                    <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400">Full Name</th>
                                    <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400">Email</th>
                                    <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400">Role</th>
                                    <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400">Status</th>
                                    <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400">Errors</th>
                                </tr>
                            </thead>

                            <tbody>
                                <template x-for="(row, index) in rows" :key="row.row_number">
                                    <tr class="border-b border-gray-100 dark:border-white/[0.05]">
                                        <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-400">
                                            <span x-text="row.row_number"></span>
                                            <input type="hidden" :name="`rows[${index}][row_number]`" :value="row.row_number">
                                        </td>

                                        <td class="px-4 py-4">
                                            <input
                                                type="text"
                                                :name="`rows[${index}][full_name]`"
                                                x-model="row.full_name"
                                                @input="validateAllRows()"
                                                class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-hidden"
                                                :class="inputClass(row.errors.full_name, row.full_name)"
                                            >
                                        </td>

                                        <td class="px-4 py-4">
                                            <input
                                                type="text"
                                                :name="`rows[${index}][email]`"
                                                x-model="row.email"
                                                @input="validateAllRows()"
                                                class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-hidden"
                                                :class="inputClass(row.errors.email, row.email)"
                                            >
                                        </td>

                                        <td class="px-4 py-4">
                                            <select
                                                :name="`rows[${index}][role]`"
                                                x-model="row.role"
                                                @change="validateAllRows()"
                                                class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-hidden"
                                                :class="inputClass(row.errors.role, row.role)"
                                            >
                                                <option value="">Select role</option>
                                                <option value="Principal">Principal</option>
                                                <option value="Evaluator">Evaluator</option>
                                            </select>
                                        </td>

                                        <td class="px-4 py-4">
                                            <template x-if="row.status === 'valid'">
                                                <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700 dark:bg-green-500/10 dark:text-green-400">
                                                    Valid
                                                </span>
                                            </template>

                                            <template x-if="row.status === 'invalid'">
                                                <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-700 dark:bg-red-500/10 dark:text-red-400">
                                                    Invalid
                                                </span>
                                            </template>
                                        </td>

                                        <td class="px-4 py-4 text-sm">
                                            <template x-if="Object.keys(row.errors).length > 0">
                                                <ul class="space-y-1 text-red-600 dark:text-red-400">
                                                    <template x-for="(message, key) in row.errors" :key="key">
                                                        <li x-text="message"></li>
                                                    </template>
                                                </ul>
                                            </template>

                                            <template x-if="Object.keys(row.errors).length === 0">
                                                <span class="text-green-600 dark:text-green-400">No errors</span>
                                            </template>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 flex flex-wrap justify-between gap-3">
                        <a href="{{ route('school-admin.users.import.index') }}">
                            <x-ui.button variant="outline">
                                Back to Upload
                            </x-ui.button>
                        </a>

                        <div class="flex gap-3">
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="invalidRows > 0"
                            >
                                Create Accounts
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function importValidation(initialRows) {
            return {
                rows: (initialRows || []).map(row => ({
                    row_number: row.row_number ?? '',
                    full_name: row.full_name ?? '',
                    email: row.email ?? '',
                    role: row.role ?? '',
                    status: row.status ?? 'invalid',
                    errors: row.errors ?? {},
                })),

                allowedRoles: ['principal', 'evaluator'],

                init() {
                    this.validateAllRows();
                },

                get totalRows() {
                    return this.rows.length;
                },

                get validRows() {
                    return this.rows.filter(row => row.status === 'valid').length;
                },

                get invalidRows() {
                    return this.rows.filter(row => row.status !== 'valid').length;
                },

                normalizeEmail(email) {
                    return String(email || '').trim().toLowerCase();
                },

                validateAllRows() {
                    const emailCounts = {};

                    this.rows.forEach(row => {
                        const email = this.normalizeEmail(row.email);
                        if (email !== '') {
                            emailCounts[email] = (emailCounts[email] || 0) + 1;
                        }
                    });

                    this.rows = this.rows.map(row => {
                        const errors = {};
                        const fullName = String(row.full_name || '').trim();
                        const email = this.normalizeEmail(row.email);
                        const role = String(row.role || '').trim();
                        const normalizedRole = role.toLowerCase();

                        if (fullName === '') {
                            errors.full_name = 'Full name is required.';
                        }

                        if (email === '') {
                            errors.email = 'Email is required.';
                        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                            errors.email = 'Invalid email format.';
                        } else if ((emailCounts[email] || 0) > 1) {
                            errors.email = 'Duplicate email in file.';
                        }

                        if (role === '') {
                            errors.role = 'Role is required.';
                        } else if (!this.allowedRoles.includes(normalizedRole)) {
                            errors.role = 'Role must be Principal or Evaluator.';
                        }

                        return {
                            ...row,
                            email: email,
                            errors: errors,
                            status: Object.keys(errors).length === 0 ? 'valid' : 'invalid',
                        };
                    });
                },

                inputClass(hasError, value) {
                    if (hasError) {
                        return 'border-red-300 bg-red-50 text-red-700 dark:border-red-500/40 dark:bg-red-500/10 dark:text-red-400';
                    }

                    if (String(value || '').trim() !== '') {
                        return 'border-green-300 bg-green-50 text-gray-800 dark:border-green-500/40 dark:bg-green-500/10 dark:text-white/90';
                    }

                    return 'border-gray-300 bg-white text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
                },

                prepareSubmit() {
                    this.validateAllRows();
                }
            }
        }
    </script>
@endsection
