import DashboardLayout from '@/Layouts/DashboardLayout';
import { useCallback, useRef, useState } from 'react';

type ScanResult = {
    valid: boolean;
    error?: string;
    checked_in_at?: string;
    ticket?: {
        id: number;
        ticket_number: string;
        status: string;
        checked_in_at: string | null;
        booking: { user: { name: string; email: string } } | null;
        booking_item: { ticket_type: { name: string } } | null;
        seat: { label: string; section: { row: { label: string } } } | null;
    };
};

export default function TicketScanner() {
    const [sessionId, setSessionId] = useState<string>('');
    const [payload, setPayload] = useState('');
    const [manualTicketNumber, setManualTicketNumber] = useState('');
    const [scanResult, setScanResult] = useState<ScanResult | null>(null);
    const [scanning, setScanning] = useState(false);
    const [mode, setMode] = useState<'scan' | 'manual'>('scan');
    const [sessionTickets, setSessionTickets] = useState<{
        session: {
            id: number;
            title: string;
            start_date: string;
            event: { title: string };
        } | null;
        tickets: Array<{
            id: number;
            ticket_number: string;
            status: string;
            checked_in_at: string | null;
            booking: { user: { name: string } } | null;
        }>;
        stats: { total: number; checked_in: number; active: number };
    } | null>(null);
    const payloadInputRef = useRef<HTMLInputElement>(null);

    const handleScan = useCallback(async () => {
        if (!payload || !sessionId) return;
        setScanning(true);
        setScanResult(null);

        try {
            const res = await fetch(route('scanner.scan'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (window as any).csrfToken,
                },
                body: JSON.stringify({
                    payload,
                    event_session_id: parseInt(sessionId),
                }),
            });
            const data = await res.json();
            setScanResult(data);
            if (data.valid) {
                setPayload('');
                if (payloadInputRef.current) payloadInputRef.current.focus();
            }
        } catch {
            setScanResult({ valid: false, error: 'Network error' });
        } finally {
            setScanning(false);
        }
    }, [payload, sessionId]);

    const handleManualCheckIn = useCallback(async () => {
        if (!manualTicketNumber || !sessionId) return;
        setScanning(true);
        setScanResult(null);

        try {
            const res = await fetch(route('scanner.manual-checkin'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (window as any).csrfToken,
                },
                body: JSON.stringify({
                    ticket_number: manualTicketNumber,
                    event_session_id: parseInt(sessionId),
                }),
            });
            const data = await res.json();
            setScanResult(data);
            if (data.valid) {
                setManualTicketNumber('');
            }
        } catch {
            setScanResult({ valid: false, error: 'Network error' });
        } finally {
            setScanning(false);
        }
    }, [manualTicketNumber, sessionId]);

    const loadSessionTickets = useCallback(async () => {
        if (!sessionId) return;
        try {
            const res = await fetch(
                route('scanner.session-tickets', {
                    session: parseInt(sessionId),
                }),
            );
            const data = await res.json();
            setSessionTickets(data);
        } catch {
            // ignore
        }
    }, [sessionId]);

    const clearResult = () => {
        setScanResult(null);
    };

    return (
        <DashboardLayout>
            <div className="mx-auto max-w-lg px-3 py-6">
                <h1 className="mb-4 text-xl font-bold">Ticket Scanner</h1>

                <div className="mb-4">
                    <label className="mb-1 block text-sm font-medium text-gray-700">
                        Event Session ID
                    </label>
                    <input
                        type="number"
                        value={sessionId}
                        onChange={(e) => setSessionId(e.target.value)}
                        className="w-full rounded border px-3 py-2 text-sm"
                        placeholder="Enter session ID..."
                    />
                </div>

                <div className="mb-4 flex gap-2">
                    <button
                        onClick={() => {
                            setMode('scan');
                            setScanResult(null);
                        }}
                        className={`flex-1 rounded py-2 text-sm font-medium ${mode === 'scan' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'}`}
                    >
                        Scan QR
                    </button>
                    <button
                        onClick={() => {
                            setMode('manual');
                            setScanResult(null);
                        }}
                        className={`flex-1 rounded py-2 text-sm font-medium ${mode === 'manual' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'}`}
                    >
                        Manual Entry
                    </button>
                    <button
                        onClick={() => {
                            loadSessionTickets();
                        }}
                        className="rounded bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200"
                    >
                        Stats
                    </button>
                </div>

                {mode === 'scan' && (
                    <div className="mb-4">
                        <div className="mb-3 flex aspect-square items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-gray-50">
                            <div className="text-center text-gray-400">
                                <svg
                                    className="mx-auto mb-2 h-16 w-16"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={1.5}
                                        d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"
                                    />
                                </svg>
                                <p className="text-sm">Scan QR Code</p>
                                <p className="mt-1 text-xs">
                                    (paste payload below)
                                </p>
                            </div>
                        </div>

                        <input
                            ref={payloadInputRef}
                            type="text"
                            value={payload}
                            onChange={(e) => setPayload(e.target.value)}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter') handleScan();
                            }}
                            className="mb-2 w-full rounded border px-3 py-3 font-mono text-sm"
                            placeholder="Paste scanned QR payload..."
                            autoFocus
                        />

                        <button
                            onClick={handleScan}
                            disabled={scanning || !payload || !sessionId}
                            className="w-full rounded-lg bg-blue-600 py-3 text-lg font-bold text-white disabled:opacity-50"
                        >
                            {scanning ? 'Verifying...' : 'Verify Ticket'}
                        </button>
                    </div>
                )}

                {mode === 'manual' && (
                    <div className="mb-4">
                        <label className="mb-1 block text-sm font-medium text-gray-700">
                            Ticket Number
                        </label>
                        <input
                            type="text"
                            value={manualTicketNumber}
                            onChange={(e) =>
                                setManualTicketNumber(
                                    e.target.value.toUpperCase(),
                                )
                            }
                            onKeyDown={(e) => {
                                if (e.key === 'Enter') handleManualCheckIn();
                            }}
                            className="mb-2 w-full rounded border px-3 py-3 font-mono text-sm"
                            placeholder="TKT-XXXXXXXXXX"
                            autoFocus
                        />
                        <button
                            onClick={handleManualCheckIn}
                            disabled={
                                scanning || !manualTicketNumber || !sessionId
                            }
                            className="w-full rounded-lg bg-blue-600 py-3 text-lg font-bold text-white disabled:opacity-50"
                        >
                            {scanning ? 'Checking...' : 'Check In'}
                        </button>
                    </div>
                )}

                {scanResult && (
                    <div
                        className={`rounded-xl p-6 text-center ${scanResult.valid ? 'border-2 border-green-500 bg-green-50' : 'border-2 border-red-500 bg-red-50'}`}
                    >
                        <div
                            className={`mb-3 text-6xl ${scanResult.valid ? 'text-green-600' : 'text-red-600'}`}
                        >
                            {scanResult.valid ? '✓' : '✗'}
                        </div>
                        <h2
                            className={`mb-2 text-2xl font-bold ${scanResult.valid ? 'text-green-800' : 'text-red-800'}`}
                        >
                            {scanResult.valid
                                ? 'ACCESS GRANTED'
                                : 'ACCESS DENIED'}
                        </h2>
                        <p
                            className={`mb-2 text-lg ${scanResult.valid ? 'text-green-700' : 'text-red-700'}`}
                        >
                            {scanResult.valid
                                ? 'Valid ticket — welcome!'
                                : scanResult.error}
                        </p>
                        {scanResult.checked_in_at && (
                            <p className="mb-2 text-sm text-red-600">
                                Previously checked in at:{' '}
                                {new Date(
                                    scanResult.checked_in_at,
                                ).toLocaleTimeString()}
                            </p>
                        )}
                        {scanResult.valid && scanResult.ticket && (
                            <div className="mt-3 rounded-lg bg-white p-3 text-left text-sm">
                                <p>
                                    <strong>Ticket:</strong>{' '}
                                    {scanResult.ticket.ticket_number}
                                </p>
                                <p>
                                    <strong>Holder:</strong>{' '}
                                    {scanResult.ticket.booking?.user?.name ??
                                        'N/A'}
                                </p>
                                <p>
                                    <strong>Type:</strong>{' '}
                                    {scanResult.ticket.booking_item?.ticket_type
                                        ?.name ?? 'N/A'}
                                </p>
                                {scanResult.ticket.seat && (
                                    <p>
                                        <strong>Seat:</strong>{' '}
                                        {
                                            scanResult.ticket.seat.section?.row
                                                ?.label
                                        }{' '}
                                        - {scanResult.ticket.seat.label}
                                    </p>
                                )}
                            </div>
                        )}
                        <button
                            onClick={clearResult}
                            className={`mt-4 rounded-lg px-6 py-2 text-sm font-medium ${scanResult.valid ? 'bg-green-600 text-white' : 'bg-red-600 text-white'}`}
                        >
                            Scan Next
                        </button>
                    </div>
                )}

                {sessionTickets && (
                    <div className="mt-4 rounded-lg border bg-white p-4">
                        <h3 className="mb-2 font-bold">
                            {sessionTickets.session?.event?.title} —{' '}
                            {(sessionTickets.session?.title ??
                            sessionTickets.session?.start_date)
                                ? new Date(
                                      sessionTickets.session.start_date,
                                  ).toLocaleDateString()
                                : ''}
                        </h3>
                        <div className="mb-3 flex gap-4 text-sm">
                            <span>
                                Total:{' '}
                                <strong>{sessionTickets.stats.total}</strong>
                            </span>
                            <span className="text-green-600">
                                In:{' '}
                                <strong>
                                    {sessionTickets.stats.checked_in}
                                </strong>
                            </span>
                            <span className="text-gray-500">
                                Remaining:{' '}
                                <strong>{sessionTickets.stats.active}</strong>
                            </span>
                        </div>
                    </div>
                )}
            </div>
        </DashboardLayout>
    );
}
