import { createApp, watch } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import { router } from './router';
import { useAuthStore } from './stores/auth';

const app = createApp(App);
const pinia = createPinia();
app.use(pinia);

const auth = useAuthStore(pinia);
await auth.initialize();

watch(() => auth.isAuthenticated, (authenticated, previous) => {
    if (previous && !authenticated) router.replace({ name: 'login' });
});

app.use(router);
await router.isReady();
app.mount('#app');
