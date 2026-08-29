import { Link, usePage } from '@inertiajs/react';
import { Bell, User } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import OrganizationSwitcher from './OrganizationSwitcher';

export default function Topbar() {
    const { auth } = usePage().props;
    const user = auth.user;
    const [profileOpen, setProfileOpen] = useState(false);
    const profileRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        function handleClickOutside(event: MouseEvent) {
            if (
                profileRef.current &&
                !profileRef.current.contains(event.target as Node)
            ) {
                setProfileOpen(false);
            }
        }
        document.addEventListener('mousedown', handleClickOutside);
        return () =>
            document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    return (
        <header className="border-border bg-card flex h-14 items-center justify-between border-b px-4">
            <div className="flex items-center gap-3">
                <OrganizationSwitcher />
            </div>

            <div className="flex items-center gap-3">
                <button className="text-muted-foreground hover:bg-muted hover:text-foreground relative rounded-md p-2 transition-colors">
                    <Bell className="h-5 w-5" />
                    <span className="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-red-500" />
                </button>

                <div ref={profileRef} className="relative">
                    <button
                        onClick={() => setProfileOpen(!profileOpen)}
                        className="text-foreground hover:bg-muted flex items-center gap-2 rounded-md p-1.5 text-sm transition-colors"
                    >
                        <span className="bg-primary/10 text-primary flex h-7 w-7 items-center justify-center rounded-full text-xs font-medium">
                            {user?.name?.charAt(0).toUpperCase() ?? (
                                <User className="h-4 w-4" />
                            )}
                        </span>
                        <span className="hidden text-sm font-medium sm:inline">
                            {user?.name}
                        </span>
                    </button>

                    {profileOpen && (
                        <div className="border-border bg-popover absolute top-full right-0 z-50 mt-1 w-48 rounded-md border p-1 shadow-lg">
                            <div className="border-border border-b px-2 py-2">
                                <p className="text-foreground text-sm font-medium">
                                    {user?.name}
                                </p>
                                <p className="text-muted-foreground text-xs">
                                    {user?.email}
                                </p>
                            </div>
                            <Link
                                href="/profile"
                                className="text-foreground hover:bg-muted flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm"
                                onClick={() => setProfileOpen(false)}
                            >
                                <User className="h-4 w-4" />
                                Profile
                            </Link>
                            <Link
                                href={route('logout')}
                                method="post"
                                as="button"
                                className="text-destructive hover:bg-muted flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm"
                            >
                                Log Out
                            </Link>
                        </div>
                    )}
                </div>
            </div>
        </header>
    );
}
