<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowUpRight } from '@lucide/vue';
import InquiryController from '@/actions/App/Http/Controllers/InquiryController';
import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/money';
import { dashboard } from '@/routes';
import type { Status } from '@/types/studio';

type Stats = {
    openInquiries: number;
    activeProjects: number;
    projectsOnHold: number;
    overdueMilestones: number;
};

const props = defineProps<{
    stats: Stats;
    newInquiries: Array<{
        id: number;
        name: string;
        budget_range: string | null;
        received_at: string | null;
    }>;
    upcoming: Array<{
        id: number;
        title: string;
        project: { id: number; title: string };
        due_date: string | null;
        overdue: boolean;
        status: Status;
    }>;
}>();

const figures = [
    {
        label: 'Open inquiries',
        value: props.stats.openInquiries,
        href: InquiryController.index().url,
    },
    {
        label: 'Active projects',
        value: props.stats.activeProjects,
        href: ProjectController.index().url,
    },
    {
        label: 'On hold',
        value: props.stats.projectsOnHold,
        href: ProjectController.index().url,
    },
    {
        label: 'Overdue milestones',
        value: props.stats.overdueMilestones,
        href: ProjectController.index().url,
    },
];

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-8 p-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight">Today</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                What came in, what is running, and what is late.
            </p>
        </div>

        <dl
            class="grid gap-px overflow-hidden rounded-xl border border-sidebar-border/70 bg-sidebar-border/50 sm:grid-cols-2 lg:grid-cols-4 dark:border-sidebar-border"
        >
            <Link
                v-for="figure in figures"
                :key="figure.label"
                :href="figure.href"
                class="group flex flex-col gap-1 bg-background p-5 transition-colors hover:bg-sidebar-accent/40"
            >
                <dt
                    class="flex items-center gap-1 text-xs text-muted-foreground"
                >
                    {{ figure.label }}
                    <ArrowUpRight
                        class="size-3 opacity-0 transition-opacity group-hover:opacity-100"
                        aria-hidden="true"
                    />
                </dt>
                <dd
                    class="text-3xl font-semibold tabular-nums"
                    :class="
                        figure.label === 'Overdue milestones' &&
                        figure.value > 0
                            ? 'text-rose-600 dark:text-rose-400'
                            : ''
                    "
                >
                    {{ figure.value }}
                </dd>
            </Link>
        </dl>

        <div class="grid gap-6 lg:grid-cols-2">
            <section
                aria-labelledby="new-inquiries"
                class="flex flex-col gap-3"
            >
                <div class="flex items-baseline justify-between gap-4">
                    <h2 id="new-inquiries" class="text-sm font-medium">
                        Waiting on a reply
                    </h2>
                    <Button as-child size="sm" variant="ghost">
                        <Link :href="InquiryController.index().url"
                            >All inquiries</Link
                        >
                    </Button>
                </div>

                <ul
                    v-if="newInquiries.length > 0"
                    class="divide-y divide-sidebar-border/40 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <li
                        v-for="inquiry in newInquiries"
                        :key="inquiry.id"
                        class="flex items-center justify-between gap-4 px-4 py-3"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">
                                {{ inquiry.name }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ inquiry.received_at }}
                                <template v-if="inquiry.budget_range">
                                    · {{ inquiry.budget_range }}</template
                                >
                            </p>
                        </div>
                    </li>
                </ul>
                <p
                    v-else
                    class="rounded-xl border border-dashed border-sidebar-border/70 px-4 py-8 text-center text-sm text-muted-foreground dark:border-sidebar-border"
                >
                    Nothing waiting. The inbox is clear.
                </p>
            </section>

            <section aria-labelledby="upcoming" class="flex flex-col gap-3">
                <h2 id="upcoming" class="text-sm font-medium">Next up</h2>

                <ul
                    v-if="upcoming.length > 0"
                    class="divide-y divide-sidebar-border/40 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <li
                        v-for="milestone in upcoming"
                        :key="milestone.id"
                        class="flex items-center justify-between gap-4 px-4 py-3"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">
                                {{ milestone.title }}
                            </p>
                            <Link
                                :href="
                                    ProjectController.show(milestone.project.id)
                                        .url
                                "
                                class="truncate text-xs text-muted-foreground hover:underline"
                            >
                                {{ milestone.project.title }}
                            </Link>
                        </div>
                        <div class="flex shrink-0 items-center gap-3">
                            <StatusBadge :status="milestone.status" />
                            <span
                                class="w-24 text-right text-xs tabular-nums"
                                :class="
                                    milestone.overdue
                                        ? 'font-medium text-rose-600 dark:text-rose-400'
                                        : 'text-muted-foreground'
                                "
                            >
                                {{ formatDate(milestone.due_date) }}
                            </span>
                        </div>
                    </li>
                </ul>
                <p
                    v-else
                    class="rounded-xl border border-dashed border-sidebar-border/70 px-4 py-8 text-center text-sm text-muted-foreground dark:border-sidebar-border"
                >
                    No milestones scheduled.
                </p>
            </section>
        </div>
    </div>
</template>
