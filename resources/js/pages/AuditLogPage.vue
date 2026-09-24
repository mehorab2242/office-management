<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import AppLayout from '../components/layout/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import LoadingState from '../components/ui/LoadingState.vue';
import DatePicker from '../components/ui/DatePicker.vue';
import { listAuditLogs } from '../services/audit';
import { listUsers } from '../services/users';
import { apiErrorMessage } from '../services/api';
import { formatDateTime } from '../utils/formatters';
import type { AuditLog, PaginationMeta, User } from '../types';

const logs = ref<AuditLog[]>([]); const users = ref<User[]>([]);
const meta = ref<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 20, total: 0 });
const loading = ref(true); const error = ref('');
const filters = reactive({ action: '', actor_user_id: '', subject_type: '', date_from: '', date_to: '' });
async function load(page=1): Promise<void> { loading.value = true; try { const response = await listAuditLogs({ page, ...Object.fromEntries(Object.entries(filters).filter(([,v]) => v)) }); logs.value = response.data; meta.value = response.meta; error.value=''; } catch(e) { error.value=apiErrorMessage(e); } finally { loading.value=false; } }
onMounted(async () => { try { users.value = await listUsers(); } catch { users.value = []; } await load(); });
</script>
<template>
    <AppLayout><div><p class="text-sm font-medium text-brand-700">Administration</p><h1 class="mt-1 text-3xl font-semibold">Audit log</h1><p class="mt-1 text-sm text-slate-500">A read only record of sensitive changes.</p></div>
        <form class="card mt-6 flex flex-wrap items-end gap-3" @submit.prevent="load()"><label><span class="label">User</span><select v-model="filters.actor_user_id" class="field"><option value="">All users</option><option v-for="user in users" :key="user.id" :value="String(user.id)">{{ user.name }}</option></select></label><label><span class="label">Action</span><input v-model="filters.action" class="field" placeholder="expense.updated" /></label><label><span class="label">Entity</span><select v-model="filters.subject_type" class="field"><option value="">All entities</option><option>Expense</option><option>User</option><option>Category</option><option>Attachment</option><option>ImportBatch</option></select></label><label><span class="label">From</span><DatePicker v-model="filters.date_from" aria-label="From date" placeholder="From" /></label><label><span class="label">To</span><DatePicker v-model="filters.date_to" aria-label="To date" placeholder="To" :min-date="filters.date_from || undefined" /></label><BaseButton type="submit">Filter</BaseButton></form>
        <p v-if="error" class="mt-5 rounded-lg bg-red-50 p-3 text-red-700">{{ error }}</p><LoadingState v-if="loading" label="Loading audit log…" />
        <section v-else class="mt-6 overflow-hidden rounded-xl border bg-white"><div class="divide-y"><article v-for="log in logs" :key="log.id" class="p-4"><div class="flex flex-wrap items-center justify-between gap-2"><strong class="text-sm">{{ log.action }}</strong><time class="text-xs text-slate-500">{{ formatDateTime(log.created_at) }}</time></div><p class="mt-1 text-sm text-slate-600">{{ log.actor?.name ?? 'System' }} · {{ log.subject_type }} #{{ log.subject_id }}</p><details v-if="log.old_values || log.new_values" class="mt-2 text-xs"><summary class="cursor-pointer text-brand-700">View changes</summary><pre class="mt-2 overflow-auto rounded bg-slate-950 p-3 text-slate-100">{{ JSON.stringify({ before: log.old_values, after: log.new_values }, null, 2) }}</pre></details></article><p v-if="!logs.length" class="p-8 text-center text-sm text-slate-500">No activity matches these filters.</p></div><footer class="flex justify-end gap-2 border-t p-3"><BaseButton variant="secondary" :disabled="meta.current_page === 1" @click="load(meta.current_page-1)">Previous</BaseButton><BaseButton variant="secondary" :disabled="meta.current_page === meta.last_page" @click="load(meta.current_page+1)">Next</BaseButton></footer></section>
    </AppLayout>
</template>
