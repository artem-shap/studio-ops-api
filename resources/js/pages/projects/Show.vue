<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, Copy, Pencil, Plus, Trash2 } from '@lucide/vue';
import ClientController from '@/actions/App/Http/Controllers/ClientController';
import MilestoneController from '@/actions/App/Http/Controllers/MilestoneController';
import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
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

const done = props.milestones.filter((m) => m.status.value === 'done').length;
const percent =
    props.milestones.length === 0
        ? 0
        : Math.round((done / props.milestones.length) * 100);

const facts = [
    {
        label: 'Budget',
        value: formatMoney(props.project.budget_cents, props.project.currency),
    },
    { label: 'Start', value: formatDate(props.project.start_date) },
    { label: 'Due', value: formatDate(props.project.due_date) },
    { label: 'Progress', value: `${done} of ${props.milestones.length}` },
];

function copyPortalLink() {
    if (portalUrl) {
        navigator.clipboard.writeText(
            window.location.origin.replace(/:\d+$/, '') + portalUrl,
        );
    }
}

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
            class="flex flex-col gap-3 rounded-xl border border-emerald-500/40 bg-emerald-50/60 p-4 dark:bg-emerald-400/10"
        >
            <div>
                <h2 class="text-sm font-medium">Portal link, shown once</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Copy it now and send it to the client. Only its hash is
                    stored, so it cannot be shown again.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <code
                    class="flex-1 overflow-x-auto rounded-md bg-background px-3 py-2 text-xs"
                >
                    {{ portalUrl }}
                </code>
                <Button size="sm" variant="outline" @click="copyPortalLink">
                    <Copy aria-hidden="true" />
                    Copy
                </Button>
            </div>
        </div>

        <PageHeader :title="project.title">
            <template #actions>
                <Button as-child size="sm" variant="outline">
                    <Link :href="ProjectController.edit(project.id).url">
                        <Pencil aria-hidden="true" />
                        Edit
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <div class="flex flex-wrap items-center gap-3">
            <StatusBadge :status="project.status" />
            <Link
                :href="ClientController.edit(project.client.id).url"
                class="text-sm text-muted-foreground hover:underline"
            >
                {{ project.client.company ?? project.client.name }}
            </Link>
        </div>

        <dl
            class="grid gap-px overflow-hidden rounded-xl border border-sidebar-border/70 bg-sidebar-border/60 sm:grid-cols-2 lg:grid-cols-4 dark:border-sidebar-border"
        >
            <div
                v-for="fact in facts"
                :key="fact.label"
                class="bg-background p-4"
            >
                <dt class="text-xs text-muted-foreground">{{ fact.label }}</dt>
                <dd class="mt-1 text-sm font-medium tabular-nums">
                    {{ fact.value }}
                </dd>
            </div>
        </dl>

        <p
            v-if="project.description"
            class="max-w-2xl text-sm leading-relaxed text-muted-foreground"
        >
            {{ project.description }}
        </p>

        <section class="flex flex-col gap-3">
            <div class="flex flex-wrap items-baseline justify-between gap-3">
                <h2 class="text-sm font-medium">Milestones</h2>
                <div class="flex items-center gap-3">
                    <div
                        class="h-1 w-32 overflow-hidden rounded-full bg-muted"
                        role="progressbar"
                        :aria-valuenow="percent"
                        :aria-valuemin="0"
                        :aria-valuemax="100"
                        aria-label="Project progress"
                    >
                        <div
                            class="h-full rounded-full bg-foreground"
                            :style="{ width: `${percent}%` }"
                        />
                    </div>
                    <span class="text-xs text-muted-foreground tabular-nums"
                        >{{ percent }}%</span
                    >
                </div>
            </div>
            <p class="text-xs text-muted-foreground">
                This list is what the client sees in their portal, in this
                order.
            </p>

            <EmptyState
                v-if="milestones.length === 0"
                title="No milestones yet"
                description="Add the first stage below. The client's portal shows this list, in this order."
            />

            <ul
                v-else
                class="divide-y divide-sidebar-border/40 overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <li
                    v-for="(milestone, index) in milestones"
                    :key="milestone.id"
                    class="group flex flex-wrap items-center gap-3 px-3 py-2.5"
                >
                    <span
                        class="w-6 shrink-0 text-center font-mono text-xs text-muted-foreground tabular-nums"
                        aria-hidden="true"
                    >
                        {{ index + 1 }}
                    </span>

                    <Form
                        v-bind="
                            MilestoneController.update.form([
                                project.id,
                                milestone.id,
                            ])
                        "
                        class="flex flex-1 flex-wrap items-center gap-2"
                        v-slot="{ errors, processing }"
                    >
                        <Input
                            name="title"
                            :default-value="milestone.title"
                            class="h-8 min-w-40 flex-1 border-transparent bg-transparent hover:border-input focus-visible:border-input"
                            :aria-label="`Milestone ${index + 1} title`"
                            required
                        />
                        <Input
                            name="due_date"
                            type="date"
                            :default-value="milestone.due_date ?? ''"
                            class="h-8 w-36 border-transparent bg-transparent hover:border-input focus-visible:border-input"
                            :aria-label="`Milestone ${index + 1} due date`"
                        />
                        <select
                            name="status"
                            class="h-8 rounded-md border border-transparent bg-transparent px-2 text-sm hover:border-input focus-visible:border-input focus-visible:outline-none"
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
                            variant="ghost"
                            :disabled="processing"
                        >
                            Save
                        </Button>
                        <InputError
                            :message="
                                errors.title ?? errors.due_date ?? errors.status
                            "
                        />
                    </Form>

                    <div
                        class="flex shrink-0 items-center opacity-60 transition-opacity group-hover:opacity-100 focus-within:opacity-100"
                    >
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
                                size="icon"
                                variant="ghost"
                                class="size-8"
                                :disabled="index === 0"
                                aria-label="Move up"
                            >
                                <ArrowUp aria-hidden="true" />
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
                                size="icon"
                                variant="ghost"
                                class="size-8"
                                :disabled="index === milestones.length - 1"
                                aria-label="Move down"
                            >
                                <ArrowDown aria-hidden="true" />
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
                                size="icon"
                                variant="ghost"
                                class="size-8 text-muted-foreground hover:text-destructive"
                                aria-label="Remove milestone"
                            >
                                <Trash2 aria-hidden="true" />
                            </Button>
                        </Form>
                    </div>
                </li>
            </ul>

            <Form
                v-bind="MilestoneController.store.form(project.id)"
                class="flex flex-wrap items-end gap-3 rounded-xl border border-dashed border-sidebar-border/70 p-4 dark:border-sidebar-border"
                v-slot="{ errors, processing }"
                reset-on-success
            >
                <div class="grid min-w-48 flex-1 gap-1.5">
                    <Label
                        for="new-milestone-title"
                        class="text-xs text-muted-foreground"
                    >
                        New milestone
                    </Label>
                    <Input
                        id="new-milestone-title"
                        name="title"
                        class="h-8"
                        required
                        placeholder="Discovery and research"
                    />
                    <InputError :message="errors.title" />
                </div>
                <div class="grid gap-1.5">
                    <Label
                        for="new-milestone-due"
                        class="text-xs text-muted-foreground"
                        >Due</Label
                    >
                    <Input
                        id="new-milestone-due"
                        name="due_date"
                        type="date"
                        class="h-8 w-36"
                    />
                </div>
                <div class="grid gap-1.5">
                    <Label
                        for="new-milestone-status"
                        class="text-xs text-muted-foreground"
                    >
                        Status
                    </Label>
                    <select
                        id="new-milestone-status"
                        name="status"
                        class="h-8 rounded-md border border-input bg-transparent px-2 text-sm"
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
                <Button type="submit" size="sm" :disabled="processing">
                    <Plus aria-hidden="true" />
                    Add
                </Button>
            </Form>
        </section>

        <section
            class="flex flex-col gap-2 border-t border-sidebar-border/70 pt-6 dark:border-sidebar-border"
        >
            <h2 class="text-sm font-medium">Delete project</h2>
            <p class="text-sm text-muted-foreground">
                Its milestones go with it.
            </p>
            <Form
                v-bind="ProjectController.destroy.form(project.id)"
                v-slot="{ processing }"
                class="mt-1"
            >
                <Button
                    type="submit"
                    variant="destructive"
                    size="sm"
                    :disabled="processing"
                >
                    <Trash2 aria-hidden="true" />
                    Delete project
                </Button>
            </Form>
        </section>
    </div>
</template>
