<script setup lang="ts">
import { computed } from 'vue';
import { Input } from '@/components/ui/input';

// A digits-only field (D-071). It is a text field, so scrolling the page or
// pressing the arrow keys never changes the number; Arrow Up/Down and Enter
// move to the previous or next field instead, like a paper form.
const props = defineProps<{ maxlength?: number }>();
const model = defineModel<string | number | null | undefined>();

const value = computed({
    get: () =>
        model.value === null || model.value === undefined
            ? ''
            : String(model.value),
    set: (text: string) => {
        model.value = text;
    },
});

const onInput = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const digits = input.value.replace(/\D/g, '').slice(0, props.maxlength);

    if (digits !== input.value) {
        input.value = digits;
    }

    value.value = digits;
};

const FIELDS =
    'input:not([type=hidden]):not([type=file]):not([disabled]):not([readonly]), select:not([disabled]), textarea:not([disabled])';

const move = (event: KeyboardEvent) => {
    const step =
        event.key === 'ArrowUp'
            ? -1
            : event.key === 'ArrowDown' || event.key === 'Enter'
              ? 1
              : 0;

    if (step === 0) {
        return;
    }

    event.preventDefault();
    const input = event.target as HTMLElement;
    const scope = input.closest('form') ?? document.body;
    const fields = [...scope.querySelectorAll<HTMLElement>(FIELDS)].filter(
        (field) => field.offsetParent !== null,
    );
    const next = fields[fields.indexOf(input) + step];
    next?.focus();

    if (next instanceof HTMLInputElement && next.type === 'text') {
        next.select();
    }
};
</script>

<template>
    <Input
        :model-value="value"
        type="text"
        inputmode="numeric"
        autocomplete="off"
        :maxlength="maxlength"
        @input="onInput"
        @keydown="move"
    />
</template>
