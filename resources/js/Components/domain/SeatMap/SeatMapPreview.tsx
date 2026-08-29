import { cn } from '@/Lib/utils';

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
    sections: Section[];
}

interface SeatMapPreviewProps {
    halls: Hall[];
    className?: string;
}

const typeColors: Record<string, string> = {
    standard: 'bg-blue-500 hover:bg-blue-600',
    vip: 'bg-yellow-500 hover:bg-yellow-600',
    premium: 'bg-purple-500 hover:bg-purple-600',
    wheelchair: 'bg-green-500 hover:bg-green-600',
};

const seatSize = 14;
const seatGap = 2;

export default function SeatMapPreview({
    halls,
    className,
}: SeatMapPreviewProps) {
    if (!halls || halls.length === 0) {
        return (
            <div className="border-border text-muted-foreground flex h-32 items-center justify-center rounded-lg border-2 border-dashed text-sm">
                No seating layout defined for this venue.
            </div>
        );
    }

    return (
        <div className={cn('space-y-8', className)}>
            {halls.map((hall) => (
                <div key={hall.id}>
                    <h4 className="mb-3 text-sm font-medium">{hall.name}</h4>
                    <div className="space-y-6">
                        {hall.sections.map((section) => (
                            <div
                                key={section.id}
                                className="border-border bg-card rounded-lg border p-4"
                            >
                                <div className="mb-2 flex items-center gap-2">
                                    <span
                                        className="h-3 w-3 rounded-full"
                                        style={{
                                            backgroundColor:
                                                section.color ?? '#3b82f6',
                                        }}
                                    />
                                    <span className="text-xs font-medium">
                                        {section.name}
                                    </span>
                                    <span className="bg-primary/10 text-primary rounded-full px-1.5 py-0.5 text-[10px]">
                                        {section.section_type}
                                    </span>
                                </div>
                                <div className="overflow-x-auto">
                                    {section.rows?.length > 0 && (
                                        <div className="inline-block">
                                            <div className="flex items-start gap-1">
                                                {/* Row labels */}
                                                <div
                                                    className="flex flex-col gap-[2px] pt-[2px]"
                                                    style={{
                                                        gap: `${seatGap}px`,
                                                    }}
                                                >
                                                    {section.rows.map((row) => (
                                                        <div
                                                            key={row.id}
                                                            className="flex items-center justify-end pr-1"
                                                            style={{
                                                                height: `${seatSize}px`,
                                                            }}
                                                        >
                                                            <span className="text-muted-foreground text-[10px] font-medium">
                                                                {row.label}
                                                            </span>
                                                        </div>
                                                    ))}
                                                </div>
                                                {/* Seat grid */}
                                                <div>
                                                    {section.rows.map((row) => (
                                                        <div
                                                            key={row.id}
                                                            className="flex gap-[2px]"
                                                            style={{
                                                                gap: `${seatGap}px`,
                                                            }}
                                                        >
                                                            {row.seats
                                                                ?.filter(
                                                                    (s) =>
                                                                        s.is_active,
                                                                )
                                                                .map((seat) => (
                                                                    <div
                                                                        key={
                                                                            seat.id
                                                                        }
                                                                        title={`${row.label}${seat.seat_number} (${seat.type})`}
                                                                        className={cn(
                                                                            'rounded-[2px] transition-colors',
                                                                            typeColors[
                                                                                seat
                                                                                    .type
                                                                            ] ??
                                                                                'bg-gray-400',
                                                                        )}
                                                                        style={{
                                                                            width: `${seatSize}px`,
                                                                            height: `${seatSize}px`,
                                                                        }}
                                                                    />
                                                                ))}
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            ))}

            <div className="text-muted-foreground flex flex-wrap gap-4 text-xs">
                <span className="flex items-center gap-1">
                    <span className="inline-block h-2.5 w-2.5 rounded bg-blue-500" />{' '}
                    Standard
                </span>
                <span className="flex items-center gap-1">
                    <span className="inline-block h-2.5 w-2.5 rounded bg-yellow-500" />{' '}
                    VIP
                </span>
                <span className="flex items-center gap-1">
                    <span className="inline-block h-2.5 w-2.5 rounded bg-purple-500" />{' '}
                    Premium
                </span>
                <span className="flex items-center gap-1">
                    <span className="inline-block h-2.5 w-2.5 rounded bg-green-500" />{' '}
                    Wheelchair
                </span>
            </div>
        </div>
    );
}
