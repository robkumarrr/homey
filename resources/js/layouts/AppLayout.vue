<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Hammer,
    House,
    ListChecks,
    Moon,
    PawPrint,
    Sun,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Toaster } from '@/components/ui/sonner';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useAppearance } from '@/composables/useAppearance';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { getInitials } from '@/composables/useInitials';
import { dashboard, finances, pets, projects, tasks } from '@/routes';
import type { NavItem } from '@/types';

const page = usePage();
const user = computed(() => page.props.auth.user);
const { resolvedAppearance, updateAppearance } = useAppearance();
const { isCurrentOrParentUrl } = useCurrentUrl();

const navItems: NavItem[] = [
    { title: 'Home', href: dashboard(), icon: House },
    { title: 'Finances', href: finances(), icon: Wallet },
    { title: 'Tasks', href: tasks(), icon: ListChecks },
    { title: 'Projects', href: projects(), icon: Hammer },
    { title: 'Pets', href: pets(), icon: PawPrint },
];

const toggleAppearance = () =>
    updateAppearance(resolvedAppearance.value === 'dark' ? 'light' : 'dark');
</script>

<template>
    <div class="min-h-screen app-wash text-foreground">
        <header
            class="sticky top-0 z-40 border-b border-white/10 text-chrome-foreground glass"
        >
            <div
                class="mx-auto flex h-14 max-w-6xl items-center gap-3 px-4 md:h-16 md:px-6"
            >
                <Link
                    :href="dashboard()"
                    class="flex items-center gap-2 font-semibold"
                >
                    <span
                        class="flex size-8 items-center justify-center rounded-xl bg-chrome-foreground text-chrome"
                    >
                        <House class="size-4" />
                    </span>
                    {{ page.props.name }}
                </Link>

                <nav
                    class="ml-6 hidden items-center gap-1 md:flex"
                    aria-label="Main"
                >
                    <Link
                        v-for="item in navItems"
                        :key="item.title"
                        :href="item.href"
                        class="rounded-full px-3 py-1.5 text-sm transition-colors"
                        :class="
                            isCurrentOrParentUrl(item.href)
                                ? 'bg-lavender font-medium text-lavender-foreground'
                                : 'text-chrome-muted hover:bg-chrome-active hover:text-chrome-foreground'
                        "
                        :aria-current="
                            isCurrentOrParentUrl(item.href) ? 'page' : undefined
                        "
                    >
                        {{ item.title }}
                    </Link>
                </nav>

                <div class="ml-auto flex items-center gap-1">
                    <button
                        type="button"
                        class="flex size-9 items-center justify-center rounded-full text-chrome-muted hover:bg-chrome-active hover:text-chrome-foreground"
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
                                        class="rounded-full bg-chrome-active text-xs font-semibold text-chrome-foreground"
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
            class="fixed inset-x-0 bottom-0 z-40 border-t border-white/10 pb-[env(safe-area-inset-bottom)] glass md:hidden"
            aria-label="Main"
        >
            <ul class="grid grid-cols-5">
                <li v-for="item in navItems" :key="item.title">
                    <Link
                        :href="item.href"
                        class="flex w-full flex-col items-center gap-1 py-2 text-[11px]"
                        :class="
                            isCurrentOrParentUrl(item.href)
                                ? 'font-medium text-chrome-foreground'
                                : 'text-chrome-muted'
                        "
                        :aria-current="
                            isCurrentOrParentUrl(item.href) ? 'page' : undefined
                        "
                    >
                        <span
                            class="flex h-7 w-12 items-center justify-center rounded-full"
                            :class="
                                isCurrentOrParentUrl(item.href)
                                    ? 'bg-lavender text-lavender-foreground'
                                    : ''
                            "
                        >
                            <component :is="item.icon" class="size-4.5" />
                        </span>
                        {{ item.title }}
                    </Link>
                </li>
            </ul>
        </nav>

        <Toaster />
    </div>
</template>
