import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head } from '@inertiajs/react';
import { VenueOption } from './Create';
import EventForm from './EventForm';

interface EventSession {
    title: string | null;
    start_date: string;
    end_date: string;
    venue_id: number | null;
    location: string | null;
    capacity: number | null;
    ticket_types:
        | {
              name: string;
              price: string;
              description?: string | null;
              mode: string;
              quantity_available?: number | null;
              max_per_order?: number | null;
          }[]
        | null;
}

interface EditEventPageProps {
    event: {
        id: number;
        title: string;
        description: string | null;
        category: string | null;
        tags: string[] | null;
        status: string;
        is_featured: boolean;
        age_restriction: number | null;
        refund_policy_days: number | null;
        refund_policy_percentage: number | null;
        cover_url: string | null;
        sessions: EventSession[] | null;
    };
    venues: VenueOption[];
}

export default function EditEvent({ event, venues }: EditEventPageProps) {
    return (
        <DashboardLayout>
            <Head title={`Edit ${event.title}`} />

            <div className="mb-6">
                <h1 className="text-2xl font-bold tracking-tight">
                    Edit Event
                </h1>
                <p className="text-muted-foreground">
                    Update the details of "{event.title}".
                </p>
            </div>

            <EventForm isEdit initial={event} venues={venues} />
        </DashboardLayout>
    );
}