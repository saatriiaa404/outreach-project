<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Check, ChevronDown } from '@lucide/vue';
import { computed } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

export type FilterOption = { value: string | number; label: string };

// A column header that filters the table, like the filter chips of a file
// manager (D-072): pick a value, or "Semua" to clear it. The choice lives in
// the URL, so the other filters, the page's state and the Back button keep
// working; the page number starts over.
const props = defineProps<{
    label: string;
    param: string;
    options: FilterOption[];
    selected: string | number | null;
    // Sent for "Semua" when the page has its own default (the Kepala's queue).
    allValue?: string;
}>();

const active = computed(() =>
    props.options.find((option) => option.value === props.selected),
);

const pick = (value: string | number | null) => {
    const query = Object.fromEntries(
        new URLSearchParams(window.location.search),
    );
    delete query.page;

    if (value === null && props.allValue !== undefined) {
        query[props.param] = props.allValue;
    } else if (value === null) {
        delete query[props.param];
    } else {
        query[props.param] = String(value);
    }

    router.get(window.location.pathname, query, {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger
            class="-mx-1 inline-flex items-center gap-1 rounded px-1 font-medium hover:bg-muted"
            :class="{ 'text-primary': active }"
            :data-test="`filter-${param}`"
        >
            {{ label }}
            <span
                v-if="active"
                class="max-w-32 truncate rounded bg-primary/10 px-1.5 text-xs font-normal"
                >{{ active.label }}</span
            >
            <ChevronDown class="size-3.5 opacity-60" />
        </DropdownMenuTrigger>
        <DropdownMenuContent align="start" class="min-w-44">
            <DropdownMenuItem @select="pick(null)">
                <Check class="size-4" :class="{ invisible: active }" />
                Semua
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem
                v-for="option in options"
                :key="option.value"
                :data-test="`filter-${param}-${option.value}`"
                @select="pick(option.value)"
            >
                <Check
                    class="size-4"
                    :class="{ invisible: option.value !== selected }"
                />
                {{ option.label }}
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
