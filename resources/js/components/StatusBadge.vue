<script setup lang="ts">
import type { Status, StatusColor } from '@/types/studio';

defineProps<{ status: Status }>();

/**
 * Written out in full because Tailwind scans source for complete class names.
 * A template literal built from the colour would be purged from the bundle and
 * the badges would render unstyled in production and nowhere else.
 */
const classes: Record<StatusColor, string> = {
    slate: 'bg-slate-100 text-slate-700 ring-slate-600/20 dark:bg-slate-400/10 dark:text-slate-300 dark:ring-slate-400/30',
    blue: 'bg-blue-100 text-blue-700 ring-blue-600/20 dark:bg-blue-400/10 dark:text-blue-300 dark:ring-blue-400/30',
    amber: 'bg-amber-100 text-amber-800 ring-amber-600/20 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/30',
    emerald:
        'bg-emerald-100 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/30',
    rose: 'bg-rose-100 text-rose-700 ring-rose-600/20 dark:bg-rose-400/10 dark:text-rose-300 dark:ring-rose-400/30',
};
</script>

<template>
    <span
        class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
        :class="classes[status.color]"
    >
        {{ status.label }}
    </span>
</template>
