<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import UserController from '@/actions/App/Http/Controllers/UserController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/users';

type Role = 'PETUGAS' | 'KEPALA' | 'ADMIN';
type Account = {
    id: number;
    name: string;
    email: string;
    role: Role;
    status: 'ACTIVE' | 'INACTIVE';
    nip: string | null;
    nip_used: boolean;
    is_self: boolean;
    last_login_at: string | null;
};

defineProps<{ users: Account[] }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Pengguna', href: index() }],
    },
});

const roleLabels: Record<Role, string> = {
    PETUGAS: 'Petugas',
    KEPALA: 'Kepala',
    ADMIN: 'Admin',
};

const page = usePage();
const errors = computed(() => page.props.errors as Record<string, string>);
const processing = ref(false);

const creating = ref(false);
const createForm = reactive({
    name: '',
    email: '',
    role: 'PETUGAS' as Role,
    nip: '',
    password: '',
    password_confirmation: '',
});

const editing = ref<Account | null>(null);
const editForm = reactive({
    name: '',
    email: '',
    status: 'ACTIVE' as Account['status'],
    nip: '',
    nip_correction_reason: '',
});
const nipCorrection = computed(
    () =>
        editing.value?.role === 'KEPALA' &&
        editing.value.nip_used &&
        (editForm.nip || null) !== editing.value.nip,
);

const resetting = ref<Account | null>(null);
const resetForm = reactive({ password: '', password_confirmation: '' });

const finish = (close: () => void) => ({
    preserveScroll: true,
    onSuccess: close,
    onFinish: () => (processing.value = false),
});

const create = () => {
    processing.value = true;
    router.post(
        UserController.store.url(),
        createForm,
        finish(() => {
            creating.value = false;
            Object.assign(createForm, {
                name: '',
                email: '',
                role: 'PETUGAS',
                nip: '',
                password: '',
                password_confirmation: '',
            });
        }),
    );
};

const openEdit = (account: Account) => {
    editing.value = account;
    Object.assign(editForm, {
        name: account.name,
        email: account.email,
        status: account.status,
        nip: account.nip ?? '',
        nip_correction_reason: '',
    });
};

const update = () => {
    processing.value = true;
    router.put(
        UserController.update.url(editing.value!.id),
        editForm,
        finish(() => (editing.value = null)),
    );
};

const reset = () => {
    processing.value = true;
    router.put(
        UserController.resetPassword.url(resetting.value!.id),
        resetForm,
        finish(() => {
            resetting.value = null;
            resetForm.password = '';
            resetForm.password_confirmation = '';
        }),
    );
};
</script>

