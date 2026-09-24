<script setup lang="ts">
defineProps<{ status: string | null }>();
</script>
<template>
    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="{
        'bg-emerald-50 text-emerald-700': status === 'paid',
        'bg-amber-50 text-amber-700': status === 'pending',
        'bg-red-50 text-red-700': status === 'unpaid',
        'bg-slate-100 text-slate-600': !status,
    }">{{ status ?? 'Unspecified' }}</span>
</template>
