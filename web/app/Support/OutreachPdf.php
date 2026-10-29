<?php

namespace App\Support;

use App\Enums\AttachmentCategory;
use App\Enums\HistoryEventType;
use App\Models\DocumentTemplateVersion;
use App\Models\HistoryEvent;
use App\Models\Outreach;
use App\Models\OutreachApproval;
use App\Models\OutreachVersion;
use App\Models\OutreachVersionAttachment;
use App\Models\Signature;
use App\Models\SignatureTemplate;
use App\Models\StampTemplate;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Renders the official PDFs of a historical version on demand (Database v1.1
 * section 18): Outreach with attachment pages, and for Baznas/CSR also
 * Mustahik and Kifayah (outreach.md 8.5). Everything comes from the stored
 * version (snapshot_data, version attachments, version signatures) and the
 * template version it recorded, never from the current Draft data.
 */
class OutreachPdf
{
    /**
     * Blade view per document and template version; old versions stay
     * available so historical documents keep their layout.
     */
    private const VIEWS = [
        'outreach' => [1 => 'pdf.v1.outreach', 2 => 'pdf.v2.outreach', 3 => 'pdf.v3.outreach'],
        'mustahik' => [1 => 'pdf.v1.mustahik', 2 => 'pdf.v2.mustahik', 3 => 'pdf.v3.mustahik'],
        'kifayah' => [1 => 'pdf.v1.kifayah', 2 => 'pdf.v2.kifayah', 3 => 'pdf.v3.kifayah'],
    ];

    /**
     * Logos of template v2 (D-064): Pemkot on the Outreach letterhead, Baznas
     * on the Mustahik and Kifayah forms whatever the Source.
     */
    private const LOGOS = [
        'outreach' => 'pemkot-surabaya.png',
        'mustahik' => 'baznas-surabaya.jpg',
        'kifayah' => 'baznas-surabaya.jpg',
    ];

    public function __construct(private PdfRenderer $renderer) {}

    /**
     * Documents of the version's template package (Q-09).
     *
     * @return list<string>
     */
    public function documents(OutreachVersion $version): array
    {
        $package = $version->documentTemplate->metadata ?? [];

        // Fixed order; stored JSON keys are unordered.
        return array_values(array_filter(array_keys(self::VIEWS), fn (string $document) => isset($package[$document])));
    }

    /**
     * Approval block values of an APPROVED decision (outreach.md 8.4): the
     * stored approval date, the approving Kepala's name and NIP read at
     * render time (database.md 4.1), and the signature template it recorded.
     *
     * @return array{date: string, name: string, nip: string|null, signature: string|null}
     */
    public function approvalBlock(OutreachApproval $approval): array
    {
        $template = $approval->head_signature_template_id === null
            ? null
            : SignatureTemplate::query()->with('file')->find($approval->head_signature_template_id);
        $stamp = $approval->stamp_template_id === null
            ? null
            : StampTemplate::query()->with('file')->find($approval->stamp_template_id);
        $event = HistoryEvent::query()
            ->where('approval_id', $approval->id)
            ->where('event_type', HistoryEventType::Approved)
            ->first();

        return [
            'date' => $approval->decided_at->translatedFormat('j F Y'),
            'name' => $approval->kepala->name,
            'pangkat' => $event?->metadata['kepala_pangkat'] ?? null,
            'nip' => $approval->kepala->nip,
            'signature' => $template ? PdfRenderer::dataUri($template->file) : null,
            'stamp' => $stamp ? PdfRenderer::dataUri($stamp->file) : null,
        ];
    }

    /**
     * @param  array{date: string, name: string, pangkat?: string|null, nip: string|null, signature: string|null, stamp?: string|null}|null  $approval
     *                                                                                                                                                  Approval block values (outreach.md 8.4); empty until the Kepala approves.
     */
    public function render(OutreachVersion $version, string $document, ?array $approval = null): string
    {
        return $this->renderer->html($this->html($version, $document, $approval), $document === 'kifayah' ? '{PAGE_NUM} dari {PAGE_COUNT}' : null);
    }

    /**
     * The HTML handed to Dompdf, from the template version the document recorded.
     *
     * @param  array{date: string, name: string, pangkat?: string|null, nip: string|null, signature: string|null, stamp?: string|null}|null  $approval
     */
    public function html(OutreachVersion $version, string $document, ?array $approval = null): string
    {
        $templateVersion = $version->documentTemplate->metadata[$document] ?? null;

        if ($templateVersion === null) {
            throw new InvalidArgumentException("The template package of version {$version->id} has no {$document} document.");
        }

        return $this->compose($document, (int) $templateVersion, $version->snapshot_data, [
            'mustahik' => $version->mustahik_signature_id,
            'kifayah' => $version->kifayah_signature_id,
        ], fn () => $this->attachments($version), $approval);
    }

    /**
     * The Mustahik or Kifayah form of a version as a page for the browser,
     * from the same template as the PDF (D-069).
     */
    public function preview(OutreachVersion $version, string $document): string
    {
        return self::forScreen($this->html($version, $document));
    }

