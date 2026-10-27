<?php

namespace App\Http\Controllers;

use App\Actions\Outreach\ChangeOutreachSourceType;
use App\Actions\Outreach\CreateOutreach;
use App\Actions\Outreach\SaveOutreachStep;
use App\Enums\AttachmentCategory;
use App\Http\Requests\Outreach\ChangeSourceTypeRequest;
use App\Http\Requests\Outreach\SaveOutreachStepRequest;
use App\Http\Requests\Outreach\StoreAttachmentRequest;
use App\Http\Requests\Outreach\StoreOutreachRequest;
use App\Models\Outreach;
use App\Models\OutreachAttachment;
use App\Models\OutreachSource;
use App\Models\OutreachType;
use App\Support\MustahikFormSchema;
use App\Support\OutreachFormSchema;
use App\Support\OutreachSteps;
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
     * List the Outreach records visible to the user (D-039).
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Outreach::class);

        $outreaches = Outreach::query()
            ->visibleTo($request->user())
            ->with(['source:id,name', 'type:id,name'])
            ->latest('id')
            ->paginate(20)
            ->through(fn (Outreach $outreach) => [
                'id' => $outreach->id,
                'code' => $outreach->outreach_number ?? $outreach->draft_code,
                // Content is hidden where only the status may be seen (D-039).
                'nama' => $request->user()->can('view', $outreach) ? $outreach->nama : null,
                'source' => $outreach->source->name,
                'type' => $outreach->type->name,
                'outreach_date' => $outreach->outreach_date->toDateString(),
                'status' => $outreach->status->value,
                'can_edit' => $request->user()->can('update', $outreach),
            ]);

        return Inertia::render('outreach/Index', [
            'outreaches' => $outreaches,
            'canCreate' => $request->user()->can('create', Outreach::class),
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

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Draft tersimpan.']);

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

    private function fieldValue(Outreach $outreach, string $name): mixed
    {
        $value = $outreach->getAttribute($name);

        return in_array($name, ['outreach_date', 'tanggal_lahir'], true) ? $value?->toDateString() : $value;
    }
}
