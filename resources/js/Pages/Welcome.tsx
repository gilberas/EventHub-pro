import HomePage from '@/Components/landing/HomePage';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head } from '@inertiajs/react';

export interface PublicEvent {
    id: number;
    organization_id: number;
    title: string;
    slug: string;
    description: string | null;
    category: string | null;
    tags: string[] | null;
    status: string;
    is_featured: boolean;
    trending_score: number | null;
    cover_url: string | null;
    organization_name: string | null;
    next_session_date: string | null;
}

interface WelcomeProps {
    featuredEvents: PublicEvent[];
    trendingEvents: PublicEvent[];
}

export default function Welcome({
    featuredEvents,
    trendingEvents,
}: WelcomeProps) {
    return (
        <PublicLayout>
            <Head>
                <title>EventHub-Pro — Discover events and book tickets</title>
                <meta
                    name="description"
                    content="Discover concerts, festivals, conferences and workshops near you. Book tickets in seconds and get an instant digital QR ticket."
                />
                <meta
                    property="og:title"
                    content="EventHub-Pro — Discover events and book tickets"
                />
                <meta
                    property="og:description"
                    content="Curated concerts, festivals, conferences and workshops. Instant digital tickets with secure QR entry."
                />
                <meta property="og:type" content="website" />
                <meta name="twitter:card" content="summary_large_image" />
            </Head>
            <HomePage
                featuredEvents={featuredEvents}
                trendingEvents={trendingEvents}
            />
        </PublicLayout>
    );
}
