<script setup lang="ts">
import { ref } from 'vue';

// The Mustahik or Kifayah form rendered by the same template as its PDF
// (D-069), sized to its content so it reads like the paper form.
defineProps<{ title: string; src: string }>();

const height = ref(600);
const loading = ref(true);

const fit = (event: Event) => {
    const frame = event.target as HTMLIFrameElement;
    const body = frame.contentDocument?.documentElement;

    if (body) {
        height.value = body.scrollHeight + 4;
    }

    loading.value = false;
};
</script>

<template>
    <div class="overflow-x-auto rounded-md bg-muted p-2 sm:p-4">
        <div class="relative mx-auto w-full max-w-[210mm] min-w-[180mm]">
            <p
                v-if="loading"
                class="absolute inset-x-0 top-6 text-center text-sm text-muted-foreground"
            >
                Memuat {{ title }}…
            </p>
            <iframe
                :src="src"
                :title="title"
                class="block w-full rounded-sm border bg-white shadow-sm"
                :style="{ height: `${height}px` }"
                data-test="form-document"
                @load="fit"
            />
        </div>
    </div>
</template>
