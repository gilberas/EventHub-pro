import ThemeToggle from '@/Components/ThemeToggle';
import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const localStorageMock = (() => {
    let store: Record<string, string> = {};
    return {
        getItem: vi.fn((key: string) => store[key] ?? null),
        setItem: vi.fn((key: string, value: string) => {
            store[key] = value;
        }),
        removeItem: vi.fn((key: string) => {
            delete store[key];
        }),
        clear: vi.fn(() => {
            store = {};
        }),
    };
})();

Object.defineProperty(window, 'localStorage', {
    value: localStorageMock,
    configurable: true,
});

beforeEach(() => {
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

describe('ThemeToggle', () => {
    beforeEach(() => {
        document.documentElement.classList.remove('dark', 'light');
        localStorageMock.clear();
    });

    afterEach(() => {
        cleanup();
    });

    it('renders a toggle button', () => {
        render(<ThemeToggle />);
        expect(screen.getByRole('button')).toBeInTheDocument();
    });

    it('clicking toggles dark class on html element', () => {
        render(<ThemeToggle />);
        const button = screen.getByRole('button');

        expect(document.documentElement.classList.contains('dark')).toBe(false);
        fireEvent.click(button);
        expect(document.documentElement.classList.contains('dark')).toBe(true);
        fireEvent.click(button);
        expect(document.documentElement.classList.contains('dark')).toBe(false);
    });

    it('clicking toggles localStorage theme value', () => {
        render(<ThemeToggle />);
        const button = screen.getByRole('button');

        fireEvent.click(button);
        expect(localStorageMock.setItem).toHaveBeenCalledWith('theme', 'light');
        fireEvent.click(button);
        expect(localStorageMock.setItem).toHaveBeenCalledWith('theme', 'dark');
    });

    it('shows correct aria-label', () => {
        render(<ThemeToggle />);
        const button = screen.getByRole('button');
        expect(button).toHaveAttribute('aria-label', 'Toggle theme');
    });
});
