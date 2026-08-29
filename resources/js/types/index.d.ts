import { Config } from 'ziggy-js';

export interface Organization {
    id: number;
    name: string;
    slug: string;
}

export interface RoleInfo {
    id: number;
    name: string;
    organization_id: number | null;
}

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    roles: RoleInfo[];
    organizations: Organization[];
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User | null;
        current_organization_id?: number | null;
    };
    ziggy: Config & { location: string };
};
