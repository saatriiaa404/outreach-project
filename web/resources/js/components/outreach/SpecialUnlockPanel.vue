<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import SpecialUnlockRequestController from '@/actions/App/Http/Controllers/SpecialUnlockRequestController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';

export type SpecialUnlockRequestRow = {
    id: number;
    requester: string;
    requester_role: 'PETUGAS' | 'KEPALA' | 'ADMIN';
    reason: string;
    status: 'PENDING' | 'PROCESSING' | 'REJECTED' | 'COMPLETED' | 'CANCELLED';
    status_label: string;
    admin: string | null;
    processed_reason: string | null;
    created_at: string | null;
    processed_at: string | null;
};

export type SpecialUnlock = {
    canRequest: boolean;
    openBy: string | null;
    requests: (SpecialUnlockRequestRow & { can_cancel: boolean })[];
};

const props = defineProps<{
    outreachId: number;
    specialUnlock: SpecialUnlock;
}>();

const asking = ref(false);
const reason = ref('');
const processing = ref(false);
const page = usePage();
const errors = computed(() => page.props.errors as Record<string, string>);

const dateTime = (value: string | null) =>
    value
        ? new Date(value).toLocaleString('id-ID', {
              dateStyle: 'long',
              timeStyle: 'short',
          })
        : '';

const submit = () => {
    if (reason.value.trim() === '') {
        return;
    }

    processing.value = true;
    router.post(
        SpecialUnlockRequestController.store.url(props.outreachId),
        { reason: reason.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                asking.value = false;
                reason.value = '';
            },
            onFinish: () => (processing.value = false),
        },
    );
};

const cancel = (id: number) => {
    processing.value = true;
    router.post(
        SpecialUnlockRequestController.cancel.url(id),
        {},
        {
            preserveScroll: true,
            onFinish: () => (processing.value = false),
        },
    );
};
</script>

<template>
    <section
        v-if="
            specialUnlock.canRequest ||
            specialUnlock.openBy ||
            specialUnlock.requests.length
        "
        class="grid gap-2"
        data-test="special-unlock"
    >
        <h3 class="font-medium">Special Unlock</h3>
        <p class="text-sm text-muted-foreground">
            Koreksi atas Outreach yang sudah difinalisasi dilakukan Admin
            melalui Special Unlock. Permintaan tidak mengubah status Outreach.
        </p>
        <p
            v-if="errors.special_unlock"
            class="text-sm text-destructive"
            role="alert"
            data-test="special-unlock-error"
        >
            {{ errors.special_unlock }}
        </p>
        <p
            v-if="specialUnlock.openBy"
            class="text-sm"
            data-test="special-unlock-open"
        >
            Sudah ada permintaan terbuka dari {{ specialUnlock.openBy }}.
            Permintaan baru dapat dibuat setelah permintaan itu selesai.
        </p>
        <div v-if="specialUnlock.canRequest">
            <Button
                variant="outline"
                data-test="request-special-unlock"
                @click="asking = true"
            >
                Minta Special Unlock
            </Button>
        </div>

        <ul
            v-if="specialUnlock.requests.length"
            class="divide-y rounded-md border text-sm"
        >
            <li
                v-for="item in specialUnlock.requests"
                :key="item.id"
                class="grid gap-1 p-3"
                data-test="special-unlock-request"
            >
                <p>
                    <span class="font-medium">{{ item.status_label }}</span>
                    · {{ item.requester }} · {{ dateTime(item.created_at) }}
                </p>
                <p class="whitespace-pre-line">Alasan: {{ item.reason }}</p>
                <p v-if="item.admin" class="text-muted-foreground">
                    Admin: {{ item.admin }}
                </p>
                <p
                    v-if="item.processed_reason"
                    class="whitespace-pre-line"
                    data-test="processed-reason"
                >
                    Alasan penolakan: {{ item.processed_reason }}
                </p>
                <div v-if="item.can_cancel">
                    <Button
                        size="sm"
                        variant="outline"
                        :disabled="processing"
                        data-test="cancel-special-unlock"
                        @click="cancel(item.id)"
                    >
                        Batalkan permintaan
                    </Button>
                </div>
            </li>
        </ul>
    </section>

    <Dialog v-model:open="asking">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Minta Special Unlock?</DialogTitle>
                <DialogDescription>
                    Permintaan masuk ke antrean Admin. Anda dapat membatalkannya
                    selama Admin belum mulai memproses.
                </DialogDescription>
            </DialogHeader>
            <div class="grid gap-2">
                <Label for="special-unlock-reason">Alasan (wajib)</Label>
                <textarea
                    id="special-unlock-reason"
                    v-model="reason"
                    rows="4"
                    maxlength="15000"
                    class="min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none md:text-sm"
                    placeholder="Jelaskan data apa yang perlu dikoreksi dan mengapa"
                    data-test="special-unlock-reason"
                />
                <p v-if="errors.reason" class="text-sm text-destructive">
                    {{ errors.reason }}
                </p>
            </div>
            <DialogFooter class="gap-2">
                <Button variant="outline" @click="asking = false">
                    Batal
                </Button>
                <Button
                    :disabled="processing || reason.trim() === ''"
                    data-test="confirm-special-unlock"
                    @click="submit"
                >
                    Kirim permintaan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
