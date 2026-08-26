<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import ClientController from '@/actions/App/Http/Controllers/ClientController';
import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
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
        <PageHeader
            :title="
                isEditing ? (client!.company ?? client!.name) : 'New client'
            "
            :description="
                isEditing
                    ? 'Contact details, portal access and their projects'
                    : 'Add someone the studio is working with'
            "
        >
            <template v-if="isEditing" #actions>
                <Button as-child size="sm" variant="outline">
                    <Link :href="ClientController.index().url"
                        >All clients</Link
                    >
                </Button>
            </template>
        </PageHeader>

        <Form
            v-bind="submit"
            class="flex max-w-2xl flex-col gap-6 rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="name">Contact name</Label>
                    <Input
                        id="name"
                        name="name"
                        :default-value="client?.name"
                        required
                        autocomplete="name"
                        :aria-invalid="errors.name ? true : undefined"
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
                        :aria-invalid="errors.email ? true : undefined"
                    />
                    <InputError :message="errors.email" />
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
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
            </div>

            <div
                class="flex items-center gap-3 border-t border-sidebar-border/40 pt-5"
            >
                <Button type="submit" size="sm" :disabled="processing">
                    {{ isEditing ? 'Save changes' : 'Create client' }}
                </Button>
                <Button as-child size="sm" variant="ghost">
                    <Link :href="ClientController.index().url">Cancel</Link>
                </Button>
            </div>
        </Form>

        <template v-if="isEditing">
            <section
                class="flex max-w-2xl flex-col gap-3 rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border"
            >
                <h2 class="text-sm font-medium">Portal access</h2>
                <p
                    v-if="client!.has_portal_access"
                    class="text-sm text-muted-foreground"
                >
                    Active until {{ formatDate(client!.portal_expires_at) }}.
                    The link itself was shown once when it was issued and cannot
                    be recovered — only its hash is stored, so a lost link is
                    reissued rather than looked up.
                </p>
                <p v-else class="text-sm text-muted-foreground">
                    No portal link. One is issued automatically when an inquiry
                    is converted into a project.
                </p>
            </section>

            <section class="flex max-w-2xl flex-col gap-3">
                <div class="flex items-baseline justify-between gap-4">
                    <h2 class="text-sm font-medium">Projects</h2>
                    <span class="text-xs text-muted-foreground">
                        {{ projects?.length ?? 0 }} on the books
                    </span>
                </div>

                <ul
                    v-if="projects && projects.length > 0"
                    class="divide-y divide-sidebar-border/40 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <li
                        v-for="project in projects"
                        :key="project.id"
                        class="group flex items-center justify-between gap-3 px-4 py-3"
                    >
                        <Link
                            :href="ProjectController.show(project.id).url"
                            class="text-sm font-medium hover:underline"
                        >
                            {{ project.title }}
                        </Link>
                        <div class="flex items-center gap-3">
                            <StatusBadge :status="project.status" />
                            <ArrowRight
                                class="size-4 text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100"
                                aria-hidden="true"
                            />
                        </div>
                    </li>
                </ul>
                <p
                    v-else
                    class="rounded-xl border border-dashed border-sidebar-border/70 px-4 py-8 text-center text-sm text-muted-foreground dark:border-sidebar-border"
                >
                    Nothing yet.
                </p>
            </section>

            <section
                class="flex max-w-2xl flex-col gap-2 border-t border-sidebar-border/70 pt-6 dark:border-sidebar-border"
            >
                <h2 class="text-sm font-medium">Delete client</h2>
                <p class="text-sm text-muted-foreground">
                    Their projects and milestones go with them, and any portal
                    link stops working.
                </p>
                <Form
                    v-bind="ClientController.destroy.form(client!.id)"
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
                        Delete client
                    </Button>
                </Form>
            </section>
        </template>
    </div>
</template>
