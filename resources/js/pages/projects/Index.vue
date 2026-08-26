<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate } from '@/lib/money';
import type { ProjectRow, Status } from '@/types/studio';

const props = defineProps<{
    projects: ProjectRow[];
    statuses: Status[];
}>();

// Filtering happens in the browser: the studio runs ten to twenty projects, so
// a round trip per filter click would be slower and no more correct.
const active = ref<string | null>(null);

const visible = computed(() =>
    active.value === null
        ? props.projects
        : props.projects.filter(
              (project) => project.status.value === active.value,
          ),
);

const countFor = (value: string) =>
    props.projects.filter((project) => project.status.value === value).length;

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Projects', href: ProjectController.index().url },
        ],
    },
});
</script>

<template>
    <Head title="Projects" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Projects"
            description="Everything currently on the books"
        >
            <template #actions>
                <Button as-child size="sm">
                    <Link :href="ProjectController.create().url">
                        <Plus aria-hidden="true" />
                        New project
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <div v-if="projects.length > 0" class="flex flex-wrap gap-1.5">
            <Button
                size="sm"
                :variant="active === null ? 'secondary' : 'ghost'"
                @click="active = null"
            >
                All
                <span class="ml-1 text-muted-foreground tabular-nums">{{
                    projects.length
                }}</span>
            </Button>
            <Button
                v-for="status in statuses"
                :key="status.value"
                size="sm"
                :variant="active === status.value ? 'secondary' : 'ghost'"
                @click="active = status.value"
            >
                {{ status.label }}
                <span class="ml-1 text-muted-foreground tabular-nums">{{
                    countFor(status.value)
                }}</span>
            </Button>
        </div>

        <EmptyState
            v-if="projects.length === 0"
            title="No projects yet"
            description="Start one directly, or convert an inquiry from the inbox into a client and a project."
        >
            <Button as-child size="sm" variant="outline">
                <Link :href="ProjectController.create().url"
                    >Create your first project</Link
                >
            </Button>
        </EmptyState>

        <EmptyState
            v-else-if="visible.length === 0"
            title="Nothing with that status"
            description="No project is in this state right now."
        />

        <div
            v-else
            class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <Table>
                <TableCaption class="sr-only">
                    Projects, their clients, status and milestone progress
                </TableCaption>
                <TableHeader>
                    <TableRow class="hover:bg-transparent">
                        <TableHead>Project</TableHead>
                        <TableHead class="hidden md:table-cell"
                            >Client</TableHead
                        >
                        <TableHead class="w-28">Status</TableHead>
                        <TableHead class="w-40">Progress</TableHead>
                        <TableHead class="hidden w-32 sm:table-cell"
                            >Due</TableHead
                        >
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="project in visible" :key="project.id">
                        <TableCell>
                            <Link
                                :href="ProjectController.show(project.id).url"
                                class="font-medium hover:underline"
                            >
                                {{ project.title }}
                            </Link>
                            <p class="text-xs text-muted-foreground md:hidden">
                                {{ project.client.name }}
                            </p>
                        </TableCell>
                        <TableCell
                            class="hidden text-muted-foreground md:table-cell"
                        >
                            {{ project.client.name }}
                        </TableCell>
                        <TableCell
                            ><StatusBadge :status="project.status"
                        /></TableCell>
                        <TableCell>
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="h-1 w-16 overflow-hidden rounded-full bg-muted"
                                    role="progressbar"
                                    :aria-valuenow="
                                        project.milestones_count === 0
                                            ? 0
                                            : Math.round(
                                                  (project.done_milestones_count /
                                                      project.milestones_count) *
                                                      100,
                                              )
                                    "
                                    :aria-valuemin="0"
                                    :aria-valuemax="100"
                                    :aria-label="`${project.title} progress`"
                                >
                                    <div
                                        class="h-full rounded-full bg-foreground"
                                        :style="{
                                            width: `${project.milestones_count === 0 ? 0 : (project.done_milestones_count / project.milestones_count) * 100}%`,
                                        }"
                                    />
                                </div>
                                <span
                                    class="text-xs text-muted-foreground tabular-nums"
                                >
                                    {{ project.done_milestones_count }}/{{
                                        project.milestones_count
                                    }}
                                </span>
                            </div>
                        </TableCell>
                        <TableCell
                            class="hidden text-muted-foreground sm:table-cell"
                        >
                            {{ formatDate(project.due_date) }}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>
</template>
