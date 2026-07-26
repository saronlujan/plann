<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { SettingsIcon, TimerOffIcon } from '@lucide/vue';
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { expireTrial } from '@/routes/dev';
import { index as billing } from '@/routes/billing';
import { contacts, dashboard, logout, preferences } from '@/routes';
import transactions from '@/routes/transactions';
import { index as settingsAccounts } from '@/routes/settings/accounts';
import { index as settingsCategories } from '@/routes/settings/categories';
import { index as settingsCurrencies } from '@/routes/settings/currencies';
import { index as settingsTags } from '@/routes/settings/tags';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

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
                <div class="flex items-center gap-2">
                    <p class="flex text-2xl text-primary">
                        pla<span class="block font-bold">nn</span>.money
                    </p>
                </div>
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
                        <Link :href="contacts().url" class="transition hover:text-muted-foreground">
                            {{ $t('common.navbar.contacts') }}
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
                                    <Link :href="settingsCategories().url" class="w-full">
                                        Categorias
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem as-child>
                                    <Link :href="settingsTags().url" class="w-full">Tags</Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem as-child>
                                    <Link :href="settingsAccounts().url" class="w-full">Contas</Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem as-child>
                                    <Link :href="settingsCurrencies().url" class="w-full">Moedas</Link>
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
