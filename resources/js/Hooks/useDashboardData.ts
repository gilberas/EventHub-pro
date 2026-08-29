import { useEffect, useState } from 'react';

export function useDashboardData<T>(endpoint: string): {
    data: T | null;
    loading: boolean;
    error: string | null;
} {
    const [data, setData] = useState<T | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        let cancelled = false;
        setLoading(true);
        setError(null);

        const timeout = setTimeout(() => {
            if (!cancelled) {
                setError('Request timed out');
                setLoading(false);
            }
        }, 15000);

        fetch(endpoint)
            .then((res) => {
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                return res.json();
            })
            .then((json) => {
                if (!cancelled) {
                    setData(json);
                    setLoading(false);
                }
            })
            .catch((err) => {
                if (!cancelled) {
                    setError(err.message);
                    setLoading(false);
                }
            })
            .finally(() => clearTimeout(timeout));

        return () => {
            cancelled = true;
            clearTimeout(timeout);
        };
    }, [endpoint]);

    return { data, loading, error };
}
