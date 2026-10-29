<?php

namespace App\Http\Controllers;

use App\Actions\Outreach\SpecialUnlockRequests;
use App\Enums\SpecialUnlockRequestStatus;
use App\Models\Outreach;
use App\Models\SpecialUnlockRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Special Unlock requests (Workflow v2.1 25.9, 31.4): requested from the
 * Outreach page, worked by the Admin through the request queue. No
 * notification is sent for a request on v1.
 */
class SpecialUnlockRequestController extends Controller
{
    /**
     * The Admin queue, open requests first and oldest first.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', SpecialUnlockRequest::class);

        $status = SpecialUnlockRequestStatus::tryFrom($request->string('status')->toString());

        $requests = SpecialUnlockRequest::query()
            ->with(['outreach:id,outreach_number,draft_code,status', 'requester:id,name', 'admin:id,name'])
            ->when($status, fn ($query, SpecialUnlockRequestStatus $status) => $query->where('status', $status))
            ->when(! $status && ! $request->has('status'), fn ($query) => $query->whereIn('status', SpecialUnlockRequestStatus::open()))
            ->oldest('created_at')
            ->oldest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (SpecialUnlockRequest $item) => [
                ...self::row($item),
                'outreach' => [
                    'id' => $item->outreach_id,
                    'code' => $item->outreach->outreach_number ?? $item->outreach->draft_code,
                    'status' => $item->outreach->status->value,
                ],
            ]);

        return Inertia::render('special-unlock/Index', [
            'requests' => $requests,
            'status' => $status !== null ? $status->value : ($request->has('status') ? null : 'OPEN'),
            'statuses' => collect(SpecialUnlockRequestStatus::cases())->map(fn (SpecialUnlockRequestStatus $item) => [
                'value' => $item->value,
                'label' => $item->label(),
            ]),
        ]);
    }

    public function store(Request $request, Outreach $outreach, SpecialUnlockRequests $requests): RedirectResponse
    {
        Gate::authorize('requestSpecialUnlock', $outreach);
        $request->validate(['reason' => ['required', 'string', 'max:15000']], [], ['reason' => 'alasan']);

        $requests->create($outreach, $request->user(), $request->string('reason')->toString());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Permintaan Special Unlock dikirim ke antrean Admin.']);

        return to_route('outreaches.show', $outreach);
    }

    public function cancel(SpecialUnlockRequest $specialUnlockRequest, SpecialUnlockRequests $requests): RedirectResponse
    {
        Gate::authorize('cancel', $specialUnlockRequest);

        $requests->cancel($specialUnlockRequest);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Permintaan Special Unlock dibatalkan.']);

        return to_route('outreaches.show', $specialUnlockRequest->outreach_id);
    }

    public function process(Request $request, SpecialUnlockRequest $specialUnlockRequest, SpecialUnlockRequests $requests): RedirectResponse
    {
        Gate::authorize('process', $specialUnlockRequest);

        $requests->startProcessing($specialUnlockRequest, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Permintaan mulai diproses. Pengaju tidak dapat membatalkannya lagi.']);

        return back();
    }

    public function reject(Request $request, SpecialUnlockRequest $specialUnlockRequest, SpecialUnlockRequests $requests): RedirectResponse
    {
        Gate::authorize('process', $specialUnlockRequest);
        // The reason is mandatory and shown to the requester (D-055, 35.5).
        $request->validate(['processed_reason' => ['required', 'string', 'max:15000']], [], ['processed_reason' => 'alasan penolakan']);

        $requests->reject($specialUnlockRequest, $request->user(), $request->string('processed_reason')->toString());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Permintaan Special Unlock ditolak.']);

        return back();
    }

    /**
     * What the requester and the Admin see of a request.
     *
     * @return array<string, mixed>
     */
    public static function row(SpecialUnlockRequest $request): array
    {
        return [
            'id' => $request->id,
            'requester' => $request->requester->name,
            'requester_role' => $request->requester_role_snapshot->value,
            'reason' => $request->reason,
            'status' => $request->status->value,
            'status_label' => $request->status->label(),
            'admin' => $request->admin?->name,
            'processed_reason' => $request->processed_reason,
            'created_at' => $request->created_at?->toIso8601String(),
            'processed_at' => $request->processed_at?->toIso8601String(),
        ];
    }
}
