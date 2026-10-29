<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Accounts are created by the Admin only, and the prototype keeps no Laravel
 * infrastructure tables besides `migrations` (testing.md scenario 23, D-030).
 */
class UnavailableAuthFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private const DOMAIN_TABLES = [
        'archive_records', 'audit_logs', 'document_template_versions', 'files', 'history_events',
        'kifayah_answers', 'kifayah_forms', 'mustahik_answers', 'mustahik_forms', 'notifications',
        'outreach_approvals', 'outreach_attachments', 'outreach_sources', 'outreach_types',
        'outreach_version_attachments', 'outreach_versions', 'outreaches', 'signature_templates',
        'signatures', 'special_unlock_requests', 'stamp_templates', 'users',
    ];

    public function test_public_registration_and_password_reset_are_not_available()
    {
        foreach (['/register', '/forgot-password', '/reset-password/token', '/email/verify'] as $uri) {
            $this->get($uri)->assertNotFound();
        }

        $this->post('/register', ['name' => 'Uji', 'email' => 'baru@example.test'])->assertNotFound();
        $this->post('/forgot-password', ['email' => 'baru@example.test'])->assertNotFound();
        $this->assertSame([], config('fortify.features'));
    }

    public function test_the_database_has_the_22_domain_tables_and_only_migrations_besides()
    {
        $tables = Schema::getTableListing(DB::connection()->getDatabaseName(), false);

        $this->assertCount(22, self::DOMAIN_TABLES);
        $this->assertEqualsCanonicalizing([...self::DOMAIN_TABLES, 'migrations'], $tables);
    }
}
