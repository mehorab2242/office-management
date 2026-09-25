<script setup lang="ts">
import BaseDialog from '../ui/BaseDialog.vue';
import StatusBadge from '../ui/StatusBadge.vue';
import { formatDate, formatMoney } from '../../utils/formatters';
import type { Expense } from '../../types';
const props = defineProps<{ open: boolean; expense: Expense | null }>();
defineEmits<{ 'update:open': [value: boolean] }>();
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
    </BaseDialog>
</template>
