<script setup lang="ts">
import { reactive, ref } from 'vue';
import { Eye, EyeOff } from '@lucide/vue';
import AppLayout from '../components/layout/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import { apiErrorMessage, validationErrors } from '../services/api';
import { changePassword } from '../services/profile';
import type { ValidationErrors } from '../types';

const form = reactive({ current_password: '', password: '', password_confirmation: '' });
const errors = ref<ValidationErrors>({});
const error = ref('');
const success = ref('');
const saving = ref(false);
const currentPasswordVisible = ref(false);
const newPasswordVisible = ref(false);
const confirmationVisible = ref(false);

async function submit(): Promise<void> {
    saving.value = true;
    errors.value = {};
    error.value = '';
    success.value = '';

    try {
        await changePassword({ ...form });
        form.current_password = '';
        form.password = '';
        form.password_confirmation = '';
        success.value = 'Your password has been changed successfully.';
    } catch (caught) {
        errors.value = validationErrors(caught);
        error.value = apiErrorMessage(caught, 'Unable to change your password. Please try again.');
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <AppLayout>
        <div class="mx-auto max-w-3xl space-y-6">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Profile</h1>
                <p class="mt-1 text-sm text-slate-500">Manage your account security.</p>
            </div>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="change-password-title">
                <div class="mb-6">
                    <h2 id="change-password-title" class="text-lg font-semibold text-slate-900">Change Password</h2>
                    <p class="mt-1 text-sm text-slate-500">Choose a new password with at least 12 characters.</p>
                </div>

                <form class="max-w-xl space-y-5" @submit.prevent="submit">
                    <p v-if="success" role="status" class="rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ success }}</p>
                    <p v-if="error" role="alert" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ error }}</p>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700" for="current-password">Current Password</label>
                        <div class="relative">
                            <input id="current-password" v-model="form.current_password" class="field pr-11" :type="currentPasswordVisible ? 'text' : 'password'" autocomplete="current-password" required />
                            <button type="button" class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-500 hover:text-slate-700" :aria-label="currentPasswordVisible ? 'Hide current password' : 'Show current password'" :aria-pressed="currentPasswordVisible" @click="currentPasswordVisible = !currentPasswordVisible">
                                <EyeOff v-if="currentPasswordVisible" class="size-4" />
                                <Eye v-else class="size-4" />
                            </button>
                        </div>
                        <p v-if="errors.current_password" class="text-sm text-red-600">{{ errors.current_password[0] }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700" for="new-password">New Password</label>
                        <div class="relative">
                            <input id="new-password" v-model="form.password" class="field pr-11" :type="newPasswordVisible ? 'text' : 'password'" autocomplete="new-password" minlength="12" required />
                            <button type="button" class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-500 hover:text-slate-700" :aria-label="newPasswordVisible ? 'Hide new password' : 'Show new password'" :aria-pressed="newPasswordVisible" @click="newPasswordVisible = !newPasswordVisible">
                                <EyeOff v-if="newPasswordVisible" class="size-4" />
                                <Eye v-else class="size-4" />
                            </button>
                        </div>
                        <p v-if="errors.password" class="text-sm text-red-600">{{ errors.password[0] }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700" for="confirm-new-password">Confirm New Password</label>
                        <div class="relative">
                            <input id="confirm-new-password" v-model="form.password_confirmation" class="field pr-11" :type="confirmationVisible ? 'text' : 'password'" autocomplete="new-password" minlength="12" required />
                            <button type="button" class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-500 hover:text-slate-700" :aria-label="confirmationVisible ? 'Hide password confirmation' : 'Show password confirmation'" :aria-pressed="confirmationVisible" @click="confirmationVisible = !confirmationVisible">
                                <EyeOff v-if="confirmationVisible" class="size-4" />
                                <Eye v-else class="size-4" />
                            </button>
                        </div>
                        <p v-if="errors.password_confirmation" class="text-sm text-red-600">{{ errors.password_confirmation[0] }}</p>
                    </div>

                    <BaseButton type="submit" :loading="saving">Change Password</BaseButton>
                </form>
            </section>
        </div>
    </AppLayout>
</template>
