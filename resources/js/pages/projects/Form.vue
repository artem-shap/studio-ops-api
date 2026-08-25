<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
        <Heading
            variant="small"
            :title="isEditing ? 'Edit project' : 'New project'"
            description="Milestones are added on the project page once it exists"
        />

        <Form
            v-bind="submit"
            class="max-w-xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <!--
                Only on create. Moving a project between clients would move a
                portal link's contents too, which is a different operation with
                different consequences than editing a title.
            -->
            <div v-if="!isEditing" class="grid gap-2">
                <Label for="client_id">Client</Label>
                <select
                    id="client_id"
                    name="client_id"
                    required
                    class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
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
                />
                <InputError :message="errors.title" />
            </div>

            <div class="grid gap-2">
                <Label for="description">Description</Label>
                <!--
                    :value rather than interpolated content. A mustache inside a
                    textarea is not how Vue sets a textarea's value, and the
                    field can render with the raw template instead of the text.
                -->
                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    :value="project?.description ?? ''"
                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                ></textarea>
                <InputError :message="errors.description" />
            </div>

            <div class="grid gap-2">
                <Label for="status">Status</Label>
                <select
                    id="status"
                    name="status"
                    required
                    class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
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

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <!-- Whole units here; storage is minor units. Nobody types cents. -->
                    <Label for="budget">Budget</Label>
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
                    />
                    <InputError :message="errors.currency" />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
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

            <div class="flex items-center gap-3">
                <Button type="submit" :disabled="processing">
                    {{ isEditing ? 'Save changes' : 'Create project' }}
                </Button>
                <Button as-child variant="ghost">
                    <Link :href="ProjectController.index().url">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
