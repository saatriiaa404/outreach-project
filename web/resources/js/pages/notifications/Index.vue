<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import NotificationController from '@/actions/App/Http/Controllers/NotificationController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/notifications';

type Notification = {
    id: number;
    type: 'OUTREACH_FINALIZED' | 'OUTREACH_RETURNED' | 'OUTREACH_COMPLETED';
    title: string;
    message: string;
    read: boolean;
    created_at: string | null;
};

const props = defineProps<{
    notifications: {
        data: Notification[];
        links: { url: string | null; label: string; active: boolean }[];
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Notifikasi', href: index() }],
    },
});

const typeLabels: Record<Notification['type'], string> = {
    OUTREACH_FINALIZED: 'Diajukan',
    OUTREACH_RETURNED: 'Dikembalikan',
    OUTREACH_COMPLETED: 'Disahkan',
};

const dateTime = (value: string | null) =>
    value
        ? new Date(value).toLocaleString('id-ID', {
              dateStyle: 'medium',
              timeStyle: 'short',
          })
        : '';

const hasUnread = () => props.notifications.data.some((item) => !item.read);
const readAll = () =>
    router.post(
        NotificationController.readAll.url(),
        {},
        { preserveScroll: true },
    );
</script>

<template>
    <Head title="Notifikasi" />

    <div class="flex max-w-3xl flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Notifikasi"
                description="Pemberitahuan in-app. Status terkini selalu mengikuti halaman Outreach."
            />
            <Button
                v-if="hasUnread()"
                variant="outline"
                data-test="read-all"
                @click="readAll"
            >
                Tandai semua dibaca
            </Button>
        </div>

        <p
            v-if="notifications.data.length === 0"
            class="text-sm text-muted-foreground"
        >
            Belum ada notifikasi.
        </p>

        <ul v-else class="divide-y rounded-md border">
            <li v-for="item in notifications.data" :key="item.id">
                <Link
                    :href="NotificationController.open.url(item.id)"
                    class="block p-3 text-sm hover:bg-muted/50"
                    :class="{ 'bg-muted/30': !item.read }"
                    data-test="notification"
                >
                    <p class="flex flex-wrap items-center gap-2">
                        <span
                            v-if="!item.read"
                            class="size-2 rounded-full bg-primary"
                            aria-label="Belum dibaca"
                        />
                        <span :class="{ 'font-medium': !item.read }">{{
                            item.title
                        }}</span>
                        <span class="text-xs text-muted-foreground"
                            >{{ typeLabels[item.type] }} ·
                            {{ dateTime(item.created_at) }}</span
                        >
                    </p>
                    <p class="mt-1 whitespace-pre-line text-muted-foreground">
                        {{ item.message }}
                    </p>
                </Link>
            </li>
        </ul>

        <nav
            v-if="notifications.links.length > 3"
            class="flex flex-wrap gap-1 text-sm"
        >
            <Link
                v-for="link in notifications.links"
                :key="link.label"
                :href="link.url ?? ''"
                class="rounded border px-2 py-1"
                :class="{
                    'pointer-events-none opacity-50': !link.url,
                    'bg-primary text-primary-foreground': link.active,
                }"
                ><span v-html="link.label"
            /></Link>
        </nav>
    </div>
</template>
