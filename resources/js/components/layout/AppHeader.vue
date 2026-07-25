<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Settings } from 'lucide-vue-next';
import LocaleSelector from '@/components/LocaleSelector.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { contacts, dashboard, logout, preferences, settings } from '@/routes';
import transactions from '@/routes/transactions';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
</script>

<template>
    <header class="w-full border-b border-zinc-200 bg-white/95 backdrop-blur">
        <div class="flex w-full items-center justify-between gap-4 px-4 py-4 lg:px-6 xl:px-8">
            <div class="relative z-20 flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <p class="flex text-2xl text-zinc-950">
                        pla<span class="block font-bold">nn</span>.money
                    </p>
                </div>
            </div>
            <div
                class="absolute right-0 left-0 z-10 m-auto flex w-full items-center justify-center"
            >
                <ul class="flex items-center gap-5 text-sm font-medium text-zinc-950">
                    <li>
                        <Link :href="dashboard().url" class="transition hover:text-zinc-500">
                            {{ $t('common.navbar.dashboard') }}
                        </Link>
                    </li>
                    <li>
                        <Link
                            :href="transactions.index().url"
                            class="transition hover:text-zinc-500"
                        >
                            {{ $t('common.navbar.transactions') }}
                        </Link>
                    </li>
                    <li>
                        <Link :href="contacts().url" class="transition hover:text-zinc-500">
                            {{ $t('common.navbar.contacts') }}
                        </Link>
                    </li>
                    <li>
                        <Link
                            :href="settings().url"
                            class="flex items-center gap-1 transition hover:text-zinc-500"
                        >
                            <Settings class="h-5 w-5" />
                        </Link>
                    </li>
                </ul>
            </div>
            <div class="relative z-20 flex items-center gap-2">
                <LocaleSelector />

                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Avatar class="size-9">
                            <AvatarImage
                                src="https://avatars.githubusercontent.com/u/7363056?v=4"
                                alt="Saron Lujan"
                            />
                            <AvatarFallback>{{ 'SL' }}</AvatarFallback>
                        </Avatar>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent class="w-52" align="end">
                        <DropdownMenuItem as-child>
                            <Link :href="preferences.url()" class="flex w-full items-center gap-2">
                                <span>{{ $t('common.profile.preferences') }}</span>
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
