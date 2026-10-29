<script setup lang="ts">
import SignaturePadLib from 'signature_pad';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    label: string;
    processing?: boolean;
}>();

const emit = defineEmits<{
    save: [dataUrl: string];
}>();

const canvas = ref<HTMLCanvasElement | null>(null);
const empty = ref(true);
let pad: SignaturePadLib | null = null;
let observer: ResizeObserver | null = null;

// Keeps strokes sharp on high-density screens and survives rotation/resizing.
const resize = () => {
    if (!canvas.value || !pad) {
        return;
    }

    const data = pad.toData();
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    canvas.value.width = canvas.value.offsetWidth * ratio;
    canvas.value.height = canvas.value.offsetHeight * ratio;
    canvas.value.getContext('2d')?.scale(ratio, ratio);
    pad.clear();
    pad.fromData(data);
};

onMounted(() => {
    if (!canvas.value) {
        return;
    }

    pad = new SignaturePadLib(canvas.value, {
        minWidth: 0.8,
        maxWidth: 2.6,
        penColor: '#111827',
    });
    pad.addEventListener('endStroke', () => (empty.value = pad!.isEmpty()));
    observer = new ResizeObserver(resize);
    observer.observe(canvas.value);
    resize();
});

onBeforeUnmount(() => {
    observer?.disconnect();
    pad?.off();
});

const clear = () => {
    pad?.clear();
    empty.value = true;
};

const undo = () => {
    if (!pad) {
        return;
    }

    const data = pad.toData();
    data.pop();
    pad.fromData(data);
    empty.value = pad.isEmpty();
};

const save = () => {
    if (pad && !pad.isEmpty()) {
        emit('save', pad.toDataURL('image/png'));
    }
};
</script>

<template>
    <div class="grid gap-2">
        <canvas
            ref="canvas"
            class="h-56 w-full touch-none rounded-md border-2 border-dashed bg-white sm:h-64"
            :aria-label="`Area tanda tangan: ${props.label}`"
            data-test="signature-canvas"
        />
        <p class="text-xs text-muted-foreground">
            Tanda tangani di dalam kotak dengan jari atau stylus.
        </p>
        <div class="flex flex-wrap gap-2">
            <Button
                type="button"
                variant="outline"
                size="sm"
                :disabled="empty || processing"
                data-test="signature-undo"
                @click="undo"
            >
                Batalkan goresan terakhir
            </Button>
            <Button
                type="button"
                variant="outline"
                size="sm"
                :disabled="empty || processing"
                data-test="signature-clear"
                @click="clear"
            >
                Ulangi
            </Button>
            <Button
                type="button"
                size="sm"
                :disabled="empty || processing"
                data-test="signature-save"
                @click="save"
            >
                Simpan tanda tangan
            </Button>
        </div>
    </div>
</template>
