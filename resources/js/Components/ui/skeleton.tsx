import { cn } from '@/Lib/utils';
import { HTMLAttributes, forwardRef } from 'react';

const Skeleton = forwardRef<HTMLDivElement, HTMLAttributes<HTMLDivElement>>(
    ({ className, ...props }, ref) => (
        <div
            ref={ref}
            className={cn('bg-muted animate-pulse rounded-md', className)}
            {...props}
        />
    ),
);
Skeleton.displayName = 'Skeleton';

export { Skeleton };