<template>
    <Head title="Pengguna" />

    <div class="flex max-w-5xl flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Pengguna"
                description="Akun dibuat dan dikelola Admin. Akun tidak dihapus, hanya dinonaktifkan. Hanya satu Kepala yang boleh aktif."
            />
            <Button data-test="user-create" @click="creating = true"
                >Tambah pengguna</Button
            >
        </div>

        <div class="overflow-x-auto rounded-md border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-3 py-2 font-medium">Nama</th>
                        <th class="px-3 py-2 font-medium">Email</th>
                        <th class="px-3 py-2 font-medium">Peran</th>
                        <th class="px-3 py-2 font-medium">NIP</th>
                        <th class="px-3 py-2 font-medium">Status</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="account in users"
                        :key="account.id"
                        class="border-t"
                        data-test="user-row"
                    >
                        <td class="px-3 py-2">
                            {{ account.name }}
                            <span
                                v-if="account.is_self"
                                class="text-muted-foreground"
                                >(Anda)</span
                            >
                        </td>
                        <td class="px-3 py-2">{{ account.email }}</td>
                        <td class="px-3 py-2">
                            {{ roleLabels[account.role] }}
                        </td>
                        <td class="px-3 py-2 font-mono text-xs">
                            {{
                                account.role === 'KEPALA'
                                    ? (account.nip ?? '—')
                                    : ''
                            }}
                        </td>
                        <td class="px-3 py-2">
                            <Badge
                                :variant="
                                    account.status === 'ACTIVE'
                                        ? 'default'
                                        : 'secondary'
                                "
                            >
                                {{
                                    account.status === 'ACTIVE'
                                        ? 'Aktif'
                                        : 'Nonaktif'
                                }}
                            </Badge>
                        </td>
                        <td class="px-3 py-2 text-right whitespace-nowrap">
                            <Button
                                size="sm"
                                variant="outline"
                                data-test="user-edit"
                                @click="openEdit(account)"
                                >Ubah</Button
                            >
                            <Button
                                size="sm"
                                variant="ghost"
                                data-test="user-reset"
                                @click="resetting = account"
                                >Reset password</Button
                            >
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <Dialog v-model:open="creating">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Tambah pengguna</DialogTitle>
                <DialogDescription>
                    Peran tidak dapat diubah setelah akun dibuat. Pengguna
                    mengganti password sendiri dari pengaturan.
                </DialogDescription>
            </DialogHeader>
            <div class="grid gap-3">
                <div class="grid gap-1">
                    <Label for="create-name">Nama</Label>
                    <Input id="create-name" v-model="createForm.name" />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid gap-1">
                    <Label for="create-email">Email</Label>
                    <Input
                        id="create-email"
                        v-model="createForm.email"
                        type="email"
                    />
                    <InputError :message="errors.email" />
                </div>
                <div class="grid gap-1">
                    <Label for="create-role">Peran</Label>
                    <select
                        id="create-role"
                        v-model="createForm.role"
                        class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                    >
                        <option
                            v-for="(label, value) in roleLabels"
                            :key="value"
                            :value="value"
                        >
                            {{ label }}
                        </option>
                    </select>
                    <InputError :message="errors.role ?? errors.status" />
                </div>
                <div v-if="createForm.role === 'KEPALA'" class="grid gap-1">
                    <Label for="create-nip">NIP</Label>
                    <Input
                        id="create-nip"
                        v-model="createForm.nip"
                        inputmode="numeric"
                        maxlength="18"
                    />
                    <InputError :message="errors.nip" />
                </div>
                <div class="grid gap-1">
                    <Label for="create-password">Password awal</Label>
                    <Input
                        id="create-password"
                        v-model="createForm.password"
                        type="password"
                        autocomplete="new-password"
                    />
                    <InputError :message="errors.password" />
                </div>
                <div class="grid gap-1">
                    <Label for="create-password-confirmation"
                        >Ulangi password</Label
                    >
                    <Input
                        id="create-password-confirmation"
                        v-model="createForm.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                    />
                </div>
            </div>
            <DialogFooter class="gap-2">
                <Button variant="outline" @click="creating = false"
                    >Batal</Button
                >
                <Button
                    :disabled="processing"
                    data-test="user-create-submit"
                    @click="create"
                    >Simpan</Button
                >
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog
        :open="editing !== null"
        @update:open="(value) => !value && (editing = null)"
    >
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Ubah {{ editing?.name }}</DialogTitle>
                <DialogDescription>
                    Peran: {{ editing ? roleLabels[editing.role] : '' }} (tidak
                    dapat diubah). Perubahan dicatat pada audit.
                </DialogDescription>
            </DialogHeader>
            <div class="grid gap-3">
                <div class="grid gap-1">
                    <Label for="edit-name">Nama</Label>
                    <Input id="edit-name" v-model="editForm.name" />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid gap-1">
                    <Label for="edit-email">Email</Label>
                    <Input
                        id="edit-email"
                        v-model="editForm.email"
                        type="email"
                    />
                    <InputError :message="errors.email" />
                </div>
                <div class="grid gap-1">
                    <Label for="edit-status">Status</Label>
                    <select
                        id="edit-status"
                        v-model="editForm.status"
                        class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                        data-test="user-status"
                    >
                        <option value="ACTIVE">Aktif</option>
                        <option value="INACTIVE">Nonaktif</option>
                    </select>
                    <InputError :message="errors.status" />
                </div>
                <div v-if="editing?.role === 'KEPALA'" class="grid gap-1">
                    <Label for="edit-nip">NIP</Label>
                    <Input
                        id="edit-nip"
                        v-model="editForm.nip"
                        inputmode="numeric"
                        maxlength="18"
                    />
                    <InputError :message="errors.nip" />
                </div>
                <div v-if="nipCorrection" class="grid gap-1">
                    <Label for="edit-nip-reason"
                        >Alasan koreksi administratif (wajib)</Label
                    >
                    <p class="text-xs text-muted-foreground">
                        NIP ini sudah tercetak pada pengesahan. PDF lama akan
                        menampilkan NIP baru saat diunduh.
                    </p>
                    <textarea
                        id="edit-nip-reason"
                        v-model="editForm.nip_correction_reason"
                        rows="3"
                        class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                        data-test="nip-reason"
                    />
                    <InputError :message="errors.nip_correction_reason" />
                </div>
            </div>
            <DialogFooter class="gap-2">
                <Button variant="outline" @click="editing = null">Batal</Button>
                <Button
                    :disabled="processing"
                    data-test="user-edit-submit"
                    @click="update"
                    >Simpan</Button
                >
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog
        :open="resetting !== null"
        @update:open="(value) => !value && (resetting = null)"
    >
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Reset password {{ resetting?.name }}</DialogTitle>
                <DialogDescription>
                    Sampaikan password ini kepada pengguna secara langsung dan
                    minta ia menggantinya dari pengaturan.
                </DialogDescription>
            </DialogHeader>
            <div class="grid gap-3">
                <div class="grid gap-1">
                    <Label for="reset-password">Password baru</Label>
                    <Input
                        id="reset-password"
                        v-model="resetForm.password"
                        type="password"
                        autocomplete="new-password"
                    />
                    <InputError :message="errors.password" />
                </div>
                <div class="grid gap-1">
                    <Label for="reset-password-confirmation"
                        >Ulangi password</Label
                    >
                    <Input
                        id="reset-password-confirmation"
                        v-model="resetForm.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                    />
                </div>
            </div>
            <DialogFooter class="gap-2">
                <Button variant="outline" @click="resetting = null"
                    >Batal</Button
                >
                <Button
                    :disabled="processing"
                    data-test="user-reset-submit"
                    @click="reset"
                    >Reset</Button
                >
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
