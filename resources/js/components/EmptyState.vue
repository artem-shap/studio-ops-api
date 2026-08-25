<script setup lang="ts">
defineProps<{
    title: string;
    description: string;
}>();
</script>

<template>
    <!--
        An empty list is a place where someone got stuck, not a place to print
        "no results". Every one of these offers the action that fills it.
    -->
    <div
        class="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-sidebar-border/70 px-6 py-16 text-center dark:border-sidebar-border"
    >
        <h2 class="text-sm font-medium">{{ title }}</h2>
        <p class="max-w-sm text-sm text-muted-foreground">{{ description }}</p>
        <slot />
    </div>
</template>
