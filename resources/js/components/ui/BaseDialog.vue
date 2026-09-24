<script setup lang="ts">
import { X } from '@lucide/vue';
import {
    DialogClose, DialogContent, DialogDescription, DialogOverlay, DialogPortal,
    DialogRoot, DialogTitle,
} from 'reka-ui';

defineProps<{ open: boolean; title: string; description?: string }>();
defineEmits<{ 'update:open': [value: boolean] }>();
</script>

<template>
    <DialogRoot :open="open" @update:open="$emit('update:open', $event)">
        <DialogPortal>
            <DialogOverlay class="fixed inset-0 z-40 bg-slate-950/50" />
            <DialogContent class="fixed left-1/2 top-1/2 z-50 max-h-[90vh] w-[calc(100%-2rem)] max-w-lg -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded-xl border bg-white p-6">
                <DialogTitle class="pr-8 text-lg font-semibold">{{ title }}</DialogTitle>
                <DialogDescription v-if="description" class="mt-1 text-sm text-slate-500">{{ description }}</DialogDescription>
                <slot />
                <DialogClose class="absolute right-4 top-4 rounded-md p-1 text-slate-500 hover:bg-slate-100" aria-label="Close dialog">
                    <X class="size-5" />
                </DialogClose>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
