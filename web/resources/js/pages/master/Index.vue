<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import MasterDataController from '@/actions/App/Http/Controllers/MasterDataController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/master-data';

type Item = {
    id: number;
    code: string;
    name: string;
    active: boolean;
    used: number;
};

const props = defineProps<{
    sources: Item[];
    types: Item[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Master Source/Jenis', href: index() }],
    },
});

const page = usePage();
const errors = computed(() => page.props.errors as Record<string, string>);
const processing = ref(false);

// Editable copies, refreshed whenever the server sends new rows.
const drafts = reactive<Record<string, { name: string; active: boolean }>>({});
const sync = () => {
    for (const [kind, items] of [
        ['source', props.sources],
        ['type', props.types],
    ] as const) {
        for (const item of items) {
            drafts[`${kind}-${item.id}`] = {
                name: item.name,
                active: item.active,
            };
        }
    }
};
sync();
watch(() => [props.sources, props.types], sync);

const changed = (kind: 'source' | 'type', item: Item) => {
    const draft = drafts[`${kind}-${item.id}`];

    return draft.name !== item.name || draft.active !== item.active;
};

const save = (kind: 'source' | 'type', item: Item) => {
    const url =
        kind === 'source'
            ? MasterDataController.updateSource.url(item.id)
            : MasterDataController.updateType.url(item.id);

    processing.value = true;
    router.put(url, drafts[`${kind}-${item.id}`], {
        preserveScroll: true,
        onFinish: () => (processing.value = false),
    });
};

const newType = reactive({ code: '', name: '' });
const addType = () => {
    processing.value = true;
    router.post(MasterDataController.storeType.url(), newType, {
        preserveScroll: true,
        onSuccess: () => {
            newType.code = '';
            newType.name = '';
        },
        onFinish: () => (processing.value = false),
    });
};
</script>

<template>
    <Head title="Master Source/Jenis" />

    <div class="flex max-w-4xl flex-1 flex-col gap-8 p-4">
        <Heading
            title="Master Source dan Jenis Outreach"
            description="Kode tidak dapat diubah setelah dibuat. Data yang sudah dipakai tidak dihapus; nonaktifkan agar tidak ditawarkan pada Outreach baru. Versi historis tetap memakai nama saat difinalisasi."
        />

        <p
            v-if="errors.active"
            class="text-sm text-destructive"
            role="alert"
            data-test="master-error"
        >
            {{ errors.active }}
        </p>

        <section
            v-for="group in [
                {
                    kind: 'source' as const,
                    title: 'Source',
                    note: 'Tiga Source baseline menentukan alur form dan paket PDF, sehingga Source baru belum dapat ditambahkan.',
                    items: sources,
                },
                {
                    kind: 'type' as const,
                    title: 'Jenis Outreach',
                    note: 'Jenis tidak mengubah alur form; semua kombinasi Source × Jenis diperbolehkan.',
                    items: types,
                },
            ]"
            :key="group.kind"
            class="grid gap-3"
            :data-test="`master-${group.kind}`"
        >
            <div>
                <h3 class="font-medium">{{ group.title }}</h3>
                <p class="text-sm text-muted-foreground">{{ group.note }}</p>
            </div>
            <ul class="divide-y rounded-md border">
                <li
                    v-for="item in group.items"
                    :key="item.id"
                    class="grid gap-3 p-3 text-sm sm:grid-cols-[12rem_1fr_auto_auto] sm:items-center"
                    data-test="master-row"
                >
                    <div>
                        <p class="font-mono text-xs">{{ item.code }}</p>
                        <p class="text-xs text-muted-foreground">
                            Dipakai {{ item.used }} Outreach
                        </p>
                    </div>
                    <Input
                        v-model="drafts[`${group.kind}-${item.id}`].name"
                        maxlength="100"
                        :aria-label="`Nama ${item.code}`"
                    />
                    <label class="flex items-center gap-2">
                        <input
                            v-model="drafts[`${group.kind}-${item.id}`].active"
                            type="checkbox"
                            class="size-4"
                            data-test="master-active"
                        />
                        Aktif
                    </label>
                    <Button
                        size="sm"
                        variant="outline"
                        :disabled="processing || !changed(group.kind, item)"
                        data-test="master-save"
                        @click="save(group.kind, item)"
                    >
                        Simpan
                    </Button>
                </li>
            </ul>
            <InputError v-if="group.kind === 'type'" :message="errors.name" />
        </section>

        <section class="grid gap-3 rounded-md border p-4" data-test="add-type">
            <h3 class="font-medium">Tambah Jenis Outreach</h3>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="grid gap-1">
                    <Label for="new-type-code">Kode</Label>
                    <Input
                        id="new-type-code"
                        v-model="newType.code"
                        maxlength="50"
                        placeholder="CONTOH_JENIS"
                        class="font-mono uppercase"
                    />
                    <p class="text-xs text-muted-foreground">
                        Huruf kapital, angka, dan garis bawah. Tidak dapat
                        diubah setelah disimpan.
                    </p>
                    <InputError :message="errors.code" />
                </div>
                <div class="grid gap-1">
                    <Label for="new-type-name">Nama</Label>
                    <Input
                        id="new-type-name"
                        v-model="newType.name"
                        maxlength="100"
                    />
                </div>
            </div>
            <div>
                <Button
                    :disabled="processing || !newType.code || !newType.name"
                    data-test="add-type-submit"
                    @click="addType"
                >
                    Tambah
                </Button>
            </div>
        </section>
    </div>
</template>
