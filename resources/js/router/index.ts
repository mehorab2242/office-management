import { createRouter, createWebHistory, type Router } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import LoginPage from '../pages/LoginPage.vue';
import DashboardPage from '../pages/DashboardPage.vue';
import ExpenseListPage from '../pages/ExpenseListPage.vue';
import ExpenseFormPage from '../pages/ExpenseFormPage.vue';
import CategoryListPage from '../pages/CategoryListPage.vue';
import ForbiddenPage from '../pages/ForbiddenPage.vue';
import NotFoundPage from '../pages/NotFoundPage.vue';
import ServerErrorPage from '../pages/ServerErrorPage.vue';
import MonthlyReportPage from '../pages/MonthlyReportPage.vue';
import YearlyReportPage from '../pages/YearlyReportPage.vue';
import CustomReportPage from '../pages/CustomReportPage.vue';
import ImportPage from '../pages/ImportPage.vue';
import UserListPage from '../pages/UserListPage.vue';
import AuditLogPage from '../pages/AuditLogPage.vue';

export function createAppRouter(): Router {
    const router = createRouter({
        history: createWebHistory(),
        routes: [
            { path: '/', redirect: '/dashboard' },
            { path: '/login', name: 'login', component: LoginPage, meta: { guest: true } },
            { path: '/dashboard', name: 'dashboard', component: DashboardPage, meta: { auth: true } },
            { path: '/expenses', name: 'expenses', component: ExpenseListPage, meta: { auth: true } },
            { path: '/expenses/create', name: 'expense-create', component: ExpenseFormPage, meta: { auth: true } },
            { path: '/expenses/:id/edit', name: 'expense-edit', component: ExpenseFormPage, meta: { auth: true } },
            { path: '/categories', name: 'categories', component: CategoryListPage, meta: { auth: true } },
            { path: '/reports', redirect: { name: 'report-monthly' } },
            { path: '/reports/monthly', name: 'report-monthly', component: MonthlyReportPage, meta: { auth: true, superAdmin: true } },
            { path: '/reports/yearly', name: 'report-yearly', component: YearlyReportPage, meta: { auth: true, superAdmin: true } },
            { path: '/reports/custom', name: 'report-custom', component: CustomReportPage, meta: { auth: true, superAdmin: true } },
            { path: '/imports', name: 'imports', component: ImportPage, meta: { auth: true, superAdmin: true } },
            { path: '/users', name: 'users', component: UserListPage, meta: { auth: true, superAdmin: true } },
            { path: '/audit-logs', name: 'audit-logs', component: AuditLogPage, meta: { auth: true, superAdmin: true } },
            { path: '/forbidden', name: 'forbidden', component: ForbiddenPage, meta: { auth: true } },
            { path: '/server-error', name: 'server-error', component: ServerErrorPage },
            { path: '/:pathMatch(.*)*', name: 'not-found', component: NotFoundPage },
        ],
    });

    router.beforeEach((to) => {
        const auth = useAuthStore();
        if (to.meta.auth && !auth.isAuthenticated) return { name: 'login', query: { redirect: to.fullPath } };
        if (to.meta.superAdmin && !auth.isSuperAdmin) return { name: 'forbidden' };
        if (to.meta.guest && auth.isAuthenticated) return { name: 'dashboard' };
        return true;
    });

    return router;
}

export const router = createAppRouter();
