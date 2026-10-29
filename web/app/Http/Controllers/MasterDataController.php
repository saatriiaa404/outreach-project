<?php

namespace App\Http\Controllers;

use App\Actions\MasterData\ManageMasterData;
use App\Models\Outreach;
use App\Models\OutreachSource;
use App\Models\OutreachType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Master Source/Type screen for the ADMIN (design.md, D-040, D-057).
 */
class MasterDataController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', OutreachSource::class);
        Gate::authorize('viewAny', OutreachType::class);

        $sourceUsage = Outreach::query()->selectRaw('source_id, count(*) as total')->groupBy('source_id')->pluck('total', 'source_id');
        $typeUsage = Outreach::query()->selectRaw('type_id, count(*) as total')->groupBy('type_id')->pluck('total', 'type_id');

        return Inertia::render('master/Index', [
            'sources' => OutreachSource::query()->orderBy('name')->get()->map(fn (OutreachSource $source) => [
                'id' => $source->id,
                'code' => $source->code,
                'name' => $source->name,
                'active' => $source->active,
                'used' => (int) ($sourceUsage[$source->id] ?? 0),
            ]),
            'types' => OutreachType::query()->orderBy('name')->get()->map(fn (OutreachType $type) => [
                'id' => $type->id,
                'code' => $type->code,
                'name' => $type->name,
                'active' => $type->active,
                'used' => (int) ($typeUsage[$type->id] ?? 0),
            ]),
        ]);
    }

    public function storeType(Request $request, ManageMasterData $master): RedirectResponse
    {
        Gate::authorize('create', OutreachType::class);
        $request->merge(['code' => strtoupper(trim($request->string('code')->toString()))]);
        $request->validate([
            // Codes look like the baseline ones and never change afterwards (D-040).
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Z][A-Z0-9_]*$/', Rule::unique('outreach_types', 'code')],
            'name' => ['required', 'string', 'max:100'],
        ], [
            'code.regex' => 'Kode hanya boleh huruf kapital, angka, dan garis bawah, diawali huruf.',
        ], ['code' => 'kode', 'name' => 'nama']);

        $type = $master->createType($request->user(), $request->string('code')->toString(), $request->string('name')->toString());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Jenis Outreach {$type->name} ditambahkan."]);

        return to_route('master-data.index');
    }

    public function updateSource(Request $request, OutreachSource $source, ManageMasterData $master): RedirectResponse
    {
        Gate::authorize('update', $source);

        return $this->update($request, $source, $master);
    }

    public function updateType(Request $request, OutreachType $type, ManageMasterData $master): RedirectResponse
    {
        Gate::authorize('update', $type);

        return $this->update($request, $type, $master);
    }

    /**
     * The code is not part of the request and can never change (D-040).
     */
    private function update(Request $request, OutreachSource|OutreachType $item, ManageMasterData $master): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'active' => ['required', 'boolean'],
        ], [], ['name' => 'nama']);

        $master->update($request->user(), $item, $request->string('name')->toString(), $request->boolean('active'));

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$request->string('name')} tersimpan."]);

        return to_route('master-data.index');
    }
}
