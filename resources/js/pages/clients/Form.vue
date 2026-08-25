<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import ClientController from '@/actions/App/Http/Controllers/ClientController';
import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate } from '@/lib/money';
import type { ClientDetail, ProjectSummary } from '@/types/studio';

const props = defineProps<{
    client: ClientDetail | null;
    projects?: ProjectSummary[];
}>();

const isEditing = computed(() => props.client !== null);

const submit = computed(() =>
    props.client === null
        ? ClientController.store.form()
        : ClientController.update.form(props.client.id),
);

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Clients', href: ClientController.index().url }],
    },
});
</script>

<template>
    <Head :title="isEditing ? client!.name : 'New client'" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <Heading
            variant="small"
            :title="isEditing ? 'Edit client' : 'New client'"
            :description="
                isEditing
                    ? 'Update the studio\'s record for this client'
                    : 'Add someone the studio is working with'
            "
        />

        <Form
            v-bind="submit"
            class="max-w-xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Contact name</Label>
                <Input
                    id="name"
                    name="name"
                    :default-value="client?.name"
                    required
                    autocomplete="name"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    name="email"
                    type="email"
                    :default-value="client?.email"
                    required
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="company">Company</Label>
                <Input
                    id="company"
                    name="company"
                    :default-value="client?.company ?? ''"
                />
                <InputError :message="errors.company" />
            </div>

            <div class="grid gap-2">
                <Label for="phone">Phone</Label>
                <Input
                    id="phone"
                    name="phone"
                    :default-value="client?.phone ?? ''"
                />
                <InputError :message="errors.phone" />
            </div>

            <div class="flex items-center gap-3">
                <Button type="submit" :disabled="processing">
                    {{ isEditing ? 'Save changes' : 'Create client' }}
                </Button>
                <Button as-child variant="ghost">
                    <Link :href="ClientController.index().url">Cancel</Link>
                </Button>
            </div>
        </Form>

        <template v-if="isEditing">
            <section class="max-w-xl space-y-3">
                <Heading
                    variant="small"
                    title="Portal access"
                    description="What this client can open from their link"
                />
                <p
                    v-if="client!.has_portal_access"
                    class="text-sm text-muted-foreground"
                >
                    Active, valid until
                    {{ formatDate(client!.portal_expires_at) }}. The link itself
                    was shown once when it was issued and cannot be recovered —
                    only its hash is stored.
                </p>
                <p v-else class="text-sm text-muted-foreground">
                    No portal link. One is issued automatically when an inquiry
                    is converted.
                </p>
            </section>

            <section class="max-w-xl space-y-3">
                <Heading
                    variant="small"
                    title="Projects"
                    :description="`${projects?.length ?? 0} on the books`"
                />
                <ul
                    v-if="projects && projects.length > 0"
                    class="divide-y divide-sidebar-border/40 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <li
                        v-for="project in projects"
                        :key="project.id"
                        class="flex items-center justify-between gap-3 px-4 py-3"
                    >
                        <Link
                            :href="ProjectController.show(project.id).url"
                            class="text-sm font-medium hover:underline"
                        >
                            {{ project.title }}
                        </Link>
                        <StatusBadge :status="project.status" />
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">Nothing yet.</p>
            </section>

            <section class="max-w-xl space-y-3">
                <Heading
                    variant="small"
                    title="Delete client"
                    description="Their projects and milestones go with them"
                />
                <Form
                    v-bind="ClientController.destroy.form(client!.id)"
                    v-slot="{ processing }"
                >
                    <Button
                        type="submit"
                        variant="destructive"
                        size="sm"
                        :disabled="processing"
                    >
                        Delete client
                    </Button>
                </Form>
            </section>
        </template>
    </div>
</template>
