<script setup lang="ts">
import { X } from '@lucide/vue';
import {
    DialogClose, DialogContent, DialogDescription, DialogOverlay, DialogPortal,
    DialogRoot, DialogTitle,
} from 'reka-ui';

withDefaults(defineProps<{ open: boolean; title: string; description?: string; size?: 'lg' | '2xl' }>(), { size: 'lg' });
defineEmits<{ 'update:open': [value: boolean] }>();

function focusDialogTitle(event: Event): void {
    event.preventDefault();
    const dialog = event.target;
    if (dialog instanceof HTMLElement) {
        dialog.querySelector<HTMLElement>('[data-dialog-title]')?.focus({ preventScroll: true });
    }
}
</script>

<template>
    <DialogRoot :open="open" @update:open="$emit('update:open', $event)">
        <DialogPortal>
            <DialogOverlay class="fixed inset-0 z-40 bg-slate-950/50" />
            <DialogContent class="fixed left-1/2 top-1/2 z-50 max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] -translate-x-1/2 -translate-y-1/2 overflow-x-hidden overflow-y-auto overscroll-contain rounded-xl border bg-white p-4 sm:max-h-[90dvh] sm:p-6" :class="size === '2xl' ? 'max-w-2xl' : 'max-w-lg'" @open-auto-focus="focusDialogTitle">
                <DialogTitle data-dialog-title tabindex="-1" class="pr-8 text-lg font-semibold">{{ title }}</DialogTitle>
                <DialogDescription v-if="description" class="mt-1 text-sm text-slate-500">{{ description }}</DialogDescription>
                <slot />
                <DialogClose class="absolute right-4 top-4 rounded-md p-1 text-slate-500 hover:bg-slate-100" aria-label="Close dialog">
                    <X class="size-5" />
                </DialogClose>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
