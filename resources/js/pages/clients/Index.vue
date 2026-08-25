<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ClientController from '@/actions/App/Http/Controllers/ClientController';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import type { ClientRow } from '@/types/studio';

defineProps<{ clients: ClientRow[] }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Clients', href: ClientController.index().url }],
    },
});
</script>

<template>
    <Head title="Clients" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                title="Clients"
                description="Everyone the studio is working with"
            />
            <Button as-child size="sm">
                <Link :href="ClientController.create().url">Add client</Link>
            </Button>
        </div>

        <EmptyState
            v-if="clients.length === 0"
            title="No clients yet"
            description="Clients appear here once you add one, or once you convert an inquiry into a project."
        >
            <Button as-child size="sm" variant="outline">
                <Link :href="ClientController.create().url"
                    >Add your first client</Link
                >
            </Button>
        </EmptyState>

        <div
            v-else
            class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <table class="w-full text-sm">
                <caption class="sr-only">
                    Clients, with their project counts and portal access
                </caption>
                <thead
                    class="border-b border-sidebar-border/70 text-left dark:border-sidebar-border"
                >
                    <tr class="text-muted-foreground">
                        <th scope="col" class="px-4 py-3 font-medium">
                            Client
                        </th>
                        <th scope="col" class="px-4 py-3 font-medium">Email</th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Projects
                        </th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Portal
                        </th>
                        <th scope="col" class="px-4 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="client in clients"
                        :key="client.id"
                        class="border-b border-sidebar-border/40 last:border-0 dark:border-sidebar-border/60"
                    >
                        <td class="px-4 py-3">
                            <div class="font-medium">
                                {{ client.company ?? client.name }}
                            </div>
                            <div
                                v-if="client.company"
                                class="text-xs text-muted-foreground"
                            >
                                {{ client.name }}
                            </div>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ client.email }}
                        </td>
                        <td class="px-4 py-3 tabular-nums">
                            {{ client.projects_count }}
                        </td>
                        <td class="px-4 py-3">
                            <span
                                :class="
                                    client.has_portal_access
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-muted-foreground'
                                "
                            >
                                {{
                                    client.has_portal_access ? 'Active' : 'None'
                                }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Button as-child size="sm" variant="ghost">
                                <Link
                                    :href="ClientController.edit(client.id).url"
                                    >Edit</Link
                                >
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
