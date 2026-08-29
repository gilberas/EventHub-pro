import { cn } from '@/Lib/utils';
import { HTMLAttributes, forwardRef } from 'react';

interface BadgeProps extends HTMLAttributes<HTMLDivElement> {
    variant?: 'default' | 'secondary' | 'outline';
}

const Badge = forwardRef<HTMLDivElement, BadgeProps>(
    ({ className, variant = 'default', ...props }, ref) => {
        return (
            <div
                ref={ref}
                className={cn(
                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold transition-colors',
                    {
                        'bg-primary text-primary-foreground':
                            variant === 'default',
                        'bg-secondary text-secondary-foreground':
                            variant === 'secondary',
                        'border-border text-foreground border':
                            variant === 'outline',
                    },
                    className,
                )}
                {...props}
            />
        );
    },
);
Badge.displayName = 'Badge';

export { Badge, type BadgeProps };
