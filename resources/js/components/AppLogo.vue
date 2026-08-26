<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground"
    >
        <AppLogoIcon class="size-4.5" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <!--
            The name is written rather than read from config: the config value
            is what the browser tab and the mailer use, and a rename there
            should not silently restyle the wordmark.
        -->
        <span
            class="mb-0.5 truncate leading-tight font-semibold tracking-tight"
        >
            Studio<span class="text-muted-foreground">Ops</span>
        </span>
    </div>
</template>
