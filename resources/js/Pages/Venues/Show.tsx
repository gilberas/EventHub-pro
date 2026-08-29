import WidgetCard from '@/Components/Dashboard/WidgetCard';
import SeatMapPreview from '@/Components/domain/SeatMap/SeatMapPreview';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    Building2,
    Copy,
    Globe,
    MapPin,
    Pencil,
    Phone,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';

interface Seat {
    id: number;
    seat_number: number;
    type: string;
    row_position: number;
    col_position: number;
    is_active: boolean;
}

interface Row {
    id: number;
    label: string;
    sort_order: number;
    seats: Seat[];
}

interface Section {
    id: number;
    name: string;
    section_type: string;
    color: string | null;
    rows: Row[];
}

interface Hall {
    id: number;
    name: string;
    description: string | null;
    capacity: number | null;
    sections: Section[];
}

interface Venue {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    address: string | null;
    city: string | null;
    state: string | null;
    country: string | null;
    zip: string | null;
    phone: string | null;
    website: string | null;
    latitude: string | null;
    longitude: string | null;
    is_active: boolean;
    halls: Hall[];
    organization_id: number;
}

interface Props {
    venue: Venue;
}

export default function VenueShow({ venue }: Props) {
    const [editing, setEditing] = useState(false);

    const { data, setData, patch, processing, errors } = useForm({
        name: venue.name,
        description: venue.description ?? '',
        address: venue.address ?? '',
        city: venue.city ?? '',
        state: venue.state ?? '',
        country: venue.country ?? '',
        zip: venue.zip ?? '',
        phone: venue.phone ?? '',
        website: venue.website ?? '',
        is_active: venue.is_active,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        patch(route('venues.update', venue.slug), {
            onSuccess: () => setEditing(false),
        });
    }

    function handleDuplicate() {
        router.post(route('venues.duplicate', venue.id));
    }

    function handleDelete() {
        if (confirm('Are you sure you want to delete this venue?')) {
            router.delete(route('venues.destroy', venue.slug));
        }
    }

    const totalSeats =
        venue.halls?.reduce(
            (sum, h) =>
                sum +
                (h.sections?.reduce(
                    (s, sec) =>
                        s +
                            sec.rows?.reduce(
                                (r, row) =>
                                    r +
                                    (row.seats?.filter((s) => s.is_active)
                                        .length ?? 0),
                                0,
                            ) ?? 0,
                    0,
                ) ?? 0),
            0,
        ) ?? 0;

    const totalHalls = venue.halls?.length ?? 0;
    const totalSections =
        venue.halls?.reduce((sum, h) => sum + (h.sections?.length ?? 0), 0) ??
        0;

    return (
        <DashboardLayout>
            <Head title={venue.name} />

            <div className="mb-6">
                <a
                    href={route('venues.index')}
                    className="text-muted-foreground hover:text-foreground mb-2 flex items-center gap-1 text-sm"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Back to Venues
                </a>
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="bg-primary/10 flex h-10 w-10 items-center justify-center rounded-lg">
                            <Building2 className="text-primary h-5 w-5" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight">
                                {venue.name}
                            </h1>
                            <p className="text-muted-foreground text-sm">
                                {[venue.city, venue.state, venue.country]
                                    .filter(Boolean)
                                    .join(', ') || 'No location'}
                            </p>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <button
                            onClick={() => setEditing(!editing)}
                            className="border-border hover:bg-muted flex items-center gap-1 rounded-md border px-3 py-2 text-sm font-medium"
                        >
                            <Pencil className="h-4 w-4" />
                            Edit
                        </button>
                        <button
                            onClick={handleDuplicate}
                            className="border-border hover:bg-muted flex items-center gap-1 rounded-md border px-3 py-2 text-sm font-medium"
                        >
                            <Copy className="h-4 w-4" />
                            Duplicate
                        </button>
                        <button
                            onClick={handleDelete}
                            className="border-border flex items-center gap-1 rounded-md border px-3 py-2 text-sm font-medium text-red-500 hover:bg-red-50"
                        >
                            <Trash2 className="h-4 w-4" />
                            Delete
                        </button>
                    </div>
                </div>
            </div>

            {editing && (
                <form onSubmit={handleSubmit} className="mb-8 space-y-6">
                    <WidgetCard title="Edit Venue">
                        <div className="space-y-4">
                            <div>
                                <label className="text-sm font-medium">
                                    Name
                                </label>
                                <input
                                    type="text"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                    className="border-border bg-background mt-1 w-full rounded-md border px-3 py-2 text-sm"
                                />
                                {errors.name && (
                                    <p className="mt-1 text-xs text-red-500">
                                        {errors.name}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className="text-sm font-medium">
                                    Description
                                </label>
                                <textarea
                                    value={data.description}
                                    onChange={(e) =>
                                        setData('description', e.target.value)
                                    }
                                    rows={3}
                                    className="border-border bg-background mt-1 w-full rounded-md border px-3 py-2 text-sm"
                                />
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="text-sm font-medium">
                                        Address
                                    </label>
                                    <input
                                        type="text"
                                        value={data.address}
                                        onChange={(e) =>
                                            setData('address', e.target.value)
                                        }
                                        className="border-border bg-background mt-1 w-full rounded-md border px-3 py-2 text-sm"
                                    />
                                </div>
                                <div>
                                    <label className="text-sm font-medium">
                                        City
                                    </label>
                                    <input
                                        type="text"
                                        value={data.city}
                                        onChange={(e) =>
                                            setData('city', e.target.value)
                                        }
                                        className="border-border bg-background mt-1 w-full rounded-md border px-3 py-2 text-sm"
                                    />
                                </div>
                                <div>
                                    <label className="text-sm font-medium">
                                        State
                                    </label>
                                    <input
                                        type="text"
                                        value={data.state}
                                        onChange={(e) =>
                                            setData('state', e.target.value)
                                        }
                                        className="border-border bg-background mt-1 w-full rounded-md border px-3 py-2 text-sm"
                                    />
                                </div>
                                <div>
                                    <label className="text-sm font-medium">
                                        Zip
                                    </label>
                                    <input
                                        type="text"
                                        value={data.zip}
                                        onChange={(e) =>
                                            setData('zip', e.target.value)
                                        }
                                        className="border-border bg-background mt-1 w-full rounded-md border px-3 py-2 text-sm"
                                    />
                                </div>
                            </div>
                            <div className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={data.is_active}
                                    onChange={(e) =>
                                        setData('is_active', e.target.checked)
                                    }
                                    className="border-border rounded"
                                />
                                <label className="text-sm font-medium">
                                    Active
                                </label>
                            </div>
                        </div>
                    </WidgetCard>
                    <div className="flex gap-2">
                        <button
                            type="submit"
                            disabled={processing}
                            className="bg-primary text-primary-foreground hover:bg-primary/90 rounded-md px-4 py-2 text-sm font-medium disabled:opacity-50"
                        >
                            {processing ? 'Saving...' : 'Save Changes'}
                        </button>
                        <button
                            type="button"
                            onClick={() => setEditing(false)}
                            className="border-border hover:bg-muted rounded-md border px-4 py-2 text-sm font-medium"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            )}

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <WidgetCard
                        title="Seat Map"
                        icon={<MapPin className="h-4 w-4" />}
                    >
                        <SeatMapPreview halls={venue.halls ?? []} />
                    </WidgetCard>
                </div>

                <div className="space-y-6">
                    <WidgetCard title="Venue Info">
                        <div className="space-y-3">
                            <div className="flex items-center gap-2 text-sm">
                                <MapPin className="text-muted-foreground h-4 w-4" />
                                <span>
                                    {[
                                        venue.address,
                                        venue.city,
                                        venue.state,
                                        venue.zip,
                                    ]
                                        .filter(Boolean)
                                        .join(', ') || '—'}
                                </span>
                            </div>
                            <div className="flex items-center gap-2 text-sm">
                                <Globe className="text-muted-foreground h-4 w-4" />
                                {venue.website ? (
                                    <a
                                        href={venue.website}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="text-primary hover:underline"
                                    >
                                        {venue.website}
                                    </a>
                                ) : (
                                    <span className="text-muted-foreground">
                                        —
                                    </span>
                                )}
                            </div>
                            <div className="flex items-center gap-2 text-sm">
                                <Phone className="text-muted-foreground h-4 w-4" />
                                <span>{venue.phone || '—'}</span>
                            </div>
                        </div>
                    </WidgetCard>

                    <WidgetCard title="Stats">
                        <div className="space-y-3">
                            <div className="flex justify-between text-sm">
                                <span className="text-muted-foreground">
                                    Halls
                                </span>
                                <span className="font-medium">
                                    {totalHalls}
                                </span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-muted-foreground">
                                    Sections
                                </span>
                                <span className="font-medium">
                                    {totalSections}
                                </span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-muted-foreground">
                                    Total Seats
                                </span>
                                <span className="font-medium">
                                    {totalSeats}
                                </span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-muted-foreground">
                                    Status
                                </span>
                                <span
                                    className={`font-medium ${venue.is_active ? 'text-green-500' : 'text-red-500'}`}
                                >
                                    {venue.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </div>
                        </div>
                    </WidgetCard>

                    {venue.halls?.map((hall) => (
                        <WidgetCard key={hall.id} title={hall.name}>
                            <div className="space-y-1 text-sm">
                                {hall.sections?.map((section) => (
                                    <div
                                        key={section.id}
                                        className="bg-muted/50 flex items-center justify-between rounded-md p-2"
                                    >
                                        <div className="flex items-center gap-2">
                                            <span
                                                className="h-2.5 w-2.5 rounded-full"
                                                style={{
                                                    backgroundColor:
                                                        section.color ??
                                                        '#3b82f6',
                                                }}
                                            />
                                            <span>{section.name}</span>
                                        </div>
                                        <span className="text-muted-foreground text-xs">
                                            {section.section_type} &middot;{' '}
                                            {section.rows?.length} rows
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </WidgetCard>
                    ))}
                </div>
            </div>
        </DashboardLayout>
    );
}
