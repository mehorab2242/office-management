import { afterEach, beforeEach, vi } from 'vitest';
import { config } from '@vue/test-utils';

config.global.stubs = {
    RouterLink: { template: '<a><slot /></a>' },
    RouterView: { template: '<div />' },
};

beforeEach(() => {
    sessionStorage.clear();
});

afterEach(() => {
    vi.clearAllMocks();
});
