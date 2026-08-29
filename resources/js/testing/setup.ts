import '@testing-library/jest-dom/vitest';

import { vi } from 'vitest';

vi.mock('ziggy-js', () => ({
    default: () => '#',
}));

// @ts-expect-error - global route mock for tests
globalThis.route = () => '#';
