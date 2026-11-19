import { usePage } from '@inertiajs/vue3';
import { nextTick, watch } from 'vue';

/**
 * After a save that left errors (a wrong format, or "Simpan & Lanjutkan"
 * with required fields still empty, D-073), scroll to the first marked field
 * in page order and put the cursor there. Fields are found by id or name.
 */
export function useFocusFirstError(): void {
    const page = usePage();

    const focusFirst = async (errors: Record<string, string> | undefined) => {
        const names = Object.keys(errors ?? {});

        if (names.length === 0) {
            return;
        }

        await nextTick();

        const fields = names
            .flatMap((name) => [
                ...document.querySelectorAll<HTMLElement>(
                    `#${CSS.escape(name)}, [name="${CSS.escape(name)}"]`,
                ),
            ])
            .filter((field) => field.getClientRects().length > 0)
            .sort((a, b) =>
                a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING
                    ? -1
                    : 1,
            );
        const first = fields[0];

        if (first) {
            first.scrollIntoView({ block: 'center', behavior: 'smooth' });
            first.focus({ preventScroll: true });
        }
    };

    watch(
        () => page.props.errors as Record<string, string> | undefined,
        focusFirst,
        { immediate: true },
    );
}
