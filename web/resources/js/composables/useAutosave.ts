import { router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

export type AutosaveState =
    | 'IDLE'
    | 'SAVING'
    | 'SAVED'
    | 'FAILED'
    | 'CONFLICT'
    | 'CONNECTION_LOST';

type Options = {
    url: string;
    step: string;
    version: number;
    values: Record<string, string>;
    intervalMs?: number;
};

const xsrfToken = () => {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
};

/**
 * Autosave for the Outreach stepper (Workflow v2.1 sections 10.2, 10.3, 11, 12):
 * saves dirty data about every 60 seconds and before leaving the page, using
 * the same server service as Save Draft. The server stays the source of truth;
 * unsaved local changes are not kept when the connection is lost.
 */
export function useAutosave(options: Options) {
    const state = ref<AutosaveState>('IDLE');
    const version = ref(options.version);
    const savedAt = ref<Date | null>(null);
    const errors = ref<Record<string, string>>({});
    const message = ref('');

    const snapshot = () => JSON.stringify(options.values);
    const lastSaved = ref(snapshot());
    const isDirty = computed(() => snapshot() !== lastSaved.value);

    let pending: Promise<boolean> | null = null;
    let leaving = false;

    const send = async (): Promise<boolean> => {
        const body = snapshot();
        state.value = 'SAVING';

        let response: Response;

        try {
            response = await fetch(options.url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': xsrfToken(),
                },
                body: JSON.stringify({
                    ...options.values,
                    step: options.step,
                    edit_version: version.value,
                }),
            });
        } catch {
            state.value = 'CONNECTION_LOST';

            return false;
        }

        const data = await response.json().catch(() => ({}));

        if (response.ok) {
            version.value = data.edit_version;
            lastSaved.value = body;
            savedAt.value = new Date();
            errors.value = {};
            state.value = 'SAVED';

            return true;
        }

        if (response.status === 409) {
            message.value = data.message ?? '';
            state.value = 'CONFLICT';

            return false;
        }

        errors.value = Object.fromEntries(
            Object.entries((data.errors ?? {}) as Record<string, string[]>).map(
                ([field, list]) => [field, list[0]],
            ),
        );
        message.value =
            response.status === 422
                ? 'Ada isian yang belum valid. Periksa field bertanda merah.'
                : 'Server menolak penyimpanan. Muat ulang halaman, lalu coba lagi.';
        state.value = 'FAILED';

        return false;
    };

    // Resolves true when nothing is left unsaved.
    const save = (): Promise<boolean> => {
        if (!isDirty.value) {
            return Promise.resolve(true);
        }

        if (state.value === 'CONFLICT') {
            return Promise.resolve(false);
        }

        pending ??= send().finally(() => (pending = null));

        return pending;
    };

    // Called when the page received a newer saved state from a normal submit.
    const markSaved = (newVersion: number) => {
        version.value = newVersion;
        lastSaved.value = snapshot();
        errors.value = {};
    };

    const reload = () => {
        leaving = true;
        router.visit(window.location.href);
    };

    const onBeforeUnload = (event: BeforeUnloadEvent) => {
        if (isDirty.value && !leaving) {
            event.preventDefault();
        }
    };

    const onOnline = () => {
        if (state.value === 'CONNECTION_LOST') {
            void save();
        }
    };

    const onOffline = () => {
        if (state.value !== 'CONFLICT') {
            state.value = 'CONNECTION_LOST';
        }
    };

    let timer: number | undefined;
    let removeBefore: (() => void) | undefined;

    onMounted(() => {
        timer = window.setInterval(
            () => void save(),
            options.intervalMs ?? 60_000,
        );
        window.addEventListener('beforeunload', onBeforeUnload);
        window.addEventListener('online', onOnline);
        window.addEventListener('offline', onOffline);

        // Leaving the step through any link saves first; the visit continues
        // only after the save succeeded. Form submissions are not intercepted.
        removeBefore = router.on('before', (event) => {
            const visit = event.detail.visit;

            if (
                leaving ||
                visit.method !== 'get' ||
                visit.prefetch ||
                !isDirty.value
            ) {
                return;
            }

            void save().then((saved) => {
                if (saved) {
                    router.visit(visit.url.href);
                }
            });

            return false;
        });
    });

    onUnmounted(() => {
        window.clearInterval(timer);
        window.removeEventListener('beforeunload', onBeforeUnload);
        window.removeEventListener('online', onOnline);
        window.removeEventListener('offline', onOffline);
        removeBefore?.();
    });

    return {
        state,
        version,
        savedAt,
        errors,
        message,
        isDirty,
        save,
        markSaved,
        reload,
    };
}
