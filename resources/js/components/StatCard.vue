<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight } from '@lucide/vue';

defineProps<{
    label: string;
    value: number | string;
    href: string;
    tone?: 'default' | 'alert';
}>();
</script>

<template>
    <Link
        :href="href"
        class="group flex flex-col gap-1.5 bg-background p-5 transition-colors hover:bg-accent/50"
    >
        <span class="flex items-center gap-1 text-xs text-muted-foreground">
            {{ label }}
            <ArrowUpRight
                class="size-3 opacity-0 transition-opacity group-hover:opacity-100"
                aria-hidden="true"
            />
        </span>
        <span
            class="text-3xl font-semibold tabular-nums"
            :class="
                tone === 'alert' && Number(value) > 0
                    ? 'text-rose-600 dark:text-rose-400'
                    : ''
            "
        >
            {{ value }}
        </span>
    </Link>
</template>
