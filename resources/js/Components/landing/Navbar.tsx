import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function Navbar() {
    const { auth } = usePage().props;
    const [menuOpen, setMenuOpen] = useState(false);

    return (
        <nav
            className="fixed top-0 inset-x-0 z-50 border-b border-rim"
            style={{
                background: 'rgba(7,7,15,0.85)',
                backdropFilter: 'blur(16px)',
            }}
        >
            <div className="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
                {/* Logo */}
                <Link href={route('home')} className="flex items-center gap-2.5 shrink-0">
                    <div className="w-8 h-8 rounded-lg gradient-btn flex items-center justify-center">
                        <svg
                            viewBox="0 0 24 24"
                            fill="white"
                            className="w-4 h-4"
                        >
                            <path
                                d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"
                                stroke="white"
                                strokeWidth="2"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                fill="none"
                            />
                        </svg>
                    </div>
                    <span className="font-display text-lg font-semibold text-white tracking-tight">
                        EventHub<span className="gradient-text">Pro</span>
                    </span>
                </Link>

                {/* Desktop nav */}
                <div className="hidden md:flex items-center gap-1">
                    <Link
                        href={route('home')}
                        className="px-4 py-2 rounded-lg text-sm font-medium transition-colors text-white bg-white/8"
                    >
                        Discover
                    </Link>
                    {auth.user && (
                        <Link
                            href={route('bookings.index')}
                            className="px-4 py-2 rounded-lg text-sm font-medium transition-colors text-muted hover:text-white"
                        >
                            My Tickets
                        </Link>
                    )}
                </div>

                {/* Right side */}
                <div className="flex items-center gap-3">
                    {auth.user ? (
                        <Link
                            href={route('dashboard')}
                            className="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium border border-rim transition-colors hover:border-white/20 bg-purple/20 text-purple-glow"
                        >
                            <span className="w-1.5 h-1.5 rounded-full bg-current" />
                            {auth.user.name || 'Dashboard'}
                        </Link>
                    ) : (
                        <div className="hidden md:flex items-center gap-2">
                            <Link
                                href={route('login')}
                                className="px-4 py-2 rounded-lg text-sm font-medium text-muted hover:text-white transition-colors"
                            >
                                Log in
                            </Link>
                            <Link
                                href={route('register')}
                                className="gradient-btn px-5 py-2 rounded-xl text-sm font-semibold text-white"
                            >
                                Sign Up
                            </Link>
                        </div>
                    )}

                    {/* Mobile menu toggle */}
                    <button
                        className="md:hidden p-2 rounded-lg text-muted hover:text-white"
                        onClick={() => setMenuOpen(!menuOpen)}
                    >
                        <svg
                            viewBox="0 0 24 24"
                            className="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                        >
                            {menuOpen ? (
                                <path d="M18 6L6 18M6 6l12 12" />
                            ) : (
                                <path d="M3 12h18M3 6h18M3 18h18" />
                            )}
                        </svg>
                    </button>
                </div>
            </div>

            {/* Mobile menu */}
            {menuOpen && (
                <div
                    className="md:hidden border-t border-rim px-4 py-3 flex flex-col gap-1"
                    style={{ background: 'rgba(7,7,15,0.95)' }}
                >
                    <Link
                        href={route('home')}
                        className="px-4 py-2.5 rounded-lg text-sm text-left text-muted hover:text-white"
                        onClick={() => setMenuOpen(false)}
                    >
                        Discover
                    </Link>
                    {auth.user && (
                        <Link
                            href={route('bookings.index')}
                            className="px-4 py-2.5 rounded-lg text-sm text-left text-muted hover:text-white"
                            onClick={() => setMenuOpen(false)}
                        >
                            My Tickets
                        </Link>
                    )}
                    <div className="border-t border-rim mt-2 pt-2">
                        {auth.user ? (
                            <Link
                                href={route('dashboard')}
                                className="gradient-btn block px-4 py-2.5 rounded-lg text-sm text-center text-white font-semibold"
                                onClick={() => setMenuOpen(false)}
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={route('login')}
                                    className="block px-4 py-2.5 rounded-lg text-sm text-left text-muted hover:text-white"
                                    onClick={() => setMenuOpen(false)}
                                >
                                    Log in
                                </Link>
                                <Link
                                    href={route('register')}
                                    className="gradient-btn block px-4 py-2.5 rounded-lg text-sm text-center text-white font-semibold mt-1"
                                    onClick={() => setMenuOpen(false)}
                                >
                                    Sign Up
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            )}
        </nav>
    );
}
