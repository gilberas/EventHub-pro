import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head } from '@inertiajs/react';
import EventForm from './EventForm';

interface HallOption {
    id: number;
    name: string;
}

export interface VenueOption {
    id: number;
    name: string;
    halls: HallOption[];
}

export default function CreateEvent({ venues }: { venues: VenueOption[] }) {
    return (
        <DashboardLayout>
            <Head title="Create Event" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold tracking-tight">
                    Create Event
                </h1>
                <p className="text-muted-foreground">
                    Set up a new event for your organization.
                </p>
            </div>

            <EventForm isEdit={false} venues={venues} />
        </DashboardLayout>
    );
}