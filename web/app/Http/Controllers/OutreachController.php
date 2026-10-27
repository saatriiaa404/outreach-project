<?php

namespace App\Http\Controllers;

use App\Actions\Outreach\CreateOutreach;
use App\Http\Requests\Outreach\StoreOutreachRequest;
use App\Models\Outreach;
use App\Models\OutreachSource;
use App\Models\OutreachType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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

        return to_route('outreaches.index');
    }
}
