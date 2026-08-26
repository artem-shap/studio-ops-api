<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { ProjectFormValues, SelectOption, Status } from '@/types/studio';

const props = defineProps<{
    project: ProjectFormValues | null;
    clients: SelectOption[];
    statuses: Status[];
}>();

const isEditing = computed(() => props.project !== null);

const submit = computed(() =>
    props.project === null
        ? ProjectController.store.form()
        : ProjectController.update.form(props.project.id),
);

const selectClass =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs transition-colors focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Projects', href: ProjectController.index().url },
        ],
    },
});
</script>

<template>
    <Head :title="isEditing ? 'Edit project' : 'New project'" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <PageHeader
            :title="isEditing ? 'Edit project' : 'New project'"
            description="Milestones are added on the project page once it exists"
        >
            <template v-if="isEditing" #actions>
                <Button as-child size="sm" variant="outline">
                    <Link :href="ProjectController.show(project!.id).url">
                        Back to project
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <Form
            v-bind="submit"
            class="flex max-w-2xl flex-col gap-6 rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border"
            v-slot="{ errors, processing }"
        >
            <!--
                Client is set once, on creation. Moving a project between
                clients would move a portal link's contents with it, which is a
                different operation with different consequences than editing a
                title, and it should not hide behind the same Save button.
            -->
            <div v-if="!isEditing" class="grid gap-2">
                <Label for="client_id">Client</Label>
                <select
                    id="client_id"
                    name="client_id"
                    required
                    :class="selectClass"
                >
                    <option
                        v-for="option in clients"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <InputError :message="errors.client_id" />
            </div>

            <div class="grid gap-2">
                <Label for="title">Title</Label>
                <Input
                    id="title"
                    name="title"
                    :default-value="project?.title"
                    required
                    :aria-invalid="errors.title ? true : undefined"
                />
                <InputError :message="errors.title" />
            </div>

            <div class="grid gap-2">
                <Label for="description">Description</Label>
                <Textarea
                    id="description"
                    name="description"
                    rows="4"
                    :default-value="project?.description ?? ''"
                    class="leading-relaxed"
                />
                <p class="text-xs text-muted-foreground">
                    The client sees this in their portal.
                </p>
                <InputError :message="errors.description" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="status">Status</Label>
                    <select
                        id="status"
                        name="status"
                        required
                        :class="selectClass"
                    >
                        <option
                            v-for="status in statuses"
                            :key="status.value"
                            :value="status.value"
                            :selected="project?.status === status.value"
                        >
                            {{ status.label }}
                        </option>
                    </select>
                    <InputError :message="errors.status" />
                </div>

                <div class="grid grid-cols-[1fr_5rem] gap-3">
                    <div class="grid gap-2">
                        <Label for="budget">Budget</Label>
                        <!-- Whole units here; storage is minor units. -->
                        <Input
                            id="budget"
                            name="budget"
                            type="number"
                            min="0"
                            step="100"
                            :default-value="project?.budget ?? ''"
                        />
                        <InputError :message="errors.budget" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="currency">Currency</Label>
                        <Input
                            id="currency"
                            name="currency"
                            maxlength="3"
                            :default-value="project?.currency ?? 'USD'"
                            required
                            class="uppercase"
                        />
                        <InputError :message="errors.currency" />
                    </div>
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="start_date">Start date</Label>
                    <Input
                        id="start_date"
                        name="start_date"
                        type="date"
                        :default-value="project?.start_date ?? ''"
                    />
                    <InputError :message="errors.start_date" />
                </div>
                <div class="grid gap-2">
                    <Label for="due_date">Due date</Label>
                    <Input
                        id="due_date"
                        name="due_date"
                        type="date"
                        :default-value="project?.due_date ?? ''"
                    />
                    <InputError :message="errors.due_date" />
                </div>
            </div>

            <div
                class="flex items-center gap-3 border-t border-sidebar-border/40 pt-5"
            >
                <Button type="submit" size="sm" :disabled="processing">
                    {{ isEditing ? 'Save changes' : 'Create project' }}
                </Button>
                <Button as-child size="sm" variant="ghost">
                    <Link :href="ProjectController.index().url">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
