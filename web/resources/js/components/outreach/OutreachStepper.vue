<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { edit } from '@/routes/outreaches';

export type StepState = 'complete' | 'incomplete' | 'untouched' | 'upcoming';

export type Step = {
    key: string;
    title: string;
    state: StepState;
    missing?: number;
};

const props = defineProps<{
    outreachId: number;
    steps: Step[];
    step: string;
}>();

// Shared by every step page while the Admin edits under Special Unlock (D-056).
const page = usePage<{
    unlockSession?: { admin: string; reason: string | null } | null;
}>();
const unlockSession = computed(() => page.props.unlockSession ?? null);

const stepIndex = computed(() =>
    props.steps.findIndex((item) => item.key === props.step),
);

const stateMarker: Record<StepState, string> = {
    complete: '✓',
    incomplete: '!',
    untouched: '○',
    upcoming: '–',
};

const stateLabel: Record<StepState, string> = {
    complete: 'selesai',
    incomplete: 'belum lengkap',
    untouched: 'belum disentuh',
    upcoming: 'belum tersedia',
};
</script>

<template>
    <nav class="lg:w-60 lg:shrink-0" aria-label="Langkah pengisian">
        <p
            v-if="unlockSession"
            class="mb-3 rounded-md border border-amber-500 bg-amber-50 p-2 text-xs text-amber-950 dark:bg-amber-950/30 dark:text-amber-100"
            data-test="unlock-banner"
        >
            <span class="font-medium">Special Unlock aktif.</span>
            Setiap perubahan dicatat dengan alasan sesi ini. Tutup dengan Lock
            atau Lock &amp; Ajukan di langkah Review.
            <span class="mt-1 block whitespace-pre-line"
                >Alasan: {{ unlockSession.reason }}</span
            >
        </p>
        <p class="text-sm text-muted-foreground lg:hidden">
            Langkah {{ stepIndex + 1 }} dari {{ steps.length }}:
            <span class="font-medium text-foreground">{{
                steps[stepIndex]?.title
            }}</span>
        </p>
        <ol class="hidden space-y-1 lg:block">
            <li v-for="(item, i) in steps" :key="item.key">
                <span
                    v-if="item.state === 'upcoming'"
                    class="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-muted-foreground"
                    :title="stateLabel[item.state]"
                >
                    <span class="w-4 text-center">–</span>
                    {{ i + 1 }}. {{ item.title }}
                </span>
                <Link
                    v-else
                    :href="edit(outreachId, { query: { step: item.key } })"
                    class="flex items-center gap-2 rounded-md px-3 py-2 text-sm hover:bg-muted"
                    :class="{ 'bg-muted font-medium': item.key === step }"
                    :title="
                        item.state === 'incomplete'
                            ? `belum lengkap: ${item.missing} isian`
                            : stateLabel[item.state]
                    "
                >
                    <span class="w-4 text-center">{{
                        item.key === step ? '●' : stateMarker[item.state]
                    }}</span>
                    {{ i + 1 }}. {{ item.title }}
                </Link>
            </li>
        </ol>
    </nav>
</template>
