<?php

namespace Tests\Feature\Users;

use App\User;
use Tests\TestCase;

/**
 * Regression test: credential and PII fields must never be written to the
 * audit log, regardless of whether they change on a user update.
 */
class UserAuditExcludeTest extends TestCase
{
    private array $sensitiveFields = [
        'api_token',
        'calendar_hash',
        'recovery',
        'recovery_expires',
        'latitude',
        'longitude',
        'password',
    ];

    public function testCredentialFieldsAreNotWrittenToAuditLog(): void
    {
        $user = User::factory()->restarter()->create();

        // Trigger an audit by updating a sensitive field alongside a benign one.
        \DB::table('users')->where('id', $user->id)->update([
            'api_token'     => 'NEW_API_TOKEN',
            'calendar_hash' => 'NEW_CALENDAR_HASH',
            'recovery'      => 'NEW_RECOVERY',
            'name'          => 'Updated Name',
        ]);

        // Re-fetch via Eloquent so the auditing trait fires on save.
        $user->refresh();
        $user->name = 'Triggered Audit';
        $user->save();

        $audits = \OwenIt\Auditing\Models\Audit::where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->get();

        $this->assertNotEmpty($audits, 'At least one audit record should exist');

        foreach ($audits as $audit) {
            $oldValues = $audit->old_values ?? [];
            $newValues = $audit->new_values ?? [];

            foreach ($this->sensitiveFields as $field) {
                $this->assertArrayNotHasKey(
                    $field,
                    $oldValues,
                    "Field '$field' must not appear in audit old_values"
                );
                $this->assertArrayNotHasKey(
                    $field,
                    $newValues,
                    "Field '$field' must not appear in audit new_values"
                );
            }
        }
    }
}
