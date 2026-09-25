import { defineComponent, ref } from 'vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import DatePicker from './DatePicker.vue';

describe('DatePicker', () => {
    it('keeps its calendar inside a dialog so dates remain interactive', () => {
        const TestDialog = defineComponent({
            components: { DatePicker },
            setup() {
                return { date: ref('2026-09-25') };
            },
            template: '<div role="dialog"><DatePicker v-model="date" /></div>',
        });
        const wrapper = mount(TestDialog);
        const dialog = wrapper.get('[role="dialog"]').element;
        const picker = wrapper.findComponent(DatePicker);
        const calendar = wrapper.get('.flatpickr-calendar').element;
        const selectableDay = calendar.querySelector<HTMLElement>('.flatpickr-day:not(.prevMonthDay):not(.nextMonthDay):not(.flatpickr-disabled):not(.selected)');

        expect(dialog.contains(calendar)).toBe(true);
        expect(selectableDay).not.toBeNull();
        selectableDay?.click();
        expect(picker.emitted('update:modelValue')).toHaveLength(1);
        expect(picker.emitted('update:modelValue')?.[0]?.[0]).not.toBe('2026-09-25');

        wrapper.unmount();
    });
});
