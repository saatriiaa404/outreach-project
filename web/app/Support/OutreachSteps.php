<?php

namespace App\Support;

use App\Models\Outreach;

/**
 * The stepper in Workflow v2.1 order (section 8.1): the form steps, then the
 * Source-dependent steps. Steps not built yet in this roadmap phase are
 * "upcoming" and cannot be opened.
 */
class OutreachSteps
{
    /**
     * @return list<array{key: string, title: string, state: string, missing?: int}>
     */
    public static function for(Outreach $outreach): array
    {
        $steps = [];

        foreach (OutreachFormSchema::steps() as $key => $definition) {
            $steps[] = [
                'key' => $key,
                'title' => $definition['title'],
                'state' => OutreachFormSchema::completion($outreach, $key),
                'missing' => count(OutreachFormSchema::missingFields($outreach, $key)),
            ];
        }

        foreach (OutreachFormSchema::upcomingSteps($outreach->source->code) as $key => $title) {
            $steps[] = match ($key) {
                'mustahik' => [
                    'key' => $key,
                    'title' => $title,
                    'state' => MustahikFormSchema::completion($outreach->mustahikForm),
                    'missing' => count(MustahikFormSchema::missing($outreach->mustahikForm)),
                ],
                'kifayah' => [
                    'key' => $key,
                    'title' => $title,
                    'state' => KifayahFormSchema::completion($outreach->kifayahForm),
                    'missing' => count(KifayahFormSchema::missing($outreach->kifayahForm)),
                ],
                'lampiran' => self::attachmentStep($outreach, $title),
                default => ['key' => $key, 'title' => $title, 'state' => 'upcoming'],
            };
        }

        return $steps;
    }

    /**
     * The step after $step if it can be opened, otherwise null.
     */
    public static function nextAfter(Outreach $outreach, string $step): ?string
    {
        $steps = self::for($outreach);
        $position = array_search($step, array_column($steps, 'key'), true);
        $next = $position === false ? null : ($steps[$position + 1] ?? null);

        return $next && $next['state'] !== 'upcoming' ? $next['key'] : null;
    }

    /**
     * @return array{key: string, title: string, state: string, missing: int}
     */
    private static function attachmentStep(Outreach $outreach, string $title): array
    {
        $missing = count(OutreachFormSchema::missingAttachments($outreach));

        return [
            'key' => 'lampiran',
            'title' => $title,
            'state' => match (true) {
                ! $outreach->attachments()->exists() => 'untouched',
                $missing > 0 => 'incomplete',
                default => 'complete',
            },
            'missing' => $missing,
        ];
    }
}
