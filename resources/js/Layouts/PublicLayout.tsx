import Navbar from '@/Components/landing/Navbar';
import { PropsWithChildren } from 'react';

export default function PublicLayout({ children }: PropsWithChildren) {
    return (
        <div className="bg-void text-foreground min-h-screen">
            <Navbar />
            <main className="flex-1 pt-16">{children}</main>
        </div>
    );
}
