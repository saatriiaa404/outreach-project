<?php

namespace Tests\Feature\Authorization;

use App\Enums\OutreachStatus;
use App\Models\Outreach;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OutreachPolicyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Expected abilities per role and status, from roles-permissions.md,
     * Workflow v2.1, D-016, D-039, and D-040. "owner" is the Petugas who owns
     * the Outreach; "other" is a different Petugas.
     *
     * @return array<string, array{string, OutreachStatus, string, bool}>
     */
    public static function matrix(): array
    {
        $cases = [];
        $expect = [
            'owner' => [
                'DRAFT' => ['view', 'viewSummary', 'update', 'finalize'],
                'MENUNGGU_PENGESAHAN' => ['view', 'viewSummary', 'requestSpecialUnlock'],
                'PERLU_PERBAIKAN' => ['view', 'viewSummary', 'update', 'finalize'],
                'SELESAI' => ['view', 'viewSummary', 'requestSpecialUnlock'],
            ],
            'other' => [
                'DRAFT' => [],
                'MENUNGGU_PENGESAHAN' => [],
                'PERLU_PERBAIKAN' => [],
                'SELESAI' => [],
            ],
            'kepala' => [
                'DRAFT' => [],
                'MENUNGGU_PENGESAHAN' => ['view', 'viewSummary', 'return', 'approve', 'requestSpecialUnlock'],
                'PERLU_PERBAIKAN' => ['viewSummary'],
                'SELESAI' => ['view', 'viewSummary', 'requestSpecialUnlock', 'downloadFinalPdf'],
            ],
            'admin' => [
                'DRAFT' => ['view', 'viewSummary'],
                'MENUNGGU_PENGESAHAN' => ['view', 'viewSummary', 'specialUnlock'],
                'PERLU_PERBAIKAN' => ['view', 'viewSummary'],
                'SELESAI' => ['view', 'viewSummary', 'specialUnlock', 'downloadFinalPdf'],
            ],
        ];
        $abilities = ['view', 'viewSummary', 'update', 'finalize', 'return', 'approve', 'requestSpecialUnlock', 'specialUnlock', 'downloadFinalPdf'];

        foreach ($expect as $actor => $byStatus) {
            foreach ($byStatus as $status => $allowed) {
                foreach ($abilities as $ability) {
                    $cases["{$actor} {$ability} {$status}"] = [$actor, OutreachStatus::from($status), $ability, in_array($ability, $allowed, true)];
                }
            }
        }

        return $cases;
    }

    #[DataProvider('matrix')]
    public function test_outreach_abilities_follow_the_role_matrix(string $actor, OutreachStatus $status, string $ability, bool $allowed)
    {
        $owner = User::factory()->create();
        $outreach = Outreach::factory()->for($owner, 'petugas')->status($status)->create();

        $user = match ($actor) {
            'owner' => $owner,
            'other' => User::factory()->create(),
            'kepala' => User::factory()->kepala()->create(),
            'admin' => User::factory()->admin()->create(),
        };

        $this->assertSame($allowed, $user->can($ability, $outreach));
    }

    public function test_only_petugas_can_create_outreach()
    {
        $this->assertTrue(User::factory()->create()->can('create', Outreach::class));
        $this->assertFalse(User::factory()->kepala()->create()->can('create', Outreach::class));
        $this->assertFalse(User::factory()->admin()->create()->can('create', Outreach::class));
    }

    public function test_inactive_users_are_denied_everything()
    {
        $owner = User::factory()->inactive()->create();
        $outreach = Outreach::factory()->for($owner, 'petugas')->create();

        $this->assertFalse($owner->can('view', $outreach));
        $this->assertFalse($owner->can('update', $outreach));
        $this->assertFalse($owner->can('create', Outreach::class));
        $this->assertFalse(User::factory()->admin()->inactive()->create()->can('view', $outreach));
    }

    public function test_visible_to_scope_limits_lists_by_role()
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $mine = collect(OutreachStatus::cases())
            ->map(fn (OutreachStatus $status) => Outreach::factory()->for($owner, 'petugas')->status($status)->create());
        Outreach::factory()->for($other, 'petugas')->status(OutreachStatus::Draft)->create();

        $this->assertEqualsCanonicalizing(
            $mine->pluck('id')->all(),
            Outreach::visibleTo($owner)->pluck('id')->all(),
        );

        $kepalaSees = Outreach::visibleTo(User::factory()->kepala()->create())->get();
        $this->assertCount(3, $kepalaSees);
        $this->assertNotContains(OutreachStatus::Draft, $kepalaSees->pluck('status')->all());

        $this->assertCount(5, Outreach::visibleTo(User::factory()->admin()->create())->get());
        $this->assertCount(0, Outreach::visibleTo(User::factory()->admin()->inactive()->create())->get());
    }
}
