import { router, usePage } from '@inertiajs/react';
import { Building2, Check, ChevronsUpDown } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

export default function OrganizationSwitcher() {
    const { auth } = usePage().props;
    const organizations = auth.user?.organizations ?? [];
    const currentOrgId = auth.current_organization_id;
    const currentOrg = organizations.find((o) => o.id === currentOrgId);
    const [open, setOpen] = useState(false);
    const ref = useRef<HTMLDivElement>(null);

    useEffect(() => {
        function handleClickOutside(event: MouseEvent) {
            if (ref.current && !ref.current.contains(event.target as Node)) {
                setOpen(false);
            }
        }
        document.addEventListener('mousedown', handleClickOutside);
        return () =>
            document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    if (organizations.length <= 1) return null;

    function switchOrganization(orgId: number) {
        router.put(
            route('organizations.switch', orgId),
            {},
            {
                preserveState: false,
                preserveScroll: true,
                onSuccess: () => {
                    window.location.reload();
                },
            },
        );
        setOpen(false);
    }

    return (
        <div ref={ref} className="relative">
            <button
                onClick={() => setOpen(!open)}
                className="border-border bg-background text-foreground hover:bg-muted/50 flex items-center gap-2 rounded-md border px-3 py-1.5 text-sm"
            >
                <Building2 className="text-muted-foreground h-4 w-4" />
                <span className="max-w-[140px] truncate">
                    {currentOrg?.name ?? 'Select Organization'}
                </span>
                <ChevronsUpDown className="text-muted-foreground h-3.5 w-3.5" />
            </button>

            {open && (
                <div className="border-border bg-popover absolute top-full right-0 z-50 mt-1 w-64 rounded-md border p-1 shadow-lg">
                    <p className="text-muted-foreground px-2 py-1.5 text-xs font-medium">
                        Switch Organization
                    </p>
                    {organizations.map((org) => (
                        <button
                            key={org.id}
                            onClick={() => switchOrganization(org.id)}
                            className="text-foreground hover:bg-muted flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm"
                        >
                            <Building2 className="text-muted-foreground h-4 w-4" />
                            <span className="flex-1 truncate text-left">
                                {org.name}
                            </span>
                            {org.id === currentOrgId && (
                                <Check className="text-primary h-4 w-4" />
                            )}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
