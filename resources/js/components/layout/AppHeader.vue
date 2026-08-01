<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { SettingsIcon, ShieldIcon } from '@lucide/vue';
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { clearServiceWorkerCaches } from '@/lib/pwa';
import { accounts as accountsIndex, contacts, dashboard, logout, preferences } from '@/routes';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as billing } from '@/routes/billing';
import { index as categoriesIndex } from '@/routes/categories';
import { edit as profile } from '@/routes/profile';
import { index as reportsIndex } from '@/routes/reports';
import { index as servicesIndex } from '@/routes/services';
import { index as tagsIndex } from '@/routes/tags';
import transactions from '@/routes/transactions';

const page = usePage<{
    auth: { user: { name: string; avatar_url: string | null; is_admin?: boolean } | null };
}>();

const isAdmin = computed(() => page.props.auth.user?.is_admin === true);

const userName = computed(() => page.props.auth.user?.name ?? '');

const avatarUrl = computed(() => page.props.auth.user?.avatar_url ?? undefined);

const userInitials = computed(
    () =>
        userName.value
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map((part) => part.charAt(0).toUpperCase())
            .join('') || '?',
);
</script>

<template>
    <header class="border-border bg-background/95 w-full border-b backdrop-blur">
        <div class="flex w-full items-center justify-between gap-4 px-4 py-4 lg:px-6 xl:px-8">
            <div class="relative z-20 flex items-center gap-3">
                <Link
                    :href="dashboard().url"
                    class="relative inline-flex items-center gap-2"
                    aria-label="Mercante Money"
                >
                    <img src="/img/logo.png" alt="Mercante" class="h-8 w-auto dark:invert" />
                </Link>
            </div>
            <div
                class="absolute left-0 right-0 z-10 m-auto flex w-full items-center justify-center"
            >
                <ul class="text-foreground flex items-center gap-5 text-sm font-medium">
                    <li>
                        <Link
                            :href="dashboard().url"
                            class="hover:text-muted-foreground transition"
                        >
                            {{ $t('common.navbar.dashboard') }}
                        </Link>
                    </li>
                    <li>
                        <Link
                            :href="transactions.index().url"
                            class="hover:text-muted-foreground transition"
                        >
                            {{ $t('common.navbar.transactions') }}
                        </Link>
                    </li>
                    <li>
                        <Link
                            :href="accountsIndex().url"
                            class="hover:text-muted-foreground transition"
                        >
                            {{ $t('common.navbar.accounts') }}
                        </Link>
                    </li>
                    <li>
                        <Link
                            :href="reportsIndex().url"
                            class="hover:text-muted-foreground transition"
                        >
                            {{ $t('common.navbar.reports') }}
                        </Link>
                    </li>
                    <li>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button
                                    type="button"
                                    :aria-label="$t('common.navbar.settings')"
                                    class="hover:text-muted-foreground flex items-center gap-1 transition"
                                >
                                    <SettingsIcon class="h-5 w-5" aria-hidden="true" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent class="w-44" align="end">
                                <DropdownMenuItem as-child>
                                    <Link :href="servicesIndex().url" class="w-full">
                                        {{ $t('common.settings_menu.services') }}
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem as-child>
                                    <Link :href="contacts().url" class="w-full">
                                        {{ $t('common.settings_menu.contacts') }}
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem as-child>
                                    <Link :href="categoriesIndex().url" class="w-full">
                                        {{ $t('common.settings_menu.categories') }}
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem as-child>
                                    <Link :href="tagsIndex().url" class="w-full">
                                        {{ $t('common.settings_menu.tags') }}
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </li>
                </ul>
            </div>
            <div class="relative z-20 flex items-center gap-2">
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <button type="button" :aria-label="userName" class="rounded-full">
                            <Avatar class="border-3 size-10">
                                <AvatarImage v-if="avatarUrl" :src="avatarUrl" :alt="userName" />
                                <AvatarFallback>{{ userInitials }}</AvatarFallback>
                            </Avatar>
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent class="w-52" align="end">
                        <!--
                            First, and only for the few who have it: whoever runs
                            the platform is also a customer of it, so this is the
                            one door between the two — not a separate login.
                        -->
                        <template v-if="isAdmin">
                            <DropdownMenuItem as-child>
                                <Link
                                    :href="adminDashboard().url"
                                    class="flex w-full items-center gap-2"
                                >
                                    <ShieldIcon class="size-4" aria-hidden="true" />
                                    <span>{{ $t('admin.title') }}</span>
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                        </template>
                        <DropdownMenuItem as-child>
                            <Link :href="profile().url" class="flex w-full items-center gap-2">
                                <span>{{ $t('common.profile.profile') }}</span>
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child>
                            <Link :href="preferences.url()" class="flex w-full items-center gap-2">
                                <span>{{ $t('common.profile.preferences') }}</span>
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child>
                            <Link :href="billing().url" class="flex w-full items-center gap-2">
                                <span>{{ $t('common.profile.billing') }}</span>
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child>
                            <Link
                                :href="logout().url"
                                method="post"
                                class="flex w-full items-center gap-2 text-red-600 focus:text-red-600"
                                @success="clearServiceWorkerCaches"
                            >
                                <span>{{ $t('common.profile.logout') }}</span>
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>
    </header>
</template>
