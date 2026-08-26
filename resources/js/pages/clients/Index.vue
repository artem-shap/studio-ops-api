<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import ClientController from '@/actions/App/Http/Controllers/ClientController';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
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
        <PageHeader
            title="Clients"
            description="Everyone the studio is working with"
        >
            <template #actions>
                <Button as-child size="sm">
                    <Link :href="ClientController.create().url">
                        <Plus aria-hidden="true" />
                        Add client
                    </Link>
                </Button>
            </template>
        </PageHeader>

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
            class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <Table>
                <TableCaption class="sr-only">
                    Clients, with their project counts and portal access
                </TableCaption>
                <TableHeader>
                    <TableRow class="hover:bg-transparent">
                        <TableHead>Client</TableHead>
                        <TableHead class="hidden sm:table-cell"
                            >Email</TableHead
                        >
                        <TableHead class="w-24 text-right">Projects</TableHead>
                        <TableHead class="w-28">Portal</TableHead>
                        <TableHead class="w-20"
                            ><span class="sr-only">Actions</span></TableHead
                        >
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="client in clients" :key="client.id">
                        <TableCell>
                            <Link
                                :href="ClientController.edit(client.id).url"
                                class="font-medium hover:underline"
                            >
                                {{ client.company ?? client.name }}
                            </Link>
                            <p
                                v-if="client.company"
                                class="text-xs text-muted-foreground"
                            >
                                {{ client.name }}
                            </p>
                        </TableCell>
                        <TableCell
                            class="hidden text-muted-foreground sm:table-cell"
                        >
                            {{ client.email }}
                        </TableCell>
                        <TableCell class="text-right tabular-nums">
                            {{ client.projects_count }}
                        </TableCell>
                        <TableCell>
                            <span
                                class="inline-flex items-center gap-1.5 text-xs"
                                :class="
                                    client.has_portal_access
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-muted-foreground'
                                "
                            >
                                <span
                                    class="size-1.5 rounded-full"
                                    :class="
                                        client.has_portal_access
                                            ? 'bg-emerald-500'
                                            : 'bg-muted-foreground/40'
                                    "
                                    aria-hidden="true"
                                />
                                {{
                                    client.has_portal_access ? 'Active' : 'None'
                                }}
                            </span>
                        </TableCell>
                        <TableCell class="text-right">
                            <Button as-child size="sm" variant="ghost">
                                <Link
                                    :href="ClientController.edit(client.id).url"
                                    >Edit</Link
                                >
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>
</template>
