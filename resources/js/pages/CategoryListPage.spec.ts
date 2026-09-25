import { flushPromises, mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import CategoryListPage from './CategoryListPage.vue';
import * as categoryService from '../services/categories';
import { useAuthStore } from '../stores/auth';

vi.mock('../services/categories');

describe('CategoryListPage', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        useAuthStore().user = { id: 2, name: 'Jane Staff', email: 'jane@example.com', role: 'staff', is_active: true };
        vi.mocked(categoryService.listCategories).mockReset().mockResolvedValue([
            { id: 3, name: 'Office Supplies', description: null, is_active: true },
        ]);
    });

    it('shows category create, edit, and delete actions to staff', async () => {
        const wrapper = mount(CategoryListPage);
        await flushPromises();

        expect(wrapper.text()).toContain('Add category');
        expect(wrapper.find('[aria-label="Edit Office Supplies"]').exists()).toBe(true);
        expect(wrapper.find('[aria-label="Delete Office Supplies"]').exists()).toBe(true);
    });
});
