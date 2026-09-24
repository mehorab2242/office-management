<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import BaseButton from '../ui/BaseButton.vue';
import type { Category, Expense, ExpensePayload, ValidationErrors } from '../../types';

const props = withDefaults(defineProps<{
    expense?: Expense | null;
    categories: Category[];
    loading?: boolean;
    serverErrors?: ValidationErrors;
}>(), { expense: null, loading: false, serverErrors: () => ({}) });

const emit = defineEmits<{ submit: [payload: ExpensePayload] }>();
const localErrors = ref<ValidationErrors>({});
const form = reactive({
    period_month: '', expense_date: '', description: '', amount: '', category_id: '',
    payment_status: '', payment_method: '', reference: '', note: '',
});

watch(() => props.expense, (expense) => {
    if (!expense) return;
    form.period_month = expense.period_month.slice(0, 7);
    form.expense_date = expense.expense_date ?? '';
    form.description = expense.description;
    form.amount = expense.amount;
    form.category_id = expense.category ? String(expense.category.id) : '';
    form.payment_status = expense.payment_status ?? '';
    form.payment_method = expense.payment_method ?? '';
    form.reference = expense.reference ?? '';
    form.note = expense.note ?? '';
}, { immediate: true });

const errors = computed(() => ({ ...props.serverErrors, ...localErrors.value }));

function validate(): boolean {
    const next: ValidationErrors = {};
    if (!form.expense_date) next.expense_date = ['Choose an expense date.'];
    if (!form.description.trim()) next.description = ['Enter a description.'];
    if (!form.amount || Number(form.amount) <= 0) next.amount = ['Enter an amount greater than zero.'];
    localErrors.value = next;
    return Object.keys(next).length === 0;
}

function submit(): void {
    if (!validate()) return;
    const period = form.period_month || form.expense_date.slice(0, 7);
    emit('submit', {
        period_month: `${period}-01`, expense_date: form.expense_date,
        description: form.description.trim(), amount: Number(form.amount),
        category_id: Number(form.category_id) || null,
        payment_status: form.payment_status ? form.payment_status as ExpensePayload['payment_status'] : null,
        payment_method: form.payment_method.trim() || null,
        reference: form.reference.trim() || null, note: form.note.trim() || null,
    });
}

function errorFor(field: string): string | undefined { return errors.value[field]?.[0]; }
</script>
<template>
    <form class="space-y-6" @submit.prevent="submit">
        <div class="grid gap-5 sm:grid-cols-2">
            <div><label class="label" for="expense-date">Expense date *</label><input id="expense-date" v-model="form.expense_date" class="field" type="date" :aria-invalid="!!errorFor('expense_date')" /><p v-if="errorFor('expense_date')" class="error-text">{{ errorFor('expense_date') }}</p></div>
            <div><label class="label" for="period-month">Period month</label><input id="period-month" v-model="form.period_month" class="field" type="month" /><p v-if="errorFor('period_month')" class="error-text">{{ errorFor('period_month') }}</p></div>
        </div>
        <div><label class="label" for="description">Description *</label><input id="description" v-model="form.description" class="field" maxlength="255" placeholder="What was purchased?" /><p v-if="errorFor('description')" class="error-text">{{ errorFor('description') }}</p></div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div><label class="label" for="amount">Amount (BDT) *</label><input id="amount" v-model="form.amount" class="field" type="number" min="0.01" max="9999999999.99" step="0.01" placeholder="0.00" /><p v-if="errorFor('amount')" class="error-text">{{ errorFor('amount') }}</p></div>
            <div><label class="label" for="category">Category</label><select id="category" v-model="form.category_id" class="field"><option value="">Uncategorized</option><option v-for="category in categories.filter((item) => item.is_active)" :key="category.id" :value="category.id">{{ category.name }}</option></select><p v-if="errorFor('category_id')" class="error-text">{{ errorFor('category_id') }}</p></div>
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div><label class="label" for="status">Payment status</label><select id="status" v-model="form.payment_status" class="field"><option value="">Unspecified</option><option value="paid">Paid</option><option value="pending">Pending</option><option value="unpaid">Unpaid</option></select></div>
            <div><label class="label" for="method">Payment method</label><input id="method" v-model="form.payment_method" class="field" maxlength="100" placeholder="Cash, card, bank transfer…" /></div>
        </div>
        <div><label class="label" for="reference">Reference</label><input id="reference" v-model="form.reference" class="field" maxlength="255" placeholder="Invoice or transaction reference" /></div>
        <div><label class="label" for="note">Notes</label><textarea id="note" v-model="form.note" class="field min-h-28 resize-y" maxlength="5000" placeholder="Optional context" /></div>
        <p v-if="errorFor('payer_allocations')" class="error-text">{{ errorFor('payer_allocations') }}</p>
        <div class="flex flex-col-reverse gap-3 border-t pt-5 sm:flex-row sm:justify-end"><RouterLink :to="{ name: 'expenses' }" class="inline-flex min-h-10 items-center justify-center rounded-lg border bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</RouterLink><BaseButton type="submit" :loading="loading">{{ expense ? 'Save changes' : 'Create expense' }}</BaseButton></div>
    </form>
</template>
