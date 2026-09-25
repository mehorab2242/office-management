<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { Eye, EyeOff, Pencil, Plus, UserCheck, UserX } from '@lucide/vue';
import AppLayout from '../components/layout/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseDialog from '../components/ui/BaseDialog.vue';
import EmptyState from '../components/ui/EmptyState.vue';
import LoadingState from '../components/ui/LoadingState.vue';
import { createUser, listUsers, updateUser } from '../services/users';
import { apiErrorMessage, validationErrors } from '../services/api';
import { useToast } from '../composables/useToast';
import { useAuthStore } from '../stores/auth';
import { formatDateTime } from '../utils/formatters';
import type { User, ValidationErrors } from '../types';

const auth = useAuthStore();
const toast = useToast();
const users = ref<User[]>([]);
const selected = ref<User | null>(null);
const formOpen = ref(false);
const deactivateOpen = ref(false);
const loading = ref(true);
const saving = ref(false);
const error = ref('');
const errors = ref<ValidationErrors>({});
const form = reactive({ name: '', email: '', password: '', password_confirmation: '', role: 'staff', is_active: true });
const passwordVisible = ref(false);
const confirmationVisible = ref(false);

function isSelf(user: User): boolean { return auth.user?.id === user.id; }
function roleLabel(user: User): string { return user.role === 'staff' ? 'Staff' : 'Super Admin'; }

async function load(): Promise<void> {
    loading.value = true;
    error.value = '';
    try { users.value = await listUsers(); } catch (caught) { error.value = apiErrorMessage(caught); } finally { loading.value = false; }
}

function startCreate(): void {
    selected.value = null;
    passwordVisible.value = false;
    confirmationVisible.value = false;
    Object.assign(form, { name: '', email: '', password: '', password_confirmation: '', role: 'staff', is_active: true });
    errors.value = {};
    formOpen.value = true;
}

function startEdit(user: User): void {
    selected.value = user;
    passwordVisible.value = false;
    confirmationVisible.value = false;
    Object.assign(form, { name: user.name, email: user.email, password: '', password_confirmation: '', is_active: user.is_active });
    errors.value = {};
    formOpen.value = true;
}

async function save(): Promise<void> {
    saving.value = true;
    errors.value = {};
    error.value = '';
    try {
        if (selected.value) {
            const payload: Record<string, unknown> = { name: form.name.trim(), email: form.email.trim(), is_active: form.is_active };
            if (form.password) { payload.password = form.password; payload.password_confirmation = form.password_confirmation; }
            await updateUser(selected.value.id, payload);
            toast.show('User account updated');
        } else {
            await createUser({
                name: form.name.trim(),
                email: form.email.trim(),
                password: form.password,
                password_confirmation: form.password_confirmation,
                is_active: form.is_active,
                role: form.role,
            });
            toast.show('User account created');
        }
        formOpen.value = false;
        await load();
    } catch (caught) {
        errors.value = validationErrors(caught);
        error.value = apiErrorMessage(caught);
    } finally {
        saving.value = false;
    }
}

function confirmDeactivate(user: User): void { selected.value = user; deactivateOpen.value = true; }

async function deactivateSelected(): Promise<void> {
    if (!selected.value) return;
    saving.value = true;
    try {
        await updateUser(selected.value.id, { is_active: false });
        toast.show('User account deactivated');
        deactivateOpen.value = false;
        await load();
    } catch (caught) {
        toast.show('Unable to deactivate staff account', 'error', apiErrorMessage(caught));
    } finally {
        saving.value = false;
    }
}

async function activate(user: User): Promise<void> {
    saving.value = true;
    try {
        await updateUser(user.id, { is_active: true });
        toast.show('Staff account activated');
        await load();
    } catch (caught) {
        toast.show('Unable to activate staff account', 'error', apiErrorMessage(caught));
    } finally {
        saving.value = false;
    }
}

