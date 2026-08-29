import WidgetCard from '@/Components/Dashboard/WidgetCard';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { Building2, Copy, Eye, Plus } from 'lucide-react';
import { useState } from 'react';

interface SectionInput {
    name: string;
    rows: number;
    seats_per_row: number;
    seat_type: string;
    color: string;
}

interface HallInput {
    name: string;
    sections: SectionInput[];
}

interface Venue {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    city: string | null;
    state: string | null;
    country: string | null;
    is_active: boolean;
    halls: { id: number; name: string }[];
    total_seats?: number;
}

interface Props {
    venues: Venue[];
}

export default function VenueIndex({ venues }: Props) {
    const [showCreateForm, setShowCreateForm] = useState(false);
    const [halls, setHalls] = useState<HallInput[]>([
        {
            name: 'Main Hall',
            sections: [
                {
                    name: 'Floor',
                    rows: 10,
                    seats_per_row: 20,
                    seat_type: 'standard',
                    color: '#3b82f6',
                },
            ],
        },
    ]);

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        description: '',
        address: '',
        city: '',
        state: '',
        country: '',
        zip: '',
        phone: '',
        website: '',
        is_active: true,
        layout: [] as HallInput[],
    });

    function addHall() {
        setHalls([
            ...halls,
            {
                name: '',
                sections: [
                    {
                        name: '',
                        rows: 10,
                        seats_per_row: 20,
                        seat_type: 'standard',
                        color: '#3b82f6',
                    },
                ],
            },
        ]);
    }

    function removeHall(index: number) {
        setHalls(halls.filter((_, i) => i !== index));
    }

    function updateHall(index: number, field: keyof HallInput, value: string) {
        const updated = [...halls];
        (updated[index] as any)[field] = value;
        setHalls(updated);
    }

    function addSection(hallIndex: number) {
        const updated = [...halls];
        updated[hallIndex].sections.push({
            name: '',
            rows: 10,
            seats_per_row: 20,
            seat_type: 'standard',
            color: '#3b82f6',
        });
        setHalls(updated);
    }

    function removeSection(hallIndex: number, sectionIndex: number) {
        const updated = [...halls];
        updated[hallIndex].sections = updated[hallIndex].sections.filter(
            (_, i) => i !== sectionIndex,
        );
        setHalls(updated);
    }

    function updateSection(
        hallIndex: number,
        sectionIndex: number,
        field: keyof SectionInput,
        value: string | number,
    ) {
        const updated = [...halls];
        (updated[hallIndex].sections[sectionIndex] as any)[field] = value;
        setHalls(updated);
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(route('venues.store'), {
            data: { ...data, layout: halls },
            onSuccess: () => {
                reset();
                setShowCreateForm(false);
                setHalls([
                    {
                        name: 'Main Hall',
                        sections: [
                            {
                                name: 'Floor',
                                rows: 10,
                                seats_per_row: 20,
                                seat_type: 'standard',
                                color: '#3b82f6',
                            },
                        ],
                    },
                ]);
            },
        });
    }

    function handleDuplicate(venue: Venue) {
        router.post(route('venues.duplicate', venue.id));
    }

    return (
        <DashboardLayout>
            <Head title="Venues" />

            <div className="mb-6 flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Venues
                    </h1>
                    <p className="text-muted-foreground">
                        Manage your event venues and seating layouts.
                    </p>
                </div>
                <button
                    onClick={() => setShowCreateForm(!showCreateForm)}
                    className="bg-primary text-primary-foreground hover:bg-primary/90 flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium"
                >
                    <Plus className="h-4 w-4" />
                    New Venue
                </button>
            </div>

            {showCreateForm && (
                <form onSubmit={handleSubmit} className="mb-8 space-y-6">
                    <WidgetCard title="Venue Details">
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
                                        Country
                                    </label>
                                    <input
                                        type="text"
                                        value={data.country}
                                        onChange={(e) =>
                                            setData('country', e.target.value)
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
                                <div>
                                    <label className="text-sm font-medium">
                                        Website
                                    </label>
                                    <input
                                        type="url"
                                        value={data.website}
                                        onChange={(e) =>
                                            setData('website', e.target.value)
                                        }
                                        className="border-border bg-background mt-1 w-full rounded-md border px-3 py-2 text-sm"
                                    />
                                </div>
                            </div>
                        </div>
                    </WidgetCard>

                    <WidgetCard title="Seat Layout Designer">
                        <p className="text-muted-foreground mb-4 text-sm">
                            Define your venue halls, sections, and seating
                            grids.
                        </p>
                        <div className="space-y-6">
                            {halls.map((hall, hi) => (
                                <div
                                    key={hi}
                                    className="border-border rounded-lg border p-4"
                                >
                                    <div className="mb-4 flex items-center justify-between">
                                        <h3 className="text-sm font-medium">
                                            Hall {hi + 1}
                                        </h3>
                                        {halls.length > 1 && (
                                            <button
                                                type="button"
                                                onClick={() => removeHall(hi)}
                                                className="text-xs text-red-500 hover:text-red-700"
                                            >
                                                Remove
                                            </button>
                                        )}
                                    </div>
                                    <div className="mb-4">
                                        <label className="text-xs font-medium">
                                            Hall Name
                                        </label>
                                        <input
                                            type="text"
                                            value={hall.name}
                                            onChange={(e) =>
                                                updateHall(
                                                    hi,
                                                    'name',
                                                    e.target.value,
                                                )
                                            }
                                            className="border-border bg-background mt-1 w-full rounded-md border px-3 py-2 text-sm"
                                        />
                                    </div>
                                    {hall.sections.map((section, si) => (
                                        <div
                                            key={si}
                                            className="bg-muted/50 mb-3 rounded-md p-3"
                                        >
                                            <div className="mb-2 flex items-center justify-between">
                                                <span className="text-xs font-medium">
                                                    Section {si + 1}
                                                </span>
                                                {hall.sections.length > 1 && (
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            removeSection(
                                                                hi,
                                                                si,
                                                            )
                                                        }
                                                        className="text-xs text-red-500 hover:text-red-700"
                                                    >
                                                        Remove
                                                    </button>
                                                )}
                                            </div>
                                            <div className="grid gap-3 sm:grid-cols-5">
                                                <div>
                                                    <label className="text-muted-foreground text-xs">
                                                        Name
                                                    </label>
                                                    <input
                                                        type="text"
                                                        value={section.name}
                                                        onChange={(e) =>
                                                            updateSection(
                                                                hi,
                                                                si,
                                                                'name',
                                                                e.target.value,
                                                            )
                                                        }
                                                        className="border-border bg-background mt-0.5 w-full rounded-md border px-2 py-1 text-xs"
                                                    />
                                                </div>
                                                <div>
                                                    <label className="text-muted-foreground text-xs">
                                                        Rows
                                                    </label>
                                                    <input
                                                        type="number"
                                                        value={section.rows}
                                                        onChange={(e) =>
                                                            updateSection(
                                                                hi,
                                                                si,
                                                                'rows',
                                                                parseInt(
                                                                    e.target
                                                                        .value,
                                                                ) || 1,
                                                            )
                                                        }
                                                        min={1}
                                                        max={26}
                                                        className="border-border bg-background mt-0.5 w-full rounded-md border px-2 py-1 text-xs"
                                                    />
                                                </div>
                                                <div>
                                                    <label className="text-muted-foreground text-xs">
                                                        Seats/Row
                                                    </label>
                                                    <input
                                                        type="number"
                                                        value={
                                                            section.seats_per_row
                                                        }
                                                        onChange={(e) =>
                                                            updateSection(
                                                                hi,
                                                                si,
                                                                'seats_per_row',
                                                                parseInt(
                                                                    e.target
                                                                        .value,
                                                                ) || 1,
                                                            )
                                                        }
                                                        min={1}
                                                        max={50}
                                                        className="border-border bg-background mt-0.5 w-full rounded-md border px-2 py-1 text-xs"
                                                    />
                                                </div>
                                                <div>
                                                    <label className="text-muted-foreground text-xs">
                                                        Type
                                                    </label>
                                                    <select
                                                        value={
                                                            section.seat_type
                                                        }
                                                        onChange={(e) =>
                                                            updateSection(
                                                                hi,
                                                                si,
                                                                'seat_type',
                                                                e.target.value,
                                                            )
                                                        }
                                                        className="border-border bg-background mt-0.5 w-full rounded-md border px-2 py-1 text-xs"
                                                    >
                                                        <option value="standard">
                                                            Standard
                                                        </option>
                                                        <option value="vip">
                                                            VIP
                                                        </option>
                                                        <option value="premium">
                                                            Premium
                                                        </option>
                                                        <option value="wheelchair">
                                                            Wheelchair
                                                        </option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label className="text-muted-foreground text-xs">
                                                        Color
                                                    </label>
                                                    <input
                                                        type="color"
                                                        value={section.color}
                                                        onChange={(e) =>
                                                            updateSection(
                                                                hi,
                                                                si,
                                                                'color',
                                                                e.target.value,
                                                            )
                                                        }
                                                        className="border-border bg-background mt-0.5 h-7 w-full rounded-md border"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                    <button
                                        type="button"
                                        onClick={() => addSection(hi)}
                                        className="text-primary mt-2 text-xs hover:underline"
                                    >
                                        + Add Section
                                    </button>
                                </div>
                            ))}
                            <button
                                type="button"
                                onClick={addHall}
                                className="text-primary text-sm hover:underline"
                            >
                                + Add Hall
                            </button>
                        </div>
                    </WidgetCard>

                    <div className="flex gap-2">
                        <button
                            type="submit"
                            disabled={processing || !data.name}
                            className="bg-primary text-primary-foreground hover:bg-primary/90 rounded-md px-4 py-2 text-sm font-medium disabled:opacity-50"
                        >
                            {processing ? 'Creating...' : 'Create Venue'}
                        </button>
                        <button
                            type="button"
                            onClick={() => {
                                setShowCreateForm(false);
                                reset();
                            }}
                            className="border-border hover:bg-muted rounded-md border px-4 py-2 text-sm font-medium"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            )}

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {venues.length === 0 && !showCreateForm && (
                    <div className="border-border col-span-full flex flex-col items-center justify-center rounded-lg border-2 border-dashed p-12 text-center">
                        <Building2 className="text-muted-foreground mb-2 h-8 w-8" />
                        <p className="text-muted-foreground text-sm">
                            No venues yet. Create your first venue to get
                            started.
                        </p>
                    </div>
                )}
                {venues.map((venue) => (
                    <WidgetCard
                        key={venue.id}
                        title={venue.name}
                        icon={<Building2 className="h-4 w-4" />}
                    >
                        <div className="space-y-2">
                            <p className="text-muted-foreground text-xs">
                                {[venue.city, venue.state, venue.country]
                                    .filter(Boolean)
                                    .join(', ') || 'No location set'}
                            </p>
                            <p className="text-muted-foreground text-xs">
                                {venue.halls?.length ?? 0} hall(s)
                            </p>
                            <div className="flex gap-2 pt-2">
                                <a
                                    href={route('venues.show', venue.slug)}
                                    className="bg-primary/10 text-primary hover:bg-primary/20 flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium"
                                >
                                    <Eye className="h-3 w-3" />
                                    View
                                </a>
                                <button
                                    onClick={() => handleDuplicate(venue)}
                                    className="border-border hover:bg-muted flex items-center gap-1 rounded-md border px-2 py-1 text-xs font-medium"
                                >
                                    <Copy className="h-3 w-3" />
                                    Duplicate
                                </button>
                            </div>
                        </div>
                    </WidgetCard>
                ))}
            </div>
        </DashboardLayout>
    );
}
