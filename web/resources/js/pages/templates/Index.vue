<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Upload } from '@lucide/vue';
import { computed, ref } from 'vue';
import TemplateController from '@/actions/App/Http/Controllers/TemplateController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/templates';

type Version = {
    id: number;
    version: number;
    active: boolean;
    valid_from: string;
    valid_until: string | null;
    url: string;
};

const props = defineProps<{
    kepala: {
        id: number;
        name: string;
        active: boolean;
        has_nip: boolean;
        versions: Version[];
    }[];
    stamps: Version[];
    maxKilobytes: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Template TTD/Stempel', href: index() }],
    },
});

const page = usePage();
const errors = computed(() => page.props.errors as Record<string, string>);
const processing = ref(false);
// Which upload the latest error belongs to.
const target = ref<string | null>(null);

const date = (value: string | null) =>
    value
        ? new Date(value).toLocaleString('id-ID', {
              dateStyle: 'medium',
              timeStyle: 'short',
          })
        : '';

const upload = (key: string, url: string, event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) {
        return;
    }

    target.value = key;
    processing.value = true;
    router.post(
        url,
        { file },
        {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => {
                processing.value = false;
                input.value = '';
            },
        },
    );
};

const pickFile = (key: string) =>
    (
        document.getElementById(`upload-${key}`) as HTMLInputElement | null
    )?.click();

const deactivate = (url: string) => {
    processing.value = true;
    router.post(
        url,
        {},
        {
            preserveScroll: true,
            onFinish: () => (processing.value = false),
        },
    );
};

const activeOf = (versions: Version[]) =>
    versions.find((version) => version.active) ?? null;

const sections = computed(() => [
    ...props.kepala.map((kepala) => ({
        key: `kepala-${kepala.id}`,
        title: `Tanda tangan ${kepala.name}`,
        note: [
            kepala.active ? null : 'Akun nonaktif.',
            kepala.has_nip
                ? null
                : 'NIP belum diisi; Approve tetap ditolak sampai NIP diisi.',
        ]
            .filter(Boolean)
            .join(' '),
        versions: kepala.versions,
        uploadUrl: TemplateController.storeSignature.url(kepala.id),
        deactivateUrl: (id: number) =>
            TemplateController.deactivateSignature.url(id),
    })),
    {
        key: 'stamp',
        title: 'Stempel Kelurahan',
        note: 'Stempel aktif dicetak menimpa sisi kiri tanda tangan Lurah pada PDF Outreach yang disahkan sesudahnya. Pengesahan tanpa stempel aktif tetap berjalan.',
        versions: props.stamps,
        uploadUrl: TemplateController.storeStamp.url(),
        deactivateUrl: (id: number) =>
            TemplateController.deactivateStamp.url(id),
    },
]);
</script>

<template>
    <Head title="Template TTD/Stempel" />

    <div class="flex max-w-4xl flex-1 flex-col gap-8 p-4">
        <Heading
            title="Template tanda tangan Kepala dan stempel"
            :description="`Unggah gambar PNG atau JPG, maksimal ${maxKilobytes / 1024} MB. Versi baru langsung aktif dan versi sebelumnya dinonaktifkan; versi lama tidak dihapus dan pengesahan yang sudah ada tetap memakai versinya.`"
        />

        <section
            v-for="section in sections"
            :key="section.key"
            class="grid gap-3 rounded-md border p-4"
            :data-test="section.key"
        >
            <div>
                <h3 class="font-medium">{{ section.title }}</h3>
                <p v-if="section.note" class="text-sm text-muted-foreground">
                    {{ section.note }}
                </p>
            </div>

            <div
                v-if="activeOf(section.versions)"
                class="flex flex-wrap items-center gap-4"
            >
                <img
                    :src="activeOf(section.versions)!.url"
                    :alt="`${section.title} versi aktif`"
                    class="h-20 w-56 rounded border bg-white object-contain"
                />
                <div class="text-sm">
                    <p>
                        Versi {{ activeOf(section.versions)!.version }} aktif
                        sejak {{ date(activeOf(section.versions)!.valid_from) }}
                    </p>
                    <Button
                        class="mt-2"
                        size="sm"
                        variant="outline"
                        :disabled="processing"
                        data-test="deactivate"
                        @click="
                            deactivate(
                                section.deactivateUrl(
                                    activeOf(section.versions)!.id,
                                ),
                            )
                        "
                    >
                        Nonaktifkan
                    </Button>
                </div>
            </div>
            <p v-else class="text-sm text-destructive" data-test="no-active">
                Belum ada versi aktif.
            </p>

            <div class="flex flex-wrap items-center gap-3">
                <Button
                    type="button"
                    :disabled="processing"
                    data-test="upload-button"
                    @click="pickFile(section.key)"
                >
                    <Upload class="size-4" />
                    {{
                        activeOf(section.versions)
                            ? 'Unggah versi baru'
                            : 'Unggah gambar'
                    }}
                </Button>
                <span class="text-xs text-muted-foreground"
                    >PNG atau JPG, latar sebaiknya transparan atau putih.</span
                >
                <input
                    :id="`upload-${section.key}`"
                    type="file"
                    accept="image/png,image/jpeg"
                    :disabled="processing"
                    class="hidden"
                    data-test="upload"
                    @change="upload(section.key, section.uploadUrl, $event)"
                />
            </div>
            <InputError v-if="target === section.key" :message="errors.file" />

            <details v-if="section.versions.length > 1" class="text-sm">
                <summary class="cursor-pointer text-muted-foreground">
                    Riwayat versi
                </summary>
                <ul class="mt-2 grid gap-2">
                    <li
                        v-for="version in section.versions"
                        :key="version.id"
                        class="flex flex-wrap items-center gap-3"
                    >
                        <img
                            :src="version.url"
                            :alt="`Versi ${version.version}`"
                            class="h-10 w-28 rounded border bg-white object-contain"
                        />
                        <span>Versi {{ version.version }}</span>
                        <Badge
                            :variant="version.active ? 'default' : 'secondary'"
                        >
                            {{ version.active ? 'Aktif' : 'Nonaktif' }}
                        </Badge>
                        <span class="text-muted-foreground">
                            {{ date(version.valid_from) }}
                            <template v-if="version.valid_until">
                                – {{ date(version.valid_until) }}
                            </template>
                        </span>
                    </li>
                </ul>
            </details>
        </section>
    </div>
</template>
