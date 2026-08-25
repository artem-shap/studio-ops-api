<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InquiryController from '@/actions/App/Http/Controllers/InquiryController';
import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
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
        <Heading
            variant="small"
            title="Inquiries"
            description="Everything that came in through the site"
        />

        <EmptyState
            v-if="inquiries.length === 0"
            title="The inbox is empty"
            description="Inquiries submitted on the public site land here, ready to be converted into a client and a project."
        />

        <ul v-else class="space-y-4">
            <li
                v-for="inquiry in inquiries"
                :key="inquiry.id"
                class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-3">
                            <h2 class="font-medium">
                                {{ inquiry.company ?? inquiry.name }}
                            </h2>
                            <StatusBadge :status="inquiry.status" />
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{ inquiry.name }} · {{ inquiry.email }}
                            <template v-if="inquiry.budget_range">
                                · {{ inquiry.budget_range }}</template
                            >
                            · {{ formatDate(inquiry.received_at) }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <Link
                            v-if="inquiry.converted_project"
                            :href="
                                ProjectController.show(
                                    inquiry.converted_project.id,
                                ).url
                            "
                            class="text-sm hover:underline"
                        >
                            Open {{ inquiry.converted_project.title }}
                        </Link>

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
                            </Button>
                        </Form>
                    </div>
                </div>

                <p class="mt-3 max-w-3xl text-sm text-muted-foreground">
                    {{ inquiry.message }}
                </p>

                <Form
                    v-if="!inquiry.converted_project"
                    v-bind="InquiryController.updateStatus.form(inquiry.id)"
                    class="mt-3 flex items-center gap-2"
                    v-slot="{ processing }"
                >
                    <label
                        :for="`status-${inquiry.id}`"
                        class="text-xs text-muted-foreground"
                        >Status</label
                    >
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
                        >Update</Button
                    >
                </Form>
            </li>
        </ul>
    </div>
</template>
