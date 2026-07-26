<script setup lang="ts">
import { EllipsisVerticalIcon } from '@lucide/vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

defineEmits<{ edit: []; delete: [] }>();
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <button
                type="button"
                class="text-muted-foreground transition hover:text-foreground"
                :aria-label="$t('common.actions.more')"
                @click.stop.prevent
            >
                <EllipsisVerticalIcon class="size-5" />
            </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" @click.stop.prevent>
            <DropdownMenuItem @select="$emit('edit')">
                {{ $t('common.actions.edit') }}
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem variant="destructive" @select="$emit('delete')">
                {{ $t('common.actions.delete') }}
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
