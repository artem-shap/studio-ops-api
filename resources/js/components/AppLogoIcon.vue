<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

defineProps<{ className?: HTMLAttributes['class'] }>();
</script>

<template>
    <!--
        The same mark as the public site's icon: one solid circle and one
        outlined, two shops under one identity. Drawn rather than imported so
        it inherits currentColor and needs no asset.
    -->
    <svg
        v-bind="$attrs"
        :class="className"
        viewBox="0 0 32 32"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
    >
        <circle cx="13" cy="16" r="6.5" fill="currentColor" />
        <circle
            cx="21"
            cy="16"
            r="6.5"
            stroke="currentColor"
            stroke-width="1.6"
        />
    </svg>
</template>
