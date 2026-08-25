<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
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
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                title="Projects"
                description="Everything currently on the books"
            />
            <Button as-child size="sm">
                <Link :href="ProjectController.create().url">New project</Link>
            </Button>
        </div>

        <div v-if="projects.length > 0" class="flex flex-wrap gap-2">
            <Button
                size="sm"
                :variant="active === null ? 'secondary' : 'ghost'"
                @click="active = null"
            >
                All
            </Button>
            <Button
                v-for="status in statuses"
                :key="status.value"
                size="sm"
                :variant="active === status.value ? 'secondary' : 'ghost'"
                @click="active = status.value"
            >
                {{ status.label }}
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
            class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <table class="w-full text-sm">
                <caption class="sr-only">
                    Projects, their clients, status and milestone progress
                </caption>
                <thead
                    class="border-b border-sidebar-border/70 text-left dark:border-sidebar-border"
                >
                    <tr class="text-muted-foreground">
                        <th scope="col" class="px-4 py-3 font-medium">
                            Project
                        </th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Client
                        </th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Status
                        </th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Progress
                        </th>
                        <th scope="col" class="px-4 py-3 font-medium">Due</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="project in visible"
                        :key="project.id"
                        class="border-b border-sidebar-border/40 last:border-0 dark:border-sidebar-border/60"
                    >
                        <td class="px-4 py-3">
                            <Link
                                :href="ProjectController.show(project.id).url"
                                class="font-medium hover:underline"
                            >
                                {{ project.title }}
                            </Link>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ project.client.name }}
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge :status="project.status" />
                        </td>
                        <td
                            class="px-4 py-3 text-muted-foreground tabular-nums"
                        >
                            {{ project.done_milestones_count }} /
                            {{ project.milestones_count }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ formatDate(project.due_date) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
