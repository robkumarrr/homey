<script setup lang="ts">
/**
 * PROTOTYPE shell — the navigation pattern decided in the grill: frosted top
 * bar with nav from tablet up, frosted bottom tab bar on phones. Shared by all
 * three home-screen variants; only the page content differs between them.
 */
import { usePage } from '@inertiajs/vue3';
import {
    Hammer,
    House,
    Moon,
    PawPrint,
    Sun,
    Wallet,
    Wrench,
} from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Toaster } from '@/components/ui/sonner';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useAppearance } from '@/composables/useAppearance';
import { getInitials } from '@/composables/useInitials';
import './theme.css';

const page = usePage();
const user = computed(() => page.props.auth.user);
const { resolvedAppearance, updateAppearance } = useAppearance();

const navItems = [
    { key: 'home', label: 'Home', short: 'Home', icon: House },
    { key: 'finances', label: 'Finances', short: 'Finances', icon: Wallet },
    {
        key: 'maintenance',
        label: 'Maintenance',
        short: 'Upkeep',
        icon: Wrench,
    },
    { key: 'projects', label: 'Projects', short: 'Projects', icon: Hammer },
    { key: 'pets', label: 'Pets', short: 'Pets', icon: PawPrint },
];

const toggleAppearance = () =>
    updateAppearance(resolvedAppearance.value === 'dark' ? 'light' : 'dark');

onMounted(() => document.documentElement.classList.add('proto-forest'));
onBeforeUnmount(() =>
    document.documentElement.classList.remove('proto-forest'),
);
</script>

<template>
    <div class="proto-wash min-h-screen text-foreground">
        <header
            class="proto-glass sticky top-0 z-40 border-b border-white/10 text-(--chrome-foreground)"
        >
            <div
                class="mx-auto flex h-14 max-w-6xl items-center gap-3 px-4 md:h-16 md:px-6"
            >
                <div class="flex items-center gap-2 font-semibold">
                    <span
                        class="flex size-8 items-center justify-center rounded-xl bg-(--chrome-foreground) text-(--chrome)"
                    >
                        <House class="size-4" />
                    </span>
                    Homey
                </div>

                <nav
                    class="ml-6 hidden items-center gap-1 md:flex"
                    aria-label="Main"
                >
                    <button
                        v-for="item in navItems"
                        :key="item.key"
                        type="button"
                        class="rounded-full px-3 py-1.5 text-sm transition-colors"
                        :class="
                            item.key === 'home'
                                ? 'bg-(--lavender) font-medium text-(--lavender-foreground)'
                                : 'text-(--chrome-muted) hover:bg-(--chrome-active) hover:text-(--chrome-foreground)'
                        "
                        :aria-current="item.key === 'home' ? 'page' : undefined"
                    >
                        {{ item.label }}
                    </button>
                </nav>

                <div class="ml-auto flex items-center gap-1">
                    <button
                        type="button"
                        class="flex size-9 items-center justify-center rounded-full text-(--chrome-muted) hover:bg-(--chrome-active) hover:text-(--chrome-foreground)"
                        :aria-label="
                            resolvedAppearance === 'dark'
                                ? 'Switch to light mode'
                                : 'Switch to dark mode'
                        "
                        @click="toggleAppearance"
                    >
                        <Sun
                            v-if="resolvedAppearance === 'dark'"
                            class="size-4.5"
                        />
                        <Moon v-else class="size-4.5" />
                    </button>

                    <DropdownMenu>
                        <DropdownMenuTrigger :as-child="true">
                            <button
                                type="button"
                                class="rounded-full p-0.5 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <Avatar
                                    class="size-8 overflow-hidden rounded-full"
                                >
                                    <AvatarImage
                                        v-if="user.avatar"
                                        :src="user.avatar"
                                        :alt="user.name"
                                    />
                                    <AvatarFallback
                                        class="rounded-full bg-(--chrome-active) text-xs font-semibold text-(--chrome-foreground)"
                                    >
                                        {{ getInitials(user.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <UserMenuContent :user="user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </header>

        <main
            class="mx-auto w-full max-w-6xl px-4 pt-5 pb-36 md:px-6 md:pt-8 md:pb-20"
        >
            <slot />
        </main>

        <nav
            class="proto-glass fixed inset-x-0 bottom-0 z-40 border-t border-white/10 pb-[env(safe-area-inset-bottom)] md:hidden"
            aria-label="Main"
        >
            <ul class="grid grid-cols-5">
                <li v-for="item in navItems" :key="item.key">
                    <button
                        type="button"
                        class="flex w-full flex-col items-center gap-1 py-2 text-[11px]"
                        :class="
                            item.key === 'home'
                                ? 'font-medium text-(--chrome-foreground)'
                                : 'text-(--chrome-muted)'
                        "
                        :aria-current="item.key === 'home' ? 'page' : undefined"
                    >
                        <span
                            class="flex h-7 w-12 items-center justify-center rounded-full"
                            :class="
                                item.key === 'home'
                                    ? 'bg-(--lavender) text-(--lavender-foreground)'
                                    : ''
                            "
                        >
                            <component :is="item.icon" class="size-4.5" />
                        </span>
                        {{ item.short }}
                    </button>
                </li>
            </ul>
        </nav>

        <Toaster />
    </div>
</template>
