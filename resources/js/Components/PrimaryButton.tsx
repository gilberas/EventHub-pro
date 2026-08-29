import { ButtonHTMLAttributes } from 'react';

export default function PrimaryButton({
    className = '',
    disabled,
    children,
    ...props
}: ButtonHTMLAttributes<HTMLButtonElement>) {
    return (
        <button
            {...props}
            className={
                `bg-primary text-primary-foreground hover:bg-primary/90 focus:ring-ring active:bg-primary/80 inline-flex items-center rounded-lg border border-transparent px-4 py-2 text-xs font-semibold tracking-widest uppercase shadow-sm transition duration-150 ease-in-out focus:ring-2 focus:ring-offset-2 focus:outline-none ${
                    disabled && 'cursor-not-allowed opacity-50'
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
