<script setup lang="ts">
import { ref, watch } from 'vue';
import BaseDialog from '../ui/BaseDialog.vue';
import StatusBadge from '../ui/StatusBadge.vue';
import { formatDate, formatMoney } from '../../utils/formatters';
import { deleteAttachment, downloadAttachment, uploadAttachment } from '../../services/attachments';
import { apiErrorMessage } from '../../services/api';
import { useAuthStore } from '../../stores/auth';
import { useToast } from '../../composables/useToast';
import type { Attachment, Expense } from '../../types';
const props = defineProps<{ open: boolean; expense: Expense | null }>();
defineEmits<{ 'update:open': [value: boolean] }>();
const auth = useAuthStore(); const toast = useToast(); const attachments = ref<Attachment[]>([]); const uploading = ref(false);
watch(() => props.expense, (expense) => { attachments.value = [...(expense?.attachments ?? [])]; }, { immediate: true });
async function upload(event: Event): Promise<void> { const file = (event.target as HTMLInputElement).files?.[0]; if (!file || !props.expense) return; uploading.value = true; try { attachments.value.push(await uploadAttachment(props.expense.id, file)); toast.show('Attachment uploaded'); } catch (error) { toast.show('Unable to upload attachment', 'error', apiErrorMessage(error)); } finally { uploading.value = false; (event.target as HTMLInputElement).value = ''; } }
async function download(attachment: Attachment): Promise<void> { if (!props.expense) return; try { await downloadAttachment(props.expense.id, attachment); } catch (error) { toast.show('Unable to download attachment', 'error', apiErrorMessage(error)); } }
async function remove(attachment: Attachment): Promise<void> { if (!props.expense) return; try { await deleteAttachment(props.expense.id, attachment.id); attachments.value = attachments.value.filter(item => item.id !== attachment.id); toast.show('Attachment deleted'); } catch (error) { toast.show('Unable to delete attachment', 'error', apiErrorMessage(error)); } }
</script>
<template>
    <BaseDialog :open="open" title="Expense details" description="Full information recorded for this expense." @update:open="$emit('update:open', $event)">
        <dl v-if="expense" class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2"><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Description</dt><dd class="mt-1 break-words font-medium">{{ expense.description }}</dd></div>
            <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Amount</dt><dd class="mt-1 font-semibold">{{ formatMoney(expense.amount) }}</dd></div>
            <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Status</dt><dd class="mt-1"><StatusBadge :status="expense.payment_status" /></dd></div>
            <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Date</dt><dd class="mt-1 text-sm">{{ formatDate(expense.expense_date) }}</dd></div>
            <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Category</dt><dd class="mt-1 text-sm">{{ expense.category?.name ?? 'Uncategorized' }}</dd></div>
            <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Payment method</dt><dd class="mt-1 text-sm">{{ expense.payment_method ?? '—' }}</dd></div>
            <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Created by</dt><dd class="mt-1 text-sm">{{ expense.created_by?.name ?? '—' }}</dd></div>
            <div v-if="expense.reference" class="sm:col-span-2"><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Reference</dt><dd class="mt-1 break-words text-sm">{{ expense.reference }}</dd></div>
            <div v-if="expense.note" class="sm:col-span-2"><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Notes</dt><dd class="mt-1 break-words whitespace-pre-wrap text-sm">{{ expense.note }}</dd></div>
        </dl>
        <section v-if="expense" class="mt-6 border-t pt-5"><div class="flex flex-wrap items-center justify-between gap-3"><div class="min-w-0"><h3 class="font-semibold">Attachments</h3><p class="text-xs text-slate-500">Receipts and supporting documents</p></div><label v-if="auth.isSuperAdmin || expense.created_by?.id === auth.user?.id" class="shrink-0 cursor-pointer rounded-lg border px-3 py-2 text-sm font-medium hover:bg-slate-50"><input class="sr-only" type="file" accept="image/*,.pdf" :disabled="uploading" @change="upload" />{{ uploading ? 'Uploading…' : 'Upload' }}</label></div><div class="mt-3 space-y-2"><div v-for="attachment in attachments" :key="attachment.id" class="flex min-w-0 items-center justify-between gap-3 rounded-lg border p-3 text-sm"><button class="min-w-0 truncate text-left text-brand-700 hover:underline" @click="download(attachment)">{{ attachment.original_name }}</button><button v-if="auth.isSuperAdmin || expense.created_by?.id === auth.user?.id" class="shrink-0 text-xs text-red-700" @click="remove(attachment)">Delete</button></div><p v-if="!attachments.length" class="text-sm text-slate-500">No attachments.</p></div></section>
    </BaseDialog>
</template>
