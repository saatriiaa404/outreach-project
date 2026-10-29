<?php

namespace App\Http\Controllers;

use App\Actions\Outreach\ChangeOutreachSourceType;
use App\Actions\Outreach\CreateOutreach;
use App\Actions\Outreach\SaveOutreachStep;
use App\Enums\AttachmentCategory;
use App\Enums\HistoryEventType;
use App\Enums\OutreachStatus;
use App\Enums\SpecialUnlockRequestStatus;
use App\Enums\UserRole;
use App\Http\Requests\Outreach\ChangeSourceTypeRequest;
use App\Http\Requests\Outreach\SaveOutreachStepRequest;
use App\Http\Requests\Outreach\StoreAttachmentRequest;
use App\Http\Requests\Outreach\StoreOutreachRequest;
use App\Models\HistoryEvent;
use App\Models\Outreach;
use App\Models\OutreachAttachment;
use App\Models\OutreachSource;
use App\Models\OutreachType;
use App\Models\OutreachVersion;
use App\Models\OutreachVersionAttachment;
use App\Models\Signature;
use App\Models\SpecialUnlockRequest;
use App\Models\User;
use App\Support\FormResults;
use App\Support\FormSignatures;
use App\Support\KifayahFormSchema;
use App\Support\MustahikFormSchema;
use App\Support\OutreachDocument;
use App\Support\OutreachFormSchema;
use App\Support\OutreachPdf;
use App\Support\OutreachSearch;
use App\Support\OutreachSnapshot;
use App\Support\OutreachSteps;
use App\Support\SpecialUnlock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OutreachController extends Controller
{
    /**
     * List the Outreach records visible to the user (D-039), optionally by
     * status. The Kepala starts on the approval queue, oldest submission
     * first.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Outreach::class);

        $user = $request->user();
        $isKepala = $user->role === UserRole::Kepala;
        $status = OutreachStatus::tryFrom($request->string('status')->toString());
        $status ??= $isKepala && ! $request->has('status') ? OutreachStatus::MenungguPengesahan : null;

        $search = (array) $request->session()->get(OutreachSearch::SESSION_KEY, []);

        $query = OutreachSearch::apply(Outreach::query()->visibleTo($user), $user, $search)
            ->when($status, fn ($query, OutreachStatus $status) => $query->where('status', $status))
            ->with(['source:id,name', 'type:id,name']);

        if ($status === OutreachStatus::MenungguPengesahan) {
            $query->orderBy(OutreachVersion::query()
                ->select('finalized_at')
                ->whereColumn('outreach_id', 'outreaches.id')
                ->orderByDesc('version_number')
                ->limit(1))->orderBy('id');
        } else {
            $query->latest('id');
        }

        $outreaches = $query
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Outreach $outreach) => [
                'id' => $outreach->id,
                'code' => $outreach->outreach_number ?? $outreach->draft_code,
                // Content is hidden where only the status may be seen (D-039).
                'nama' => $user->can('view', $outreach) ? $outreach->nama : null,
                'source' => $outreach->source->name,
                'type' => $outreach->type->name,
                'outreach_date' => $outreach->outreach_date->toDateString(),
                'status' => $outreach->status->value,
                'can_edit' => $user->can('update', $outreach),
                'can_open' => $user->can('viewSummary', $outreach),
            ]);

        return Inertia::render('outreach/Index', [
            'outreaches' => $outreaches,
            'statuses' => collect(OutreachStatus::cases())
                ->reject(fn (OutreachStatus $item) => $isKepala && $item === OutreachStatus::Draft)
                ->map(fn (OutreachStatus $item) => $item->value)
                ->values(),
            'status' => $status?->value,
            'canCreate' => $user->can('create', Outreach::class),
            // Criteria come back from the session to fill the form (D-061).
            'search' => (object) $search,
            'searchOptions' => [
                'sources' => OutreachSource::query()->orderBy('name')->get(['id', 'name']),
                'types' => OutreachType::query()->orderBy('name')->get(['id', 'name']),
            ],
        ]);
    }

    /**
     * Read-only view of the latest submission (Workflow v2.1 20.3) with the
     * Return action for the Kepala. Where only the status may be seen, as for
     * the Kepala on PERLU_PERBAIKAN, it carries the status and the decision
     * history with Return notes only (D-039).
     */
    public function show(Request $request, Outreach $outreach): Response
    {
        Gate::authorize('viewSummary', $outreach);

        $user = $request->user();
        $outreach->load(['source:id,code,name', 'type:id,code,name', 'petugas:id,name']);
        $full = $user->can('view', $outreach);
        $version = $full ? $outreach->latestVersion()->first() : null;

        return Inertia::render('outreach/Show', [
            'outreach' => [
                'id' => $outreach->id,
                'code' => $outreach->outreach_number ?? $outreach->draft_code,
                'source' => $outreach->source->name,
                'type' => $outreach->type->name,
                'status' => $outreach->status->value,
                'petugas' => $outreach->petugas->name,
                'nama' => $full ? $outreach->nama : null,
            ],
            'history' => HistoryEvent::query()
                ->where('outreach_id', $outreach->id)
                ->whereIn('event_type', [
                    HistoryEventType::Finalized, HistoryEventType::Refinalized, HistoryEventType::Returned, HistoryEventType::Approved,
                    HistoryEventType::SpecialUnlocked, HistoryEventType::SpecialUnlockLocked, HistoryEventType::SpecialUnlockSubmitted,
                ])
                ->orderBy('id')
                ->get()
                ->map(fn (HistoryEvent $event) => [
                    'id' => $event->id,
                    'type' => $event->event_type->value,
                    'actor' => $event->actor_name_snapshot,
                    'at' => $event->created_at?->toIso8601String(),
                    'description' => $event->description,
                    'reason' => $event->reason,
                ]),
            'version' => $version === null ? null : $this->versionReview($outreach, $version),
            'canReturn' => $user->can('return', $outreach),
            'canApprove' => $user->can('approve', $outreach),
            // Final PDFs of the approved version, rendered on demand (R7.5, D-040).
            'downloads' => $version?->status === OutreachVersion::APPROVED && $user->can('downloadFinalPdf', $outreach)
                ? collect(app(OutreachPdf::class)->documents($version))->map(fn (string $document) => [
                    'title' => OutreachPdfController::TITLES[$document],
                    'url' => route('outreaches.versions.pdf', [$outreach, $version, $document]),
                ])->all()
                : [],
            'canEdit' => $user->can('update', $outreach),
            'specialUnlock' => $this->specialUnlock($outreach, $user),
        ]);
    }

    /**
     * Show the Source, Type, and Date step that starts a new Outreach.
     */
    public function create(): Response
    {
        Gate::authorize('create', Outreach::class);

        return Inertia::render('outreach/Create', [
            'sources' => OutreachSource::query()->where('active', true)->orderBy('name')->get(['id', 'name']),
            'types' => OutreachType::query()->where('active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreOutreachRequest $request, CreateOutreach $createOutreach): RedirectResponse
    {
        $outreach = $createOutreach->handle(
            $request->user(),
            $request->integer('source_id'),
            $request->integer('type_id'),
            $request->string('outreach_date')->toString(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => "Outreach {$outreach->draft_code} dibuat sebagai Draft."]);

        return to_route('outreaches.edit', ['outreach' => $outreach, 'step' => 'klien']);
    }

    /**
     * Show one step of the data collection stepper (Workflow v2.1 section 8).
     */
    public function edit(Request $request, Outreach $outreach): Response
    {
        Gate::authorize('update', $outreach);

        $steps = OutreachFormSchema::steps();
        $outreach->load(['source:id,code,name', 'type:id,code,name']);
        $stepList = OutreachSteps::for($outreach);
        $step = $request->query('step');
        $step = is_string($step) && collect($stepList)->contains(fn (array $item) => $item['key'] === $step && $item['state'] !== 'upcoming') ? $step : 'klien';

        $shared = [
            'outreach' => [
                'id' => $outreach->id,
                'code' => $outreach->outreach_number ?? $outreach->draft_code,
                'source' => $outreach->source->name,
                'type' => $outreach->type->name,
                'status' => $outreach->status->value,
                'edit_version' => $outreach->edit_version,
                'outreach_date' => $outreach->outreach_date->toDateString(),
                'source_id' => $outreach->source_id,
                'type_id' => $outreach->type_id,
            ],
            'steps' => $stepList,
            'step' => $step,
            // Why the Kepala returned it, shown while the Petugas revises (21.6).
            'returned' => $outreach->status === OutreachStatus::PerluPerbaikan ? $this->latestReturn($outreach) : null,
            // The Admin edits inside an open Special Unlock session (D-056).
            'unlockSession' => $request->user()->role === UserRole::Admin ? $this->unlockSession($outreach) : null,
        ];

        if ($step === 'mustahik') {
            $form = $outreach->mustahikForm;

            return Inertia::render('outreach/Mustahik', [
                ...$shared,
                'petugas' => ($form->petugas ?? $outreach->petugas)->name,
                'wilayah' => config('app.wilayah'),
                // Contact from Outreach field 15, not a verified Kepala Keluarga number (D-036).
                'noTelp' => $outreach->getAttribute('no_telp'),
                // Offered only through the explicit copy button (D-048).
                'klien' => [
                    'nama' => $outreach->nama,
                    'nik' => $outreach->getAttribute('nik'),
                    'alamat' => $outreach->getAttribute('alamat_domisili'),
                ],
                'header' => collect(MustahikFormSchema::HEADER)->map(fn (array $field, string $name) => [
                    'name' => $name,
                    ...$field,
                    // Survey date starts at the Outreach date when the form is first opened (D-048).
                    'value' => $form === null
                        ? ($name === 'survey_date' ? $outreach->outreach_date->toDateString() : null)
                        : ($name === 'survey_date' ? $form->survey_date?->toDateString() : $form->getAttribute($name)),
                ])->values(),
                'questions' => collect(MustahikFormSchema::QUESTIONS)->map(fn (array $question, string $code) => [
                    'code' => $code,
                    ...$question,
                    'value' => $form?->answers->firstWhere('question_code', $code)?->answer['option'],
                ])->values(),
                'missingForFinalization' => MustahikFormSchema::missing($form),
                // Changing the data clears this signature (Workflow v2.1 16.4).
                'signed' => $form?->signature_id !== null,
            ]);
        }

        if ($step === 'kifayah') {
            $form = $outreach->kifayahForm;

            return Inertia::render('outreach/Kifayah', [
                ...$shared,
                // Shown read-only from Outreach, never typed again (D-036).
                'identitas' => [
                    'nama' => $outreach->nama,
                    'nik' => $outreach->getAttribute('nik'),
                    'alamat' => $outreach->getAttribute('alamat_domisili'),
                    'kelurahan' => config('app.wilayah.kelurahan'),
                    'kecamatan' => config('app.wilayah.kecamatan'),
                    'no_hp' => $outreach->getAttribute('no_telp'),
                ],
                'fields' => collect(KifayahFormSchema::FIELDS)->map(fn (array $field, string $name) => [
                    'name' => $name,
                    ...$field,
                    'value' => $form?->getAttribute($name),
                ])->values(),
                'questions' => collect(KifayahFormSchema::QUESTIONS)->map(fn (array $question, string $code) => [
                    'code' => $code,
                    ...$question,
                    'value' => $form?->answers->firstWhere('question_code', $code)?->answer['option'],
                ])->values(),
                'missingForFinalization' => KifayahFormSchema::missing($form),
                'signed' => $form?->signature_id !== null,
            ]);
        }

        if ($step === 'review') {
            $missing = OutreachFormSchema::missingForFinalization($outreach);
            $usesForms = $outreach->source->usesMustahikKifayah();
            $snapshot = OutreachSnapshot::build($outreach);

            return Inertia::render('outreach/Review', [
                ...$shared,
                // Every step except Review itself, with what Finalisasi still needs (17.1).
                'checklist' => collect($stepList)->reject(fn (array $item) => $item['key'] === 'review')->map(fn (array $item) => [
                    'key' => $item['key'],
                    'title' => $item['title'],
                    'missing' => $missing[$item['key']] ?? [],
                ])->values(),
                'ready' => $missing === [],
                'canFinalize' => $request->user()->can('finalize', $outreach),
                'canLock' => $request->user()->can('lockSpecialUnlock', $outreach),
                'document' => OutreachDocument::build($outreach),
                'mustahik' => $usesForms ? FormResults::mustahik($snapshot['mustahik']) : null,
                'kifayah' => $usesForms ? FormResults::kifayah($snapshot['kifayah']) : null,
                'signatures' => $usesForms ? collect(FormSignatures::FORMS)->map(fn (array $definition, string $form) => [
                    'title' => $definition['title'],
                    'signed' => FormSignatures::form($outreach, $form)?->signature_id !== null,
                    'url' => FormSignatures::form($outreach, $form)?->signature_id ? route('outreaches.signatures.show', [$outreach, $form]) : null,
                ])->values() : [],
            ]);
        }

        if ($step === 'tanda_tangan') {
            return Inertia::render('outreach/Signatures', [
                ...$shared,
                'canSign' => $request->user()->can('sign', $outreach),
                'parties' => collect(FormSignatures::FORMS)->map(function (array $definition, string $form) use ($outreach) {
                    $record = FormSignatures::form($outreach, $form);
                    $signature = $record?->signature_id ? Signature::query()->find($record->signature_id) : null;
                    $missing = $form === 'mustahik' ? MustahikFormSchema::missing($outreach->mustahikForm) : KifayahFormSchema::missing($outreach->kifayahForm);

                    return [
                        'form' => $form,
                        'title' => $definition['title'],
                        'document' => $definition['document'],
                        // Petugas signs as the logged-in user; the Warga is the Klien (16.1).
                        'signer' => $signature->signer_name_snapshot ?? ($form === 'mustahik' ? $outreach->petugas->name : $outreach->nama),
                        'signed_at' => $signature?->signed_at->toIso8601String(),
                        'url' => $signature ? route('outreaches.signatures.show', [$outreach, $form]).'?v='.$signature->id : null,
                        'formMissing' => count($missing),
                    ];
                })->values(),
            ]);
        }

        if ($step === 'lampiran') {
            return Inertia::render('outreach/Attachments', [
                ...$shared,
                'categories' => collect(AttachmentCategory::cases())->map(fn (AttachmentCategory $category) => [
                    'value' => $category->value,
                    'label' => $category->label(),
                    'baseline' => $category->isBaseline(),
                    'accept' => implode(',', $category->allowedMimeTypes()),
                ]),
                'attachments' => $outreach->attachments()->with('file')->orderBy('sort_order')->get()
                    ->map(fn (OutreachAttachment $attachment) => [
                        'id' => $attachment->id,
                        'category' => $attachment->category->value,
                        'title' => $attachment->title,
                        'name' => $attachment->file->original_name,
                        'is_image' => $attachment->file->isImage(),
                        'size_bytes' => $attachment->file->size_bytes,
                        'url' => route('outreaches.attachments.show', [$outreach, $attachment]),
                    ]),
                'maxKilobytes' => StoreAttachmentRequest::MAX_KILOBYTES,
                'missingForFinalization' => OutreachFormSchema::missingAttachments($outreach),
                // Suggestion only; PENDING VALIDATION until Kelurahan confirms (D-033).
                'additionalHint' => AttachmentCategory::additionalFor($outreach->type->code),
            ]);
        }

        $sources = OutreachSource::query()->where('active', true)->orWhere('id', $outreach->source_id)->orderBy('name')->get(['id', 'code', 'name']);

        return Inertia::render('outreach/Edit', [
            ...$shared,
            // Choices for changing Source and Type on the Identitas step (Workflow v2.1 7.3).
            'sourceTypeChoices' => $step === 'identitas' && $request->user()->can('changeSourceType', $outreach) ? [
                'sources' => $sources->map(fn (OutreachSource $source) => [
                    'id' => $source->id,
                    'name' => $source->name,
                    'destructive' => ChangeOutreachSourceType::isDestructive($outreach->source, $source),
                ]),
                'types' => OutreachType::query()->where('active', true)->orWhere('id', $outreach->type_id)->orderBy('name')->get(['id', 'name']),
            ] : null,
            'fields' => collect($steps[$step]['fields'])->map(fn (array $field, string $name) => [
                'name' => $name,
                'value' => $this->fieldValue($outreach, $name),
                ...$field,
            ])->values(),
        ]);
    }

    /**
     * Save the current step as Draft data, then stay or continue.
     */
    public function update(SaveOutreachStepRequest $request, Outreach $outreach, SaveOutreachStep $saveStep): RedirectResponse
    {
        $step = $request->string('step')->toString();

        $saveStep->handle($outreach, $request->user(), $step, $request->stepData(), $request->integer('edit_version'));

        $next = OutreachSteps::nextAfter($outreach->load('source'), $step);
        $target = $request->boolean('next') && $next ? $next : $step;

        Inertia::flash('toast', ['type' => 'success', 'message' => $outreach->status === OutreachStatus::Draft ? 'Draft tersimpan.' : 'Perubahan tersimpan.']);

        return to_route('outreaches.edit', ['outreach' => $outreach, 'step' => $target]);
    }

    /**
     * Change Source and Type of a Draft, then reload the stepper so the new
     * configuration applies (Workflow v2.1 sections 7.3, 9.5, 9.7).
     */
    public function changeSourceType(ChangeSourceTypeRequest $request, Outreach $outreach, ChangeOutreachSourceType $change): RedirectResponse
    {
        $change->handle(
            $outreach,
            $request->user(),
            OutreachSource::query()->findOrFail($request->integer('source_id')),
            OutreachType::query()->findOrFail($request->integer('type_id')),
            $request->integer('edit_version'),
            $request->boolean('confirm_destructive'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Source dan Jenis Outreach tersimpan.']);

        return to_route('outreaches.edit', ['outreach' => $outreach, 'step' => 'identitas']);
    }

    /**
     * Autosave the current step in the background (Workflow v2.1 section 10.2).
     * A stale edit_version answers 409 so the page can show CONFLICT.
     */
    public function autosave(SaveOutreachStepRequest $request, Outreach $outreach, SaveOutreachStep $saveStep): JsonResponse
    {
        try {
            $version = $saveStep->handle(
                $outreach,
                $request->user(),
                $request->string('step')->toString(),
                $request->stepData(),
                $request->integer('edit_version'),
                autosave: true,
            );
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json(['edit_version' => $version, 'saved_at' => now()->toIso8601String()]);
    }

    /**
     * What is reviewed: the version as submitted, never the current data.
     *
     * @return array<string, mixed>
     */
    private function versionReview(Outreach $outreach, OutreachVersion $version): array
    {
        $snapshot = $version->snapshot_data;
        $usesForms = $outreach->source->usesMustahikKifayah();
        $attachments = $version->attachments()->with('file')->orderBy('sort_order')->get()
            ->sortBy(fn (OutreachVersionAttachment $attachment) => array_search($attachment->category, AttachmentCategory::cases(), true), SORT_NUMERIC)
            ->map(fn (OutreachVersionAttachment $attachment) => [
                'label' => $attachment->title ?: $attachment->category->label(),
                'name' => $attachment->file->original_name,
                'is_image' => $attachment->file->isImage(),
                'url' => route('outreaches.versions.attachments.show', [$outreach, $version, $attachment]),
            ])->values()->all();

        return [
            'id' => $version->id,
            'number' => $version->version_number,
            'status' => $version->status,
            'finalized_at' => $version->finalized_at->toIso8601String(),
            'document' => OutreachDocument::fromSnapshot($snapshot, $attachments),
            'mustahik' => $usesForms ? FormResults::mustahik($snapshot['mustahik']) : null,
            'kifayah' => $usesForms ? FormResults::kifayah($snapshot['kifayah']) : null,
            'signatures' => $usesForms ? collect(FormSignatures::FORMS)->map(fn (array $definition, string $form) => [
                'title' => $definition['title'],
                'url' => $version->getAttribute("{$form}_signature_id") === null
                    ? null
                    : route('outreaches.versions.signatures.show', [$outreach, $version, $form]),
            ])->values()->all() : [],
        ];
    }

    /**
     * Special Unlock requests on this Outreach as the user may see them: the
     * Admin sees all, a requester their own; others only learn that an open
     * request already exists, since only one may be open (D-055).
     *
     * @return array<string, mixed>
     */
    private function specialUnlock(Outreach $outreach, User $user): array
    {
        $requests = SpecialUnlockRequest::query()
            ->where('outreach_id', $outreach->id)
            ->with(['requester:id,name', 'admin:id,name'])
            ->latest('id')
            ->get();
        $open = $requests->first(fn (SpecialUnlockRequest $request) => in_array($request->status, SpecialUnlockRequestStatus::open(), true));

        return [
            'canRequest' => $open === null && $user->can('requestSpecialUnlock', $outreach),
            'canUnlock' => $user->can('specialUnlock', $outreach),
            'session' => $this->unlockSession($outreach),
            'editVersion' => $user->role === UserRole::Admin ? $outreach->edit_version : null,
            'openBy' => $open === null || $open->requester_id === $user->id ? null : $open->requester->name,
            'requests' => $requests
                ->filter(fn (SpecialUnlockRequest $request) => $user->role === UserRole::Admin || $request->requester_id === $user->id)
                ->map(fn (SpecialUnlockRequest $request) => [
                    ...SpecialUnlockRequestController::row($request),
                    'can_cancel' => $request->status === SpecialUnlockRequestStatus::Pending && $user->can('cancel', $request),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Why the Outreach is back with the Petugas: the latest Return note of the
     * Kepala or Lock note of the Admin after Special Unlock (21.6, D-056).
     *
     * @return array{kind: string, note: string|null, by: string, at: string|null}|null
     */
    private function latestReturn(Outreach $outreach): ?array
    {
        $event = HistoryEvent::query()
            ->where('outreach_id', $outreach->id)
            ->whereIn('event_type', [HistoryEventType::Returned, HistoryEventType::SpecialUnlockLocked])
            ->latest('id')
            ->first();

        return $event === null ? null : [
            'kind' => $event->event_type->value,
            'note' => $event->reason,
            'by' => $event->actor_name_snapshot,
            'at' => $event->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{admin: string, reason: string|null, at: string|null}|null
     */
    private function unlockSession(Outreach $outreach): ?array
    {
        $session = SpecialUnlock::session($outreach);

        return $session === null ? null : [
            'admin' => $session->actor_name_snapshot,
            'reason' => $session->reason,
            'at' => $session->created_at?->toIso8601String(),
        ];
    }

    private function fieldValue(Outreach $outreach, string $name): mixed
    {
        $value = $outreach->getAttribute($name);

        return in_array($name, ['outreach_date', 'tanggal_lahir'], true) ? $value?->toDateString() : $value;
    }
}
