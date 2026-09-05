<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    imports: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    file: null,
});

const fileInput = ref(null);
const selectedImport = ref(null);

const submit = () => {
    form.post(route('imports.store'), {
        onSuccess: () => {
            form.reset();
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
};

const openLogs = (importItem) => {
    selectedImport.value = importItem;
};

const closeLogs = () => {
    selectedImport.value = null;
};

const statusBadge = (status) => {
    switch (status) {
        case 'success':
            return 'bg-green-100 text-green-800';
        case 'partial':
            return 'bg-yellow-100 text-yellow-800';
        default:
            return 'bg-red-100 text-red-800';
    }
};
</script>

<template>
    <Head title="Transaction Import" />

    <div class="min-h-screen bg-gray-100 py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-gray-900">Bank Transaction Import</h1>
            </div>

            <!-- Upload Card -->
            <div class="bg-white p-6 shadow sm:rounded-lg">
                <form @submit.prevent="submit" class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                    <div class="flex-1 w-full">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Upload bank statement file (CSV, JSON, XML)
                        </label>
                        <input
                            ref="fileInput"
                            type="file"
                            accept=".csv,.json,.xml"
                            :disabled="form.processing"
                            @input="form.file = $event.target.files[0]"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 cursor-pointer border border-gray-300 rounded-md"
                        />
                        <InputError :message="form.errors.file" class="mt-2" />
                    </div>
                    <PrimaryButton :disabled="!form.file || form.processing" class="sm:self-end mt-1">
                        <span v-if="form.processing">Processing...</span>
                        <span v-else>Send File</span>
                    </PrimaryButton>
                </form>
            </div>

            <!-- Table Card -->
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Import History</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-medium">
                            <tr>
                                <th class="px-6 py-3 text-left">File Name</th>
                                <th class="px-6 py-3 text-center">Total</th>
                                <th class="px-6 py-3 text-center">Success</th>
                                <th class="px-6 py-3 text-center">Errors</th>
                                <th class="px-6 py-3 text-center">Status</th>
                                <th class="px-6 py-3 text-left">Created At</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="importItem in imports" :key="importItem.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">{{ importItem.file_name }}</td>
                                <td class="px-6 py-4 text-center text-gray-600">{{ importItem.total_records }}</td>
                                <td class="px-6 py-4 text-center font-semibold text-green-600">{{ importItem.successful_records }}</td>
                                <td class="px-6 py-4 text-center font-semibold" :class="importItem.failed_records ? 'text-red-600' : 'text-gray-400'">
                                    {{ importItem.failed_records }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span :class="statusBadge(importItem.status)" class="px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wide">
                                        {{ importItem.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-500 text-xs whitespace-nowrap">
                                    {{ new Date(importItem.created_at).toLocaleString('en-US') }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button
                                        v-if="importItem.failed_records > 0"
                                        type="button"
                                        @click="openLogs(importItem)"
                                        class="text-xs font-semibold text-red-600 hover:text-red-800 hover:underline cursor-pointer"
                                    >
                                        Errors ({{ importItem.failed_records }})
                                    </button>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                            </tr>
                            <tr v-if="!imports.length">
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500 text-sm">
                                    No import history found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Native Breeze Modal -->
            <Modal :show="!!selectedImport" @close="closeLogs" max-width="2xl">
                <div class="p-6">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Errors in import #{{ selectedImport?.id }}
                        </h3>
                        <button @click="closeLogs" class="text-gray-400 hover:text-gray-600 text-xl font-bold leading-none cursor-pointer">&times;</button>
                    </div>
                    <div class="mt-4 max-h-96 overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-xs">
                            <thead class="bg-gray-50 text-gray-500 uppercase font-medium">
                                <tr>
                                    <th class="px-4 py-2 text-left w-1/3">Transaction ID</th>
                                    <th class="px-4 py-2 text-left">Error Message</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr v-for="logEntry in selectedImport?.logs" :key="logEntry.id">
                                    <td class="px-4 py-2 font-mono text-gray-600 whitespace-nowrap">{{ logEntry.transaction_id || 'No ID' }}</td>
                                    <td class="px-4 py-2 text-red-600">{{ logEntry.error_message }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <SecondaryButton @click="closeLogs">Close</SecondaryButton>
                    </div>
                </div>
            </Modal>
        </div>
    </div>
</template>
