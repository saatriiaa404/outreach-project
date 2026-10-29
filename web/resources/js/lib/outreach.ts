/**
 * Status shown in the step headings. The Admin only reaches the stepper of a
 * submitted or completed Outreach during Special Unlock (D-056).
 */
export function stepStatusLabel(status: string): string {
    return (
        {
            DRAFT: 'Draft',
            PERLU_PERBAIKAN: 'Perlu Perbaikan',
            MENUNGGU_PENGESAHAN: 'Menunggu Pengesahan · Special Unlock',
            SELESAI: 'Selesai · Special Unlock',
        }[status] ?? status
    );
}
