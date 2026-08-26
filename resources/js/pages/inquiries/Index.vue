<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Mail } from '@lucide/vue';
import InquiryController from '@/actions/App/Http/Controllers/InquiryController';
import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/money';
import type { InquiryRow, Status } from '@/types/studio';

defineProps<{
    inquiries: InquiryRow[];
    statuses: Status[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inquiries', href: InquiryController.index().url },
        ],
    },
});
</script>

<template>
    <Head title="Inquiries" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Inquiries"
            description="Everything that came in through the site"
        />

        <EmptyState
            v-if="inquiries.length === 0"
            title="The inbox is empty"
            description="Inquiries submitted on the public site land here, ready to be converted into a client and a project."
        />

        <ul v-else class="flex flex-col gap-4">
            <li
                v-for="inquiry in inquiries"
                :key="inquiry.id"
                class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <div
                    class="flex flex-wrap items-start justify-between gap-4 p-5"
                >
                    <div class="flex min-w-0 gap-4">
                        <span
                            class="flex size-10 shrink-0 items-center justify-center rounded-full border border-sidebar-border/70 bg-muted text-xs font-medium text-muted-foreground dark:border-sidebar-border"
                            aria-hidden="true"
                        >
                            {{
                                (inquiry.company ?? inquiry.name)
                                    .slice(0, 2)
                                    .toUpperCase()
                            }}
                        </span>
                        <div class="min-w-0 space-y-1">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <h2 class="font-medium">
                                    {{ inquiry.company ?? inquiry.name }}
                                </h2>
                                <StatusBadge :status="inquiry.status" />
                            </div>
                            <p
                                class="flex flex-wrap items-center gap-x-2 text-xs text-muted-foreground"
                            >
                                <span>{{ inquiry.name }}</span>
                                <span aria-hidden="true">·</span>
                                <a
                                    :href="`mailto:${inquiry.email}`"
                                    class="inline-flex items-center gap-1 hover:underline"
                                >
                                    <Mail class="size-3" aria-hidden="true" />
                                    {{ inquiry.email }}
                                </a>
                                <template v-if="inquiry.budget_range">
                                    <span aria-hidden="true">·</span>
                                    <span>{{ inquiry.budget_range }}</span>
                                </template>
                                <span aria-hidden="true">·</span>
                                <span>{{
                                    formatDate(inquiry.received_at)
                                }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        <Button
                            v-if="inquiry.converted_project"
                            as-child
                            size="sm"
                            variant="outline"
                        >
                            <Link
                                :href="
                                    ProjectController.show(
                                        inquiry.converted_project.id,
                                    ).url
                                "
                            >
                                Open project
                                <ArrowRight aria-hidden="true" />
                            </Link>
                        </Button>

                        <!--
                            Converting is deliberately not offered twice. The
                            action is idempotent anyway, but a button that looks
                            available invites a second click and a support
                            question about the duplicate that never happened.
                        -->
                        <Form
                            v-else
                            v-bind="InquiryController.convert.form(inquiry.id)"
                            v-slot="{ processing }"
                        >
                            <Button
                                type="submit"
                                size="sm"
                                :disabled="processing"
                            >
                                Convert to project
                                <ArrowRight aria-hidden="true" />
                            </Button>
                        </Form>
                    </div>
                </div>

                <blockquote
                    class="border-t border-sidebar-border/40 bg-muted/40 px-5 py-4 text-sm leading-relaxed text-muted-foreground"
                >
                    {{ inquiry.message }}
                </blockquote>

                <Form
                    v-if="!inquiry.converted_project"
                    v-bind="InquiryController.updateStatus.form(inquiry.id)"
                    class="flex items-center gap-2 border-t border-sidebar-border/40 px-5 py-3"
                    v-slot="{ processing }"
                >
                    <label
                        :for="`status-${inquiry.id}`"
                        class="text-xs text-muted-foreground"
                    >
                        Status
                    </label>
                    <select
                        :id="`status-${inquiry.id}`"
                        name="status"
                        class="h-8 rounded-md border border-input bg-transparent px-2 text-sm"
                    >
                        <option
                            v-for="status in statuses.filter(
                                (s) => s.value !== 'converted',
                            )"
                            :key="status.value"
                            :value="status.value"
                            :selected="inquiry.status.value === status.value"
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
                        Update
                    </Button>
                </Form>
            </li>
        </ul>
    </div>
</template>