    /**
     * The same form for the current Draft data, with the template the
     * Finalisasi would record and the signatures given so far.
     */
    public function draftPreview(Outreach $outreach, string $document): string
    {
        $template = DocumentTemplateVersion::query()
            ->where('template_key', DocumentTemplateVersion::keyFor($outreach->source->code))
            ->where('is_active', true)
            ->orderByDesc('version_number')
            ->first();
        $templateVersion = $template?->metadata[$document] ?? null;

        if ($templateVersion === null) {
            throw new InvalidArgumentException("The active template package of {$outreach->source->code} has no {$document} document.");
        }

        $snapshot = OutreachSnapshot::build($outreach);

        if ($snapshot[$document] === null) {
            $title = $document === 'mustahik' ? 'Formulir Mustahik' : 'Formulir Had Kifayah';

            return '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"></head>'
                .'<body style="font-family: sans-serif; color: #b91c1c; margin: 12mm 10mm;">'.e($title).' belum diisi.</body></html>';
        }

        return self::forScreen($this->compose($document, (int) $templateVersion, $snapshot, [
            'mustahik' => FormSignatures::form($outreach, 'mustahik')?->signature_id,
            'kifayah' => FormSignatures::form($outreach, 'kifayah')?->signature_id,
        ], fn () => [], null));
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  array{mustahik: int|null, kifayah: int|null}  $signatures
     * @param  callable(): array<int, array{label: string, name: string, is_image: bool, url: string}>  $attachments
     * @param  array<string, mixed>|null  $approval
     */
    private function compose(string $document, int $templateVersion, array $snapshot, array $signatures, callable $attachments, ?array $approval): string
    {
        $view = self::VIEWS[$document][$templateVersion] ?? throw new InvalidArgumentException("No view for {$document} template v{$templateVersion}.");

        return view($view, [...match ($document) {
            'outreach' => [
                'document' => OutreachDocument::fromSnapshot($snapshot, $attachments()),
                'approval' => $approval,
            ],
            'mustahik' => $this->mustahik($snapshot, $signatures['mustahik']),
            default => $this->kifayah($snapshot, $signatures['kifayah']),
        }, 'logo' => self::logo(self::LOGOS[$document]),
            // The Kelurahan letterhead as one image from template v3 (D-070).
            'kop' => $document === 'outreach' && $templateVersion >= 3 ? self::logo('kop-manukan.png') : null,
        ])->render();
    }

    /**
     * On screen the page margins of @page do not apply, so the body gets
     * them instead; the template itself stays as printed.
     */
    private static function forScreen(string $html): string
    {
        $style = '<style>@media screen { html { background: #fff; } body { margin: 12mm 10mm; } }</style>';

        return str_replace('</head>', $style.'</head>', $html);
    }

    /**
     * @return array<int, array{label: string, name: string, is_image: bool, url: string}>
     */
    private function attachments(OutreachVersion $version): array
    {
        return $version->attachments()->with('file')->orderBy('sort_order')->get()
            ->sortBy(fn ($attachment) => array_search($attachment->category, AttachmentCategory::cases(), true), SORT_NUMERIC)
            ->map(fn (OutreachVersionAttachment $attachment) => [
                'label' => $attachment->title ?: $attachment->category->label(),
                'name' => $attachment->file->original_name,
                'is_image' => $attachment->file->isImage(),
                'url' => $attachment->file->isImage() ? PdfRenderer::dataUri($attachment->file) : '',
            ])->values()->all();
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private function mustahik(array $snapshot, ?int $signatureId): array
    {
        $form = $snapshot['mustahik'];
        $survey = $form['survey_date'] === null ? null : Carbon::parse($form['survey_date']);
        $sections = [
            'A. KONDISI RUMAH' => 'HOUSE_',
            'B. KONDISI EKONOMI' => 'ECONOMIC_',
            'C. KONDISI FISIK DAN TANGUNGAN' => 'PHYSICAL_',
        ];

        return [
            'header' => [
                'petugas' => $form['petugas']['name'],
                'survey' => $survey ? $survey->translatedFormat('j F Y').' / '.$survey->translatedFormat('l') : '',
                'kelurahan' => $snapshot['meta']['wilayah']['kelurahan'],
                'kecamatan' => $snapshot['meta']['wilayah']['kecamatan'],
                'kk_nama' => $form['kepala_keluarga']['nama'],
                'kk_nik' => $form['kepala_keluarga']['nik_no_kk'],
                'kk_alamat' => $form['kepala_keluarga']['alamat'],
                'kk_rt' => $form['kepala_keluarga']['rt'],
                'kk_rw' => $form['kepala_keluarga']['rw'],
                // Outreach contact, not a verified Kepala Keluarga number (D-036).
                'no_hp' => $snapshot['client']['no_telp'],
            ],
            'sections' => array_map(fn (string $title, string $prefix) => [
                'title' => $title,
                'questions' => $this->questions(MustahikFormSchema::QUESTIONS, $form['answers'], $prefix),
            ], array_keys($sections), $sections),
            'signature' => [
                'date' => $survey?->translatedFormat('j F Y') ?? '',
                'name' => $form['petugas']['name'],
                'image' => $this->signatureImage($signatureId),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private function kifayah(array $snapshot, ?int $signatureId): array
    {
        $form = $snapshot['kifayah'];
        $client = $snapshot['client'];
        $amount = fn (string $name) => self::amount($form[$name]);
        // The box beside an amount is ticked when there is an expense, i.e. not 0 (VAL-006, D-064).
        $rows = fn (array $names) => array_map(fn (string $name) => [
            'label' => KifayahFormSchema::FIELDS[$name]['label'],
            'value' => $amount($name),
            'checked' => ($form[$name] ?? 0) > 0,
        ], $names);
        $signature = $signatureId ? Signature::query()->find($signatureId) : null;
        $sections = [];

        foreach (KifayahFormSchema::QUESTIONS as $code => $question) {
            $sections[$question['section']][] = $this->question($question, $form['answers'][$code]['option'] ?? null);
        }

        return [
            // Identity read from the Outreach snapshot (D-036).
            'identitas' => [
                'Nama' => $client['nama'],
                'NIK' => $client['nik'],
                'Alamat Tempat Tinggal' => $client['alamat_domisili'],
                'Kelurahan' => $snapshot['meta']['wilayah']['kelurahan'],
                'Kecamatan' => $snapshot['meta']['wilayah']['kecamatan'],
                'No. HP' => $client['no_telp'],
            ],
            'income' => $rows(['income_head_family', 'income_other_members']),
            'expenseGroups' => [
                ['title' => 'PENGELUARAN KELUARGA PER BULAN', 'rows' => $rows([
                    'expense_head_household', 'expense_housewife', 'expense_adult_working', 'expense_adult_not_working',
                    'expense_school_sma', 'expense_school_smp', 'expense_school_sd', 'expense_baby_not_school',
                ])],
                ['title' => 'PENGELUARAN TAMBAHAN PER BULAN', 'rows' => $rows([
                    'expense_disability', 'expense_breastfeeding', 'expense_pregnant', 'expense_widow_not_working',
                ])],
            ],
            'education' => [
                'label' => KifayahFormSchema::FIELDS['higher_education_count']['label'],
                'value' => $form['higher_education_count'],
            ],
            'questionSections' => array_map(fn (string $title, array $questions) => ['title' => $title, 'questions' => $questions], array_keys($sections), $sections),
            // The date follows the Outreach date (kifayah.md 5).
            'signature' => [
                'date' => Carbon::parse($snapshot['meta']['outreach_date'])->translatedFormat('j F Y'),
                'name' => $signature->signer_name_snapshot ?? $client['nama'],
                'image' => $this->signatureImage($signatureId),
            ],
        ];
    }

    /**
     * Mustahik questions of one section, numbered within the section, with
     * the chosen option marked.
     *
     * @param  array<string, array{label: string, options: array<int, string>}>  $questions
     * @param  array<string, array{option: string, label: string|null}>  $answers
     * @return list<array<string, mixed>>
     */
    private function questions(array $questions, array $answers, string $prefix): array
    {
        $result = [];

        foreach ($questions as $code => $question) {
            if (str_starts_with($code, $prefix)) {
                $result[] = ['no' => count($result) + 1, ...$this->question($question, $answers[$code]['option'] ?? null)];
            }
        }

        return $result;
    }

    /**
     * @param  array{label: string, options: array<int, string>}  $question
     * @return array{label: string, options: list<array{letter: string, label: string, checked: bool}>}
     */
    private function question(array $question, ?string $chosen): array
    {
        $options = [];

        foreach ($question['options'] as $code => $label) {
            $options[] = ['letter' => range('a', 'z')[$code - 1], 'label' => $label, 'checked' => (string) $code === $chosen];
        }

        return ['label' => $question['label'], 'options' => $options];
    }

    private function signatureImage(?int $signatureId): ?string
    {
        $signature = $signatureId ? Signature::query()->with('file')->find($signatureId) : null;

        return $signature ? PdfRenderer::dataUri($signature->file) : null;
    }

    /**
     * Rupiah amount printed after "Rp." on the Kifayah form; 0 reads "-" (D-050).
     */
    /**
     * A logo bundled with the templates, embedded like every other image.
     */
    private static function logo(string $name): string
    {
        $path = resource_path('views/pdf/logos/'.$name);
        $mime = str_ends_with($name, '.png') ? 'image/png' : 'image/jpeg';

        return "data:{$mime};base64,".base64_encode((string) file_get_contents($path));
    }

    private static function amount(?int $value): string
    {
        return match (true) {
            $value === null => '',
            $value === 0 => '-',
            default => number_format($value, 0, ',', '.'),
        };
    }
}