onMounted(load);
</script>
<template>
    <AppLayout>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-brand-700">Administration</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">User Management</h1>
                <p class="mt-1 text-sm text-slate-500">Manage staff and administrator accounts, access, and status.</p>
            </div>
            <BaseButton @click="startCreate"><Plus class="size-4" />Add user</BaseButton>
        </div>
        <p v-if="error" class="mt-6 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ error }}</p>
        <LoadingState v-if="loading" label="Loading user accounts…" />
        <section v-else class="mt-6 overflow-hidden rounded-xl border bg-white">
            <EmptyState v-if="!users.length" title="No user accounts yet" description="Add a staff or administrator account to get started.">
                <BaseButton @click="startCreate">Add user</BaseButton>
            </EmptyState>
            <div v-else>
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full min-w-[52rem] text-left text-sm">
                        <thead class="border-b bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3 font-medium">Name</th>
                                <th class="px-5 py-3 font-medium">Email</th>
                                <th class="px-5 py-3 font-medium">Role</th>
                                <th class="px-5 py-3 font-medium">Status</th>
                                <th class="px-5 py-3 font-medium">Created At</th>
                                <th class="px-5 py-3 text-right font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50">
                                <td class="px-5 py-4 font-medium">
                                    {{ user.name }}
                                    <span v-if="isSelf(user)" class="ml-2 rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700">You</span>
                                </td>
                                <td class="px-5 py-4 text-slate-500">{{ user.email }}</td>
                                <td class="px-5 py-4"><span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="user.role === 'staff' ? 'bg-slate-100 text-slate-600' : 'bg-brand-50 text-brand-700'">{{ roleLabel(user) }}</span></td>
                                <td class="px-5 py-4"><span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="user.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'">{{ user.is_active ? 'Active' : 'Inactive' }}</span></td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-500">{{ formatDateTime(user.created_at ?? null) }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-1">
                                        <button class="rounded p-2 text-slate-500 hover:bg-slate-100" :aria-label="`Edit ${user.name}`" @click="startEdit(user)"><Pencil class="size-4" /></button>
                                        <button v-if="user.is_active" class="rounded p-2 text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40" :disabled="isSelf(user)" :aria-label="`Deactivate ${user.name}`" @click="confirmDeactivate(user)"><UserX class="size-4" /></button>
                                        <button v-else class="rounded p-2 text-emerald-600 hover:bg-emerald-50" :aria-label="`Activate ${user.name}`" @click="activate(user)"><UserCheck class="size-4" /></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="divide-y md:hidden">
                    <article v-for="user in users" :key="user.id" class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-medium">
                                    {{ user.name }}
                                    <span v-if="isSelf(user)" class="text-xs font-medium text-brand-700">(you)</span>
                                </p>
                                <p class="truncate text-sm text-slate-500">{{ user.email }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="user.role === 'staff' ? 'bg-slate-100 text-slate-600' : 'bg-brand-50 text-brand-700'">{{ roleLabel(user) }}</span>
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="user.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'">{{ user.is_active ? 'Active' : 'Inactive' }}</span>
                                    <span class="text-xs text-slate-400">{{ formatDateTime(user.created_at ?? null) }}</span>
                                </div>
                            </div>
                            <div class="flex shrink-0 gap-1">
                                <button class="rounded p-2 text-slate-500 hover:bg-slate-100" :aria-label="`Edit ${user.name}`" @click="startEdit(user)"><Pencil class="size-4" /></button>
                                <button v-if="user.is_active" class="rounded p-2 text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40" :disabled="isSelf(user)" :aria-label="`Deactivate ${user.name}`" @click="confirmDeactivate(user)"><UserX class="size-4" /></button>
                                <button v-else class="rounded p-2 text-emerald-600 hover:bg-emerald-50" :aria-label="`Activate ${user.name}`" @click="activate(user)"><UserCheck class="size-4" /></button>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>
        <BaseDialog v-model:open="formOpen" :title="selected ? 'Edit account' : 'Add user'" description="Manage staff and administrator accounts.">
            <form class="mt-5 space-y-4" @submit.prevent="save">
                <label class="block">
                    <span class="label">Name</span>
                    <input v-model="form.name" class="field" maxlength="255" />
                    <span v-if="errors.name" class="error-text">{{ errors.name[0] }}</span>
                </label>
                <label class="block">
                    <span class="label">Email</span>
                    <input v-model="form.email" class="field" type="email" maxlength="255" />
                    <span v-if="errors.email" class="error-text">{{ errors.email[0] }}</span>
                </label>
                <label v-if="!selected" class="block">
                    <span class="label">Role</span>
                    <select v-model="form.role" class="field" aria-label="Role">
                        <option value="staff">Staff</option>
                        <option value="super_admin">Admin</option>
                    </select>
                    <span v-if="errors.role" class="error-text">{{ errors.role[0] }}</span>
                </label>
                <label class="block">
                    <span class="label">{{ selected ? 'New password (optional)' : 'Password' }}</span>
                    <div class="relative"><input v-model="form.password" class="field pr-11" :type="passwordVisible ? 'text' : 'password'" minlength="12" /><button type="button" class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-500 hover:text-slate-700" :aria-label="passwordVisible ? 'Hide password' : 'Show password'" :aria-pressed="passwordVisible" @click="passwordVisible = !passwordVisible"><EyeOff v-if="passwordVisible" class="size-4" /><Eye v-else class="size-4" /></button></div>
                    <span v-if="errors.password" class="error-text">{{ errors.password[0] }}</span>
                </label>
                <label class="block">
                    <span class="label">{{ selected ? 'Confirm new password' : 'Confirm password' }}</span>
                    <div class="relative"><input v-model="form.password_confirmation" class="field pr-11" :type="confirmationVisible ? 'text' : 'password'" minlength="12" /><button type="button" class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-500 hover:text-slate-700" :aria-label="confirmationVisible ? 'Hide password confirmation' : 'Show password confirmation'" :aria-pressed="confirmationVisible" @click="confirmationVisible = !confirmationVisible"><EyeOff v-if="confirmationVisible" class="size-4" /><Eye v-else class="size-4" /></button></div>
                    <span v-if="errors.password_confirmation" class="error-text">{{ errors.password_confirmation[0] }}</span>
                </label>
                <label class="block">
                    <span class="label">Status</span>
                    <select v-model="form.is_active" class="field">
                        <option :value="true">Active</option>
                        <option :value="false">Inactive</option>
                    </select>
                </label>
                <div class="flex justify-end gap-2">
                    <BaseButton variant="secondary" @click="formOpen = false">Cancel</BaseButton>
                    <BaseButton type="submit" :loading="saving">{{ selected ? 'Save changes' : 'Create account' }}</BaseButton>
                </div>
            </form>
        </BaseDialog>
        <BaseDialog v-model:open="deactivateOpen" title="Deactivate staff account" description="Deactivated accounts cannot sign in until they are activated again.">
            <p class="mt-5 text-sm text-slate-600">Deactivate <strong>{{ selected?.name }}</strong>?</p>
            <div class="mt-6 flex justify-end gap-2">
                <BaseButton variant="secondary" @click="deactivateOpen = false">Cancel</BaseButton>
                <BaseButton variant="danger" :loading="saving" @click="deactivateSelected">Deactivate</BaseButton>
            </div>
        </BaseDialog>
    </AppLayout>
</template>
