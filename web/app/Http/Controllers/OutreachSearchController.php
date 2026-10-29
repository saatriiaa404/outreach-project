<?php

namespace App\Http\Controllers;

use App\Models\Outreach;
use App\Support\OutreachSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Keeps the search criteria in the session so a NIK never appears in a URL
 * (security.md, D-061). The list reads them back.
 */
class OutreachSearchController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('viewAny', Outreach::class);
        $validated = $request->validate([
            'nama' => ['nullable', 'string', 'max:255'],
            'nik' => ['nullable', 'string', 'max:32'],
            'code' => ['nullable', 'string', 'max:50'],
            'source_id' => ['nullable', 'integer', 'exists:outreach_sources,id'],
            'type_id' => ['nullable', 'integer', 'exists:outreach_types,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ], [], ['nama' => 'nama', 'nik' => 'NIK', 'code' => 'ID', 'date_from' => 'tanggal awal', 'date_to' => 'tanggal akhir']);

        $criteria = [];

        foreach (OutreachSearch::FIELDS as $field) {
            $value = trim((string) ($validated[$field] ?? ''));

            if ($value !== '') {
                $criteria[$field] = in_array($field, ['source_id', 'type_id'], true) ? (int) $value : $value;
            }
        }

        $request->session()->put(OutreachSearch::SESSION_KEY, $criteria);

        // Searching looks across statuses unless the list was filtered.
        return to_route('outreaches.index', ['status' => $request->string('status')->toString()]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget(OutreachSearch::SESSION_KEY);

        return to_route('outreaches.index');
    }
}
