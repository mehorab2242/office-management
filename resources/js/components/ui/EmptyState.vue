<script setup lang="ts">
import { Inbox } from '@lucide/vue';
defineProps<{ title: string; description: string }>();
</script>
<template>
    <div class="flex min-h-48 flex-col items-center justify-center p-6 text-center">
        <div class="rounded-full bg-slate-100 p-3"><Inbox class="size-6 text-slate-500" /></div>
        <h2 class="mt-3 font-semibold">{{ title }}</h2>
        <p class="mt-1 max-w-sm text-sm text-slate-500">{{ description }}</p>
        <div class="mt-4"><slot /></div>
    </div>
</template>
