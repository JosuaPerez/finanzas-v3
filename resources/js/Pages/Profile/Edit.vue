<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import FinancialPreferencesForm from './Partials/FinancialPreferencesForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: { type: Boolean },
    status:          { type: String  },
    hasPassword: { type: Boolean, default: true },
});
</script>

<template>
    <Head title="Perfil y preferencias" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-xl text-white leading-tight tracking-tight flex items-center gap-2">
                ⚙️ Perfil y preferencias
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">

                <FinancialPreferencesForm />

                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                />

                <UpdatePasswordForm />

                <DeleteUserForm :has-password="hasPassword" />

            </div>
        </div>
    </AuthenticatedLayout>
</template>
