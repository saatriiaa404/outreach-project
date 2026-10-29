<script setup lang="ts">
export type Returned = {
    kind: 'RETURNED' | 'SPECIAL_UNLOCK_LOCKED';
    note: string | null;
    by: string;
    at: string | null;
};

defineProps<{ returned: Returned }>();
</script>

<template>
    <section
        class="rounded-md border border-amber-500 bg-amber-50 p-4 text-sm text-amber-950 dark:bg-amber-950/30 dark:text-amber-100"
        data-test="return-notice"
    >
        <p class="font-medium">
            {{
                returned.kind === 'RETURNED'
                    ? 'Dikembalikan oleh'
                    : 'Dikunci kembali setelah Special Unlock oleh'
            }}
            {{ returned.by }}
            <template v-if="returned.at">
                pada
                {{
                    new Date(returned.at).toLocaleString('id-ID', {
                        dateStyle: 'long',
                        timeStyle: 'short',
                    })
                }}
            </template>
        </p>
        <p class="mt-1 whitespace-pre-line">{{ returned.note }}</p>
        <p class="mt-2 text-xs opacity-80">
            Perbaiki sesuai catatan, lalu lakukan Finalisasi Ulang dari langkah
            Review.
        </p>
    </section>
</template>
