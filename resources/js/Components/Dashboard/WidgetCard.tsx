import { Card, CardContent } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';
import { cn } from '@/Lib/utils';
import { type ReactNode } from 'react';

interface WidgetCardProps {
    title: string;
    value?: string;
    description?: string;
    icon?: ReactNode;
    trend?: { value: string; positive: boolean };
    loading?: boolean;
    className?: string;
    children?: ReactNode;
}

export default function WidgetCard({
    title,
    value,
    description,
    icon,
    trend,
    loading = false,
    className,
    children,
}: WidgetCardProps) {
    if (loading) {
        return (
            <Card className={cn('', className)}>
                <CardContent className="p-6">
                    <div className="space-y-3">
                        <Skeleton className="h-4 w-1/2" />
                        <Skeleton className="h-8 w-3/4" />
                        <Skeleton className="h-3 w-1/3" />
                    </div>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card className={cn('', className)}>
            <CardContent className="p-6">
                {children ?? (
                    <div className="space-y-2">
                        <div className="flex items-center justify-between">
                            <h3 className="text-muted-foreground text-sm font-medium">
                                {title}
                            </h3>
                            {icon && (
                                <span className="text-muted-foreground">
                                    {icon}
                                </span>
                            )}
                        </div>
                        {value && (
                            <p className="text-2xl font-bold tracking-tight">
                                {value}
                            </p>
                        )}
                        {(description || trend) && (
                            <div className="flex items-center gap-2">
                                {description && (
                                    <p className="text-muted-foreground text-xs">
                                        {description}
                                    </p>
                                )}
                                {trend && (
                                    <span
                                        className={cn(
                                            'text-xs font-medium',
                                            trend.positive
                                                ? 'text-green-500'
                                                : 'text-red-500',
                                        )}
                                    >
                                        {trend.positive ? '↑' : '↓'}{' '}
                                        {trend.value}
                                    </span>
                                )}
                            </div>
                        )}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
