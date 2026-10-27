<?php

namespace App\Http\Controllers;

use App\Actions\Outreach\CreateOutreach;
use App\Actions\Outreach\SaveOutreachStep;
use App\Http\Requests\Outreach\SaveOutreachStepRequest;
use App\Http\Requests\Outreach\StoreOutreachRequest;
use App\Models\Outreach;
use App\Models\OutreachSource;
use App\Models\OutreachType;
use App\Support\OutreachFormSchema;
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
        $step = $request->query('step');
        $step = is_string($step) && array_key_exists($step, $steps) ? $step : 'klien';
        $outreach->load(['source:id,code,name', 'type:id,name']);

        return Inertia::render('outreach/Edit', [
            'outreach' => [
                'id' => $outreach->id,
                'code' => $outreach->outreach_number ?? $outreach->draft_code,
                'source' => $outreach->source->name,
                'type' => $outreach->type->name,
                'status' => $outreach->status->value,
                'edit_version' => $outreach->edit_version,
                'outreach_date' => $outreach->outreach_date->toDateString(),
            ],
            'steps' => [
                ...collect($steps)->map(fn (array $definition, string $key) => [
                    'key' => $key,
                    'title' => $definition['title'],
                    'state' => OutreachFormSchema::completion($outreach, $key),
                    'missing' => count(OutreachFormSchema::missingFields($outreach, $key)),
                ])->values(),
                ...collect(OutreachFormSchema::upcomingSteps($outreach->source->code))->map(fn (string $title, string $key) => [
                    'key' => $key,
                    'title' => $title,
                    'state' => 'upcoming',
                ])->values(),
            ],
            'step' => $step,
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

        $keys = array_keys(OutreachFormSchema::steps());
        $next = $keys[array_search($step, $keys, true) + 1] ?? null;
        $target = $request->boolean('next') && $next ? $next : $step;

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Draft tersimpan.']);

        return to_route('outreaches.edit', ['outreach' => $outreach, 'step' => $target]);
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
