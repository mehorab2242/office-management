<script setup lang="ts">
import { CheckCircle2, CircleAlert, X } from '@lucide/vue';
import { useToast } from '../../composables/useToast';
const { messages, dismiss } = useToast();
</script>
<template>
    <div class="fixed right-4 top-4 z-[100] flex w-[calc(100%-2rem)] max-w-sm flex-col gap-2" aria-live="polite">
        <div v-for="message in messages" :key="message.id" class="flex gap-3 rounded-xl border bg-white p-4" role="status">
            <CheckCircle2 v-if="message.variant === 'success'" class="mt-0.5 size-5 shrink-0 text-emerald-600" />
            <CircleAlert v-else class="mt-0.5 size-5 shrink-0 text-red-600" />
            <div class="min-w-0 flex-1"><p class="text-sm font-semibold">{{ message.title }}</p><p v-if="message.description" class="mt-0.5 text-sm text-slate-500">{{ message.description }}</p></div>
            <button class="self-start rounded p-1 text-slate-400 hover:bg-slate-100" aria-label="Dismiss notification" @click="dismiss(message.id)"><X class="size-4" /></button>
        </div>
    </div>
</template>
