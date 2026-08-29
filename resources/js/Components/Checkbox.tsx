import { InputHTMLAttributes } from 'react';

export default function Checkbox({
    className = '',
    ...props
}: InputHTMLAttributes<HTMLInputElement>) {
    return (
        <input
            {...props}
            type="checkbox"
            className={
                'border-border bg-background text-primary focus:ring-ring rounded shadow-sm focus:ring-2 focus:ring-offset-1 ' +
                className
            }
        />
    );
}
