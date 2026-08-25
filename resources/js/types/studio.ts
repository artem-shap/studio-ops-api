/**
 * Shapes the admin panel receives from Inertia.
 *
 * Statuses arrive with their label and colour already resolved by the PHP enum,
 * so nothing here re-derives them. A second status-to-colour map is the exact
 * thing this shape exists to prevent.
 */

export type StatusColor = 'slate' | 'blue' | 'amber' | 'emerald' | 'rose';

export type Status = {
    value: string;
    label: string;
    color: StatusColor;
};

export type ClientRow = {
    id: number;
    name: string;
    email: string;
    company: string | null;
    projects_count: number;
    has_portal_access: boolean;
};

export type ClientDetail = {
    id: number;
    name: string;
    email: string;
    company: string | null;
    phone: string | null;
    has_portal_access: boolean;
    portal_expires_at: string | null;
};

export type ProjectSummary = {
    id: number;
    title: string;
    status: Status;
};

export type ProjectRow = {
    id: number;
    title: string;
    client: { id: number; name: string };
    status: Status;
    due_date: string | null;
    milestones_count: number;
    done_milestones_count: number;
};

export type ProjectDetail = {
    id: number;
    title: string;
    description: string | null;
    status: Status;
    budget_cents: number | null;
    currency: string;
    start_date: string | null;
    due_date: string | null;
    client: { id: number; name: string; company: string | null };
};

export type ProjectFormValues = {
    id: number;
    client_id: number;
    title: string;
    description: string | null;
    status: string;
    budget: number | null;
    currency: string;
    start_date: string | null;
    due_date: string | null;
};

export type Milestone = {
    id: number;
    title: string;
    due_date: string | null;
    status: Status;
};

export type InquiryRow = {
    id: number;
    name: string;
    email: string;
    company: string | null;
    message: string;
    budget_range: string | null;
    received_at: string | null;
    status: Status;
    converted_project: { id: number; title: string } | null;
};

export type SelectOption = {
    value: string;
    label: string;
};
