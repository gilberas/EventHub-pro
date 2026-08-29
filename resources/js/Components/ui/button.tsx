import { cn } from '@/Lib/utils';
import {
    ButtonHTMLAttributes,
    Children,
    cloneElement,
    forwardRef,
    isValidElement,
} from 'react';

const buttonVariants = {
    default: 'bg-primary text-primary-foreground shadow hover:bg-primary/90',
    destructive:
        'bg-destructive text-destructive-foreground shadow-sm hover:bg-destructive/90',
    outline:
        'border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground',
    secondary:
        'bg-secondary text-secondary-foreground shadow-sm hover:bg-secondary/80',
    ghost: 'hover:bg-accent hover:text-accent-foreground',
    link: 'text-primary underline-offset-4 hover:underline',
} as const;

const buttonSizes = {
    default: 'h-9 px-4 py-2',
    sm: 'h-8 rounded-md px-3 text-xs',
    lg: 'h-10 rounded-md px-8',
    icon: 'h-9 w-9',
} as const;

const baseClasses =
    'inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50';

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: keyof typeof buttonVariants;
    size?: keyof typeof buttonSizes;
    asChild?: boolean;
}

const Button = forwardRef<HTMLButtonElement, ButtonProps>(
    (
        {
            className,
            variant = 'default',
            size = 'default',
            asChild = false,
            ...props
        },
        ref,
    ) => {
        const classes = cn(
            baseClasses,
            buttonVariants[variant],
            buttonSizes[size],
            className,
        );

        if (asChild) {
            const child = Children.only(props.children);
            if (isValidElement<{ className?: string }>(child)) {
                return cloneElement(child, {
                    className: cn(child.props.className, classes),
                });
            }
            return null;
        }

        return <button className={classes} ref={ref} {...props} />;
    },
);
Button.displayName = 'Button';

export { Button, buttonSizes, buttonVariants };
export type { ButtonProps };
