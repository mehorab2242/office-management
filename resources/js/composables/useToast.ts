import { ref } from 'vue';

export interface ToastMessage {
    id: number;
    title: string;
    description?: string;
    variant: 'success' | 'error';
}

const messages = ref<ToastMessage[]>([]);
let nextId = 1;

export function useToast() {
    function show(title: string, variant: ToastMessage['variant'] = 'success', description?: string): void {
        const id = nextId++;
        messages.value.push({ id, title, description, variant });
        window.setTimeout(() => dismiss(id), 4500);
    }

    function dismiss(id: number): void {
        messages.value = messages.value.filter((message) => message.id !== id);
    }

    return { messages, show, dismiss };
}
