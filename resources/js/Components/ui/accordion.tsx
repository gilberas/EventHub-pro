import { cn } from '@/Lib/utils';
import {
    createContext,
    useContext,
    useState,
    type ButtonHTMLAttributes,
    type HTMLAttributes,
    type ReactNode,
} from 'react';

type AccordionType = 'single' | 'multiple';

interface AccordionContextValue {
    type: AccordionType;
    openItems: string[];
    toggleItem: (value: string) => void;
}

const AccordionContext = createContext<AccordionContextValue | null>(null);

function useAccordion() {
    const context = useContext(AccordionContext);
    if (!context) {
        throw new Error(
            'Accordion components must be used within an Accordion',
        );
    }
    return context;
}

interface AccordionProps extends HTMLAttributes<HTMLDivElement> {
    type?: AccordionType;
    defaultValue?: string[];
    children: ReactNode;
}

function Accordion({
    type = 'single',
    defaultValue = [],
    className,
    children,
    ...props
}: AccordionProps) {
    const [openItems, setOpenItems] = useState<string[]>(defaultValue);

    const toggleItem = (value: string) => {
        setOpenItems((prev) => {
            if (type === 'single') {
                return prev.includes(value) ? [] : [value];
            }
            return prev.includes(value)
                ? prev.filter((item) => item !== value)
                : [...prev, value];
        });
    };

    return (
        <AccordionContext.Provider value={{ type, openItems, toggleItem }}>
            <div className={cn('space-y-2', className)} {...props}>
                {children}
            </div>
        </AccordionContext.Provider>
    );
}

interface AccordionItemProps extends HTMLAttributes<HTMLDivElement> {
    value: string;
}

function AccordionItem({
    value,
    className,
    children,
    ...props
}: AccordionItemProps) {
    const { openItems } = useAccordion();
    const isOpen = openItems.includes(value);

    return (
        <div
            className={cn(
                'border-border bg-card text-card-foreground rounded-lg border shadow-sm',
                className,
            )}
            data-state={isOpen ? 'open' : 'closed'}
            {...props}
        >
            {children}
        </div>
    );
}

interface AccordionTriggerProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    itemValue: string;
}

function AccordionTrigger({
    itemValue,
    className,
    children,
    ...props
}: AccordionTriggerProps) {
    const { openItems, toggleItem } = useAccordion();
    const isOpen = openItems.includes(itemValue);

    return (
        <button
            type="button"
            onClick={() => toggleItem(itemValue)}
            className={cn(
                'flex w-full items-center justify-between px-4 py-3 text-sm font-medium transition-all hover:underline [&[data-state=open]>svg]:rotate-180',
                className,
            )}
            data-state={isOpen ? 'open' : 'closed'}
            {...props}
        >
            {children}
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
                className="size-4 shrink-0 transition-transform duration-200"
            >
                <path d="m6 9 6 6 6-6" />
            </svg>
        </button>
    );
}

interface AccordionContentProps extends HTMLAttributes<HTMLDivElement> {
    itemValue: string;
}

function AccordionContent({
    itemValue,
    className,
    children,
    ...props
}: AccordionContentProps) {
    const { openItems } = useAccordion();
    const isOpen = openItems.includes(itemValue);

    return (
        <div
            className={cn(
                'overflow-hidden text-sm transition-all',
                isOpen ? 'max-h-screen' : 'max-h-0',
                className,
            )}
            data-state={isOpen ? 'open' : 'closed'}
            {...props}
        >
            <div className="px-4 pt-0 pb-3">{children}</div>
        </div>
    );
}

export { Accordion, AccordionContent, AccordionItem, AccordionTrigger };
