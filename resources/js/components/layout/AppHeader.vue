<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { SettingsIcon, TimerOffIcon } from '@lucide/vue';
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { clearServiceWorkerCaches } from '@/lib/pwa';
import { accounts as accountsIndex, contacts, dashboard, logout, preferences } from '@/routes';
import { index as billing } from '@/routes/billing';
import { index as categoriesIndex } from '@/routes/categories';
import { expireTrial } from '@/routes/dev';
import { edit as profile } from '@/routes/profile';
import { index as reportsIndex } from '@/routes/reports';
import { index as tagsIndex } from '@/routes/tags';
import transactions from '@/routes/transactions';

const page = usePage<{
    auth: { user: { name: string; avatar_url: string | null } | null };
    dev: boolean;
}>();

const isDev = computed(() => page.props.dev === true);

function expireTrialNow(): void {
    router.post(expireTrial().url);
}

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
    <header class="w-full border-b border-border bg-background/95 backdrop-blur">
        <div class="flex w-full items-center justify-between gap-4 px-4 py-4 lg:px-6 xl:px-8">
            <div class="relative z-20 flex items-center gap-3">
                <!-- <div class="flex flex-col items-start">
                    <p class="flex text-2xl text-primary">
                        <strong class="tracking-widest">mercante</strong>
                    </p>
                    <span class="-mt-2 ml-2 opacity-50">money</span>
                </div> -->
                <Link
                    :href="dashboard().url"
                    class="relative inline-flex items-center gap-2"
                    aria-label="Mercante Money"
                >
                    <img src="/img/logo.png" alt="Mercante" class="h-7 w-auto dark:invert" />
                    <span
                        class="rounded-full bg-primary px-2 py-0.5 text-xs leading-none font-bold tracking-wide text-white"
                    >
                        money
                    </span>
                </Link>
            </div>
            <div
                class="absolute right-0 left-0 z-10 m-auto flex w-full items-center justify-center"
            >
                <ul class="flex items-center gap-5 text-sm font-medium text-foreground">
                    <li>
                        <Link
                            :href="dashboard().url"
                            class="transition hover:text-muted-foreground"
                        >
                            {{ $t('common.navbar.dashboard') }}
                        </Link>
                    </li>
                    <li>
                        <Link
                            :href="transactions.index().url"
                            class="transition hover:text-muted-foreground"
                        >
                            {{ $t('common.navbar.transactions') }}
                        </Link>
                    </li>
                    <li>
                        <Link
                            :href="accountsIndex().url"
                            class="transition hover:text-muted-foreground"
                        >
                            {{ $t('common.navbar.accounts') }}
                        </Link>
                    </li>
                    <li>
                        <Link
                            :href="reportsIndex().url"
                            class="transition hover:text-muted-foreground"
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
                                    class="flex items-center gap-1 transition hover:text-muted-foreground"
                                >
                                    <SettingsIcon class="h-5 w-5" aria-hidden="true" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent class="w-44" align="end">
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
                <button
                    v-if="isDev"
                    type="button"
                    title="DEV: vencer o período de teste agora"
                    class="flex items-center gap-1.5 rounded-full border border-dashed border-amber-500 px-3 py-1.5 text-xs font-medium text-amber-600 transition hover:bg-amber-500/10 dark:text-amber-400"
                    @click="expireTrialNow"
                >
                    <TimerOffIcon class="size-3.5" aria-hidden="true" />
                    Vencer trial
                </button>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <button type="button" :aria-label="userName" class="rounded-full">
                            <Avatar class="size-10 border-3">
                                <AvatarImage v-if="avatarUrl" :src="avatarUrl" :alt="userName" />
                                <AvatarFallback>{{ userInitials }}</AvatarFallback>
                            </Avatar>
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent class="w-52" align="end">
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
