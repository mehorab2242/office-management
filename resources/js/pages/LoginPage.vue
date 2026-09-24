<script setup lang="ts">
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Building2 } from '@lucide/vue';
import BaseButton from '../components/ui/BaseButton.vue';
import { useAuthStore } from '../stores/auth';
import { apiErrorMessage } from '../services/api';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();
const email = ref('');
const password = ref('');
const loading = ref(false);
const error = ref('');

async function submit(): Promise<void> {
    error.value = '';
    if (!email.value || !password.value) {
        error.value = 'Enter your email address and password.';
        return;
    }
    loading.value = true;
    try {
        await auth.signIn(email.value, password.value);
        const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/dashboard';
        await router.replace(redirect);
    } catch (caught) {
        error.value = apiErrorMessage(caught, 'The email or password is incorrect.');
    } finally {
        loading.value = false;
    }
}
</script>
<template>
    <main class="grid min-h-screen bg-slate-50 lg:grid-cols-2">
        <section class="hidden bg-slate-950 p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="flex items-center gap-3 font-semibold"><span class="rounded-lg bg-brand-600 p-2"><Building2 class="size-5" /></span>Office Cost Management</div>
            <div><p class="text-4xl font-semibold leading-tight">Clear spending records for every working month.</p><p class="mt-4 max-w-lg text-slate-400">Track expenses, payment status, and category totals from one secure workspace.</p></div>
            <p class="text-sm text-slate-500">Internal finance workspace</p>
        </section>
        <section class="flex items-center justify-center p-5 sm:p-10">
            <div class="w-full max-w-md">
                <div class="mb-8 flex items-center gap-3 lg:hidden"><span class="rounded-lg bg-brand-600 p-2 text-white"><Building2 class="size-5" /></span><span class="font-semibold">Office Costs</span></div>
                <h1 class="text-3xl font-semibold tracking-tight">Welcome back</h1>
                <p class="mt-2 text-slate-500">Sign in to manage office expenses.</p>
                <form class="mt-8 space-y-5" @submit.prevent="submit">
                    <div><label class="label" for="email">Email address</label><input id="email" v-model.trim="email" class="field" type="email" autocomplete="email" placeholder="name@company.com" /></div>
                    <div><label class="label" for="password">Password</label><input id="password" v-model="password" class="field" type="password" autocomplete="current-password" /></div>
                    <p v-if="error" class="rounded-lg bg-red-50 p-3 text-sm text-red-700" role="alert">{{ error }}</p>
                    <BaseButton type="submit" class="w-full" :loading="loading">Sign in</BaseButton>
                </form>
            </div>
        </section>
    </main>
</template>
