<script setup lang="ts">
import { Inbox, SquareKanban, Users } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

defineProps<{
    title?: string;
    description?: string;
}>();

const holds = [
    { icon: Users, label: 'Clients and their portal access' },
    { icon: SquareKanban, label: 'Projects, milestones and dates' },
    { icon: Inbox, label: 'Inquiries from the public site' },
];
</script>

<template>
    <div class="grid min-h-svh lg:grid-cols-[1fr_1.1fr]">
        <!--
            The panel says what is behind the login. Anyone reaching this screen
            either works here or took a wrong turn, and both are better served
            by a plain statement than by decoration.
        -->
        <aside
            class="relative hidden flex-col justify-between bg-sidebar p-10 lg:flex dark:border-r dark:border-sidebar-border"
        >
            <div class="flex items-center gap-2.5">
                <span
                    class="flex size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground"
                >
                    <AppLogoIcon class="size-4.5" />
                </span>
                <span class="font-semibold tracking-tight">
                    Studio<span class="text-muted-foreground">Ops</span>
                </span>
            </div>

            <div class="max-w-sm space-y-8">
                <p
                    class="text-2xl leading-snug font-medium tracking-tight text-balance"
                >
                    Everything the studio is running, in one place.
                </p>

                <ul class="space-y-4">
                    <li
                        v-for="item in holds"
                        :key="item.label"
                        class="flex items-center gap-3 text-sm text-muted-foreground"
                    >
                        <span
                            class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-sidebar-border/70 dark:border-sidebar-border"
                        >
                            <component
                                :is="item.icon"
                                class="size-4"
                                :stroke-width="1.5"
                                aria-hidden="true"
                            />
                        </span>
                        {{ item.label }}
                    </li>
                </ul>
            </div>

            <p class="text-xs text-muted-foreground">
                Staff accounts are created by the studio. There is no sign-up.
            </p>
        </aside>

        <main class="flex items-center justify-center p-6 sm:p-10">
            <div class="w-full max-w-sm space-y-8">
                <!-- The mark only shows here where the panel is hidden. -->
                <div class="flex items-center gap-2.5 lg:hidden">
                    <span
                        class="flex size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground"
                    >
                        <AppLogoIcon class="size-4.5" />
                    </span>
                    <span class="font-semibold tracking-tight">
                        Studio<span class="text-muted-foreground">Ops</span>
                    </span>
                </div>

                <div class="space-y-2">
                    <h1
                        v-if="title"
                        class="text-xl font-semibold tracking-tight"
                    >
                        {{ title }}
                    </h1>
                    <p v-if="description" class="text-sm text-muted-foreground">
                        {{ description }}
                    </p>
                </div>

                <slot />
            </div>
        </main>
    </div>
</template>
