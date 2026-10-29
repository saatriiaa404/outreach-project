<script lang="ts">
import { router } from '@inertiajs/vue3';

// Pages visited in this tab since the app loaded, counted once for the whole
// app; the first page has no in-app page to go back to.
let visits = 0;
router.on('navigate', () => {
    visits++;
});
</script>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { toUrl } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const props = withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();

// Back like the browser button; without an earlier page, up to the nearest
// breadcrumb that is not this page (e.g. the Outreach list), or the dashboard.
const back = () => {
    if (visits > 1) {
        window.history.back();

        return;
    }

    const here = window.location.pathname;
    const parent = [...props.breadcrumbs]
        .reverse()
        .map((item) => new URL(toUrl(item.href), window.location.origin))
        .find((url) => url.pathname !== here);
    router.visit(parent ? parent.pathname + parent.search : dashboard.url());
};
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex min-w-0 items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <Button
            v-if="page.component !== 'Dashboard'"
            variant="outline"
            size="sm"
            class="ml-auto shrink-0"
            data-test="back"
            @click="back"
        >
            <ArrowLeft class="size-4" />
            Kembali
        </Button>
    </header>
</template>
