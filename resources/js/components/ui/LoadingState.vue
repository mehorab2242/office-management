<script setup lang="ts">
import { LoaderCircle } from '@lucide/vue';
defineProps<{ label?: string }>();
</script>
<template>
    <div class="flex min-h-48 flex-col items-center justify-center gap-3 text-sm text-slate-500" role="status">
        <LoaderCircle class="size-6 animate-spin text-brand-600" />
        <span>{{ label ?? 'Loading…' }}</span>
    </div>
</template>
