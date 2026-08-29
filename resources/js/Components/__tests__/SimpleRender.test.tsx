import PublicLayout from '@/Layouts/PublicLayout';
import { render } from '@testing-library/react';
import { beforeAll, describe, expect, it, vi } from 'vitest';

beforeAll(() => {
    window.matchMedia = vi.fn().mockImplementation((query: string) => ({
        matches: false,
        media: query,
        onchange: null,
        addListener: vi.fn(),
        removeListener: vi.fn(),
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
        dispatchEvent: vi.fn(),
    }));
});

vi.mock('@inertiajs/react', () => ({
    Link: ({ children, href, ...props }: any) => (
        <a href={href} {...props}>
            {children}
        </a>
    ),
    usePage: () => ({ props: { auth: { user: null } } }),
}));

vi.mock('ziggy-js', () => ({
    default: () => '#',
    route: () => '#',
}));

describe('PublicLayout', () => {
    it('renders without throwing', () => {
        const { container } = render(
            <PublicLayout>
                <div>Test child</div>
            </PublicLayout>,
        );
        expect(container).toBeInTheDocument();
    });
});
