import type { PageProps } from '@inertiajs/core';
import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface SharedData extends PageProps {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
}

export interface User {
    user_id: number;
    first_name: string;
    last_name: string;
    middle_name: string;
    suffix: string;
    birth_date: string;
    sex: string;
    occupation: string;
    religion:string;
    email_address: string;
    phone_verified_at: string | null; 
    phone_number: string;
    profile_picture?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    user_type: string; // Added for role-based sidebar
    is_active: boolean;
    balance: number;
}


export type BreadcrumbItemType = BreadcrumbItem;
