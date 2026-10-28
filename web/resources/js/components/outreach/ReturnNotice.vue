<script setup lang="ts">
export type Returned = {
    note: string | null;
    kepala: string;
    decided_at: string;
};

defineProps<{ returned: Returned }>();
</script>

<template>
    <section
        class="rounded-md border border-amber-500 bg-amber-50 p-4 text-sm text-amber-950 dark:bg-amber-950/30 dark:text-amber-100"
        data-test="return-notice"
    >
        <p class="font-medium">
            Dikembalikan oleh {{ returned.kepala }} pada
            {{
                new Date(returned.decided_at).toLocaleString('id-ID', {
                    dateStyle: 'long',
                    timeStyle: 'short',
                })
            }}
        </p>
        <p class="mt-1 whitespace-pre-line">{{ returned.note }}</p>
        <p class="mt-2 text-xs opacity-80">
            Perbaiki sesuai catatan, lalu lakukan Finalisasi Ulang dari langkah
            Review.
        </p>
    </section>
</template>
