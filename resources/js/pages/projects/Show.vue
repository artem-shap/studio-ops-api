<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import ClientController from '@/actions/App/Http/Controllers/ClientController';
import MilestoneController from '@/actions/App/Http/Controllers/MilestoneController';
import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate, formatMoney } from '@/lib/money';
import type { Milestone, ProjectDetail, Status } from '@/types/studio';

const props = defineProps<{
    project: ProjectDetail;
    milestones: Milestone[];
    milestoneStatuses: Status[];
    portalToken: string | null;
}>();

const portalUrl =
    props.portalToken === null ? null : `/portal/${props.portalToken}`;

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Projects', href: ProjectController.index().url },
        ],
    },
});
</script>

<template>
    <Head :title="project.title" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <!--
            Shown once, immediately after a conversion. Only the hash is stored,
            so once this render is gone the link cannot be recovered — it has to
            be revoked and reissued instead.
        -->
        <div
            v-if="portalUrl"
            class="rounded-xl border border-emerald-500/40 bg-emerald-50 p-4 dark:bg-emerald-400/10"
        >
            <h2 class="text-sm font-medium">Portal link, shown once</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Copy it now and send it to the client. Only its hash is stored,
                so it cannot be shown again.
            </p>
            <code
                class="mt-2 block overflow-x-auto rounded-md bg-background px-3 py-2 text-xs"
                >{{ portalUrl }}</code
            >
        </div>

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <h1 class="text-xl font-semibold">{{ project.title }}</h1>
                    <StatusBadge :status="project.status" />
                </div>
                <p class="text-sm text-muted-foreground">
                    <Link
                        :href="ClientController.edit(project.client.id).url"
                        class="hover:underline"
                    >
                        {{ project.client.company ?? project.client.name }}
                    </Link>
                </p>
            </div>
            <Button as-child size="sm" variant="outline">
                <Link :href="ProjectController.edit(project.id).url"
                    >Edit project</Link
                >
            </Button>
        </div>

        <dl class="grid gap-4 sm:grid-cols-3">
            <div
                class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <dt class="text-xs text-muted-foreground">Budget</dt>
                <dd class="mt-1 text-sm font-medium tabular-nums">
                    {{ formatMoney(project.budget_cents, project.currency) }}
                </dd>
            </div>
            <div
                class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <dt class="text-xs text-muted-foreground">Start</dt>
                <dd class="mt-1 text-sm font-medium">
                    {{ formatDate(project.start_date) }}
                </dd>
            </div>
            <div
                class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <dt class="text-xs text-muted-foreground">Due</dt>
                <dd class="mt-1 text-sm font-medium">
                    {{ formatDate(project.due_date) }}
                </dd>
            </div>
        </dl>

        <p
            v-if="project.description"
            class="max-w-2xl text-sm text-muted-foreground"
        >
            {{ project.description }}
        </p>

        <section class="space-y-3">
            <Heading
                variant="small"
                title="Milestones"
                description="What the client sees in their portal"
            />

            <EmptyState
                v-if="milestones.length === 0"
                title="No milestones yet"
                description="Add the first stage below. The client's portal shows this list, in this order."
            />

            <ul
                v-else
                class="divide-y divide-sidebar-border/40 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <li
                    v-for="(milestone, index) in milestones"
                    :key="milestone.id"
                    class="px-4 py-3"
                >
                    <Form
                        v-bind="
                            MilestoneController.update.form([
                                project.id,
                                milestone.id,
                            ])
                        "
                        class="flex flex-wrap items-center gap-3"
                        v-slot="{ errors, processing }"
                    >
                        <Input
                            :name="'title'"
                            :default-value="milestone.title"
                            class="min-w-40 flex-1"
                            :aria-label="`Milestone ${index + 1} title`"
                            required
                        />
                        <Input
                            name="due_date"
                            type="date"
                            :default-value="milestone.due_date ?? ''"
                            class="w-40"
                            :aria-label="`Milestone ${index + 1} due date`"
                        />
                        <select
                            name="status"
                            class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                            :aria-label="`Milestone ${index + 1} status`"
                        >
                            <option
                                v-for="status in milestoneStatuses"
                                :key="status.value"
                                :value="status.value"
                                :selected="
                                    milestone.status.value === status.value
                                "
                            >
                                {{ status.label }}
                            </option>
                        </select>
                        <Button
                            type="submit"
                            size="sm"
                            variant="outline"
                            :disabled="processing"
                            >Save</Button
                        >
                        <InputError
                            :message="
                                errors.title ?? errors.due_date ?? errors.status
                            "
                        />
                    </Form>

                    <div class="mt-2 flex items-center gap-1">
                        <Form
                            v-bind="
                                MilestoneController.move.form([
                                    project.id,
                                    milestone.id,
                                    'up',
                                ])
                            "
                        >
                            <Button
                                type="submit"
                                size="sm"
                                variant="ghost"
                                :disabled="index === 0"
                                aria-label="Move up"
                            >
                                Up
                            </Button>
                        </Form>
                        <Form
                            v-bind="
                                MilestoneController.move.form([
                                    project.id,
                                    milestone.id,
                                    'down',
                                ])
                            "
                        >
                            <Button
                                type="submit"
                                size="sm"
                                variant="ghost"
                                :disabled="index === milestones.length - 1"
                                aria-label="Move down"
                            >
                                Down
                            </Button>
                        </Form>
                        <Form
                            v-bind="
                                MilestoneController.destroy.form([
                                    project.id,
                                    milestone.id,
                                ])
                            "
                        >
                            <Button
                                type="submit"
                                size="sm"
                                variant="ghost"
                                aria-label="Remove milestone"
                            >
                                Remove
                            </Button>
                        </Form>
                    </div>
                </li>
            </ul>

            <Form
                v-bind="MilestoneController.store.form(project.id)"
                class="flex flex-wrap items-end gap-3 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                v-slot="{ errors, processing }"
                reset-on-success
            >
                <div class="grid min-w-48 flex-1 gap-2">
                    <Label for="new-milestone-title">New milestone</Label>
                    <Input
                        id="new-milestone-title"
                        name="title"
                        required
                        placeholder="Discovery and research"
                    />
                    <InputError :message="errors.title" />
                </div>
                <div class="grid gap-2">
                    <Label for="new-milestone-due">Due</Label>
                    <Input
                        id="new-milestone-due"
                        name="due_date"
                        type="date"
                        class="w-40"
                    />
                </div>
                <div class="grid gap-2">
                    <Label for="new-milestone-status">Status</Label>
                    <select
                        id="new-milestone-status"
                        name="status"
                        class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                    >
                        <option
                            v-for="status in milestoneStatuses"
                            :key="status.value"
                            :value="status.value"
                        >
                            {{ status.label }}
                        </option>
                    </select>
                </div>
                <Button type="submit" size="sm" :disabled="processing"
                    >Add</Button
                >
            </Form>
        </section>

        <section class="space-y-3">
            <Heading
                variant="small"
                title="Delete project"
                description="Its milestones go with it"
            />
            <Form
                v-bind="ProjectController.destroy.form(project.id)"
                v-slot="{ processing }"
            >
                <Button
                    type="submit"
                    variant="destructive"
                    size="sm"
                    :disabled="processing"
                    >Delete project</Button
                >
            </Form>
        </section>
    </div>
</template>
