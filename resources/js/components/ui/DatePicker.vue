<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import flatpickr from 'flatpickr';
import monthSelectPlugin from 'flatpickr/dist/plugins/monthSelect/index.js';
import 'flatpickr/dist/flatpickr.css';
import 'flatpickr/dist/plugins/monthSelect/style.css';

const props = withDefaults(defineProps<{
    modelValue: string;
    mode?: 'date' | 'month';
    minDate?: string;
}>(), { mode: 'date', minDate: undefined });

const emit = defineEmits<{ 'update:modelValue': [value: string] }>();
const input = ref<HTMLInputElement | null>(null);
let picker: ReturnType<typeof flatpickr> | null = null;

onMounted(() => {
    if (!input.value) return;

    const isInsideDialog = input.value.closest('[role="dialog"]') !== null;

    picker = flatpickr(input.value, {
        allowInput: true,
        static: isInsideDialog,
        dateFormat: props.mode === 'month' ? 'Y-m' : 'Y-m-d',
        altInput: true,
        altFormat: props.mode === 'month' ? 'F Y' : 'F j, Y',
        defaultDate: props.modelValue || undefined,
        minDate: props.minDate || undefined,
        plugins: props.mode === 'month' ? [monthSelectPlugin({ shorthand: true, dateFormat: 'Y-m', altFormat: 'F Y' })] : [],
        onChange: (_selectedDates, dateString) => emit('update:modelValue', dateString),
    });

    if (isInsideDialog) {
        picker.input.parentElement?.classList.add('w-full');
    }
});

watch(() => props.modelValue, (value) => {
    if (picker && value !== picker.input.value) picker.setDate(value || undefined, false);
});

watch(() => props.minDate, (value) => picker?.set('minDate', value || undefined));

onBeforeUnmount(() => picker?.destroy());
</script>

<template>
    <input ref="input" v-bind="$attrs" class="field min-w-0 max-w-full" :value="modelValue" />
</template>
