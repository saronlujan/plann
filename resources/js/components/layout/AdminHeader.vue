<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@lucide/vue';
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import { dashboard, logout } from '@/routes';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as adminTenants } from '@/routes/admin/tenants';
import { edit as profile } from '@/routes/profile';

const page = usePage<{
    auth: { user: { name: string; avatar_url: string | null } | null };
}>();

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

/**
 * Two sections and no more. The admin area answers "who is using this and are
 * they paying" — anything else belongs in the app the customers use.
 */
const links = computed(() => [
    { label: 'admin.nav.dashboard', href: adminDashboard().url },
    { label: 'admin.nav.tenants', href: adminTenants().url },
]);

const currentUrl = computed(() => page.url.split('?')[0]);

function isActive(href: string): boolean {
    return href === '/admin' ? currentUrl.value === href : currentUrl.value.startsWith(href);
}
</script>

<template>
    <!--
        Same chrome as the app the customers use: the person here is one of them,
        with a workspace and a plan of their own. Only the navigation differs, and
        the badge beside the mark says which side you are on.
    -->
    <header class="w-full border-b border-border bg-background/95 backdrop-blur">
        <div class="flex w-full items-center justify-between gap-4 px-4 py-4 lg:px-6 xl:px-8">
            <div class="relative z-20 flex items-center gap-3">
                <Link
                    :href="adminDashboard().url"
                    class="relative inline-flex items-center gap-2"
                    aria-label="Mercante Money"
                >
                    <img src="/img/logo.png" alt="Mercante" class="h-7 w-auto dark:invert" />
                    <span
                        class="rounded-full bg-foreground px-2 py-0.5 text-xs leading-none font-bold tracking-wide text-background"
                    >
                        {{ $t('admin.title').toLowerCase() }}
                    </span>
                </Link>
            </div>

            <div
                class="absolute right-0 left-0 z-10 m-auto flex w-full items-center justify-center"
            >
                <ul class="flex items-center gap-5 text-sm font-medium text-foreground">
                    <li v-for="link in links" :key="link.href">
                        <Link
                            :href="link.href"
                            :class="
                                cn(
                                    'transition',
                                    isActive(link.href)
                                        ? 'font-medium'
                                        : 'text-muted-foreground hover:text-foreground',
                                )
                            "
                        >
                            {{ $t(link.label) }}
                        </Link>
                    </li>
                </ul>
            </div>

            <div class="relative z-20 flex items-center gap-2">
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
                            <Link
                                :href="dashboard().url"
                                class="flex w-full items-center gap-2"
                            >
                                <ArrowLeftIcon class="size-4" aria-hidden="true" />
                                <span>{{ $t('admin.nav.back_to_app') }}</span>
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <!--
                            The customer profile page, reused as it stands: an
                            admin has the same name, address and password as
                            anyone else, and a second screen would earn nothing.
                        -->
                        <DropdownMenuItem as-child>
                            <Link :href="profile().url" class="flex w-full items-center gap-2">
                                <span>{{ $t('common.profile.profile') }}</span>
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
