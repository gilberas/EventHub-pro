import { Eye, EyeOff } from 'lucide-react';
import {
    forwardRef,
    InputHTMLAttributes,
    useEffect,
    useRef,
    useState,
} from 'react';

export default forwardRef(function TextInput(
    {
        type = 'text',
        className = '',
        isFocused = false,
        showPasswordToggle = false,
        ...props
    }: InputHTMLAttributes<HTMLInputElement> & {
        isFocused?: boolean;
        showPasswordToggle?: boolean;
    },
    ref,
) {
    const localRef = useRef<HTMLInputElement>(null);
    const [showPassword, setShowPassword] = useState(false);
    const resolvedType = showPasswordToggle && showPassword ? 'text' : type;

    const mergedRef = (node: HTMLInputElement | null) => {
        localRef.current = node;
        if (typeof ref === 'function') {
            ref(node);
        } else if (ref) {
            (ref as React.MutableRefObject<HTMLInputElement | null>).current =
                node;
        }
    };

    useEffect(() => {
        if (isFocused) {
            localRef.current?.focus();
        }
    }, [isFocused]);

    return (
        <div className="relative">
            <input
                {...props}
                type={resolvedType}
                className={
                    'bg-background text-foreground placeholder:text-muted-foreground focus:ring-ring rounded-lg border px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-offset-1 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50 ' +
                    (showPasswordToggle ? 'pr-10' : '') +
                    className
                }
                ref={mergedRef}
            />

            {showPasswordToggle && (
                <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="text-muted-foreground hover:text-foreground focus:ring-ring absolute inset-y-0 right-0 flex items-center rounded-r-lg pr-3 focus:ring-2 focus:ring-offset-1 focus:outline-none"
                    aria-label={
                        showPassword ? 'Hide password' : 'Show password'
                    }
                    tabIndex={-1}
                >
                    {showPassword ? (
                        <EyeOff className="h-4 w-4" />
                    ) : (
                        <Eye className="h-4 w-4" />
                    )}
                </button>
            )}
        </div>
    );
});
