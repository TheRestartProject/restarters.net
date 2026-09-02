<?php

namespace App\Console\Commands;

use App\Group;
use HieuLe\WordpressXmlrpcClient\WordpressClient;
use Illuminate\Console\Command;

/**
 * Backfill for groups whose WordPress group_avatar_url custom field holds a bare filename rather than a
 * full URL.  Editing a group used to push env('UPLOADS_URL').'mid_'.$path, and UPLOADS_URL has never been
 * defined in the Laravel app, so every group edited since the port left WordPress with an unrenderable
 * src like "mid_1652779858....png" (or the literal string "null" for groups with no image at all).
 */
class WordpressFixGroupAvatars extends Command
{
    protected $signature = 'wordpress:group:fix-avatars
                            {--dry-run : Report what would change without touching WordPress}
                            {--id=* : Only check these group ids}
                            {--limit= : Stop after this many groups have been checked}
                            {--sleep=0 : Seconds to wait between groups, to go easy on WordPress}';

    protected $description = 'Repair group_avatar_url custom fields in WordPress which hold a relative path';

    /**
     * The client is injected here rather than into the constructor.  Artisan resolves commands when it
     * boots, which can happen well before the command runs, so a constructor dependency would be pinned to
     * whatever was bound at boot time.
     */
    public function handle(WordpressClient $wpClient): int
    {
        if (! config('restarters.features.wordpress_integration')) {
            $this->error('WordPress integration is disabled on this site.');

            return self::FAILURE;
        }

        $dryRun = $this->option('dry-run');
        $sleep = (float) $this->option('sleep');

        $query = Group::whereNotNull('wordpress_post_id')
            ->where('wordpress_post_id', '>', 0)
            ->orderBy('idgroups');

        if ($ids = $this->option('id')) {
            $query->whereIn('idgroups', $ids);
        }

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $groups = $query->get();

        $this->info(($dryRun ? '[dry run] ' : '').'Checking '.$groups->count().' groups with a WordPress post.');

        $checked = $fixed = $skipped = $failed = 0;

        foreach ($groups as $group) {
            $checked++;

            try {
                $post = $wpClient->getPost($group->wordpress_post_id);
            } catch (\Exception $e) {
                $failed++;
                $this->warn(sprintf(
                    'Group %d (post %s): could not read post - %s',
                    $group->idgroups,
                    $group->wordpress_post_id,
                    $e->getMessage()
                ));

                continue;
            }

            $field = $this->findCustomField($post, 'group_avatar_url');
            $current = $field['value'] ?? null;

            if ($this->isUsable($current)) {
                $skipped++;

                continue;
            }

            $wanted = $group->groupImagePath();

            $this->line(sprintf(
                'Group %d (post %s) "%s": %s -> %s',
                $group->idgroups,
                $group->wordpress_post_id,
                $group->name,
                $current === null ? '(no field)' : var_export($current, true),
                $wanted
            ));

            if ($dryRun) {
                $fixed++;

                continue;
            }

            // Send only the avatar field, so we don't disturb anything else on the post.  The field's own
            // id has to go with it, otherwise WordPress adds a second group_avatar_url rather than
            // replacing the existing one.
            $replacement = ['key' => 'group_avatar_url', 'value' => $wanted];

            if (isset($field['id'])) {
                $replacement['id'] = $field['id'];
            }

            try {
                $wpClient->editPost($group->wordpress_post_id, ['custom_fields' => [$replacement]]);
                $fixed++;
            } catch (\Exception $e) {
                $failed++;
                $this->warn(sprintf(
                    'Group %d (post %s): could not update post - %s',
                    $group->idgroups,
                    $group->wordpress_post_id,
                    $e->getMessage()
                ));
            }

            if ($sleep > 0) {
                usleep((int) ($sleep * 1000000));
            }
        }

        $this->info(sprintf(
            '%s: %d checked, %d %s, %d already correct, %d failed.',
            $dryRun ? 'Dry run' : 'Done',
            $checked,
            $fixed,
            $dryRun ? 'would be fixed' : 'fixed',
            $skipped,
            $failed
        ));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function findCustomField($post, string $key): ?array
    {
        if (! is_array($post) || ! isset($post['custom_fields']) || ! is_array($post['custom_fields'])) {
            return null;
        }

        foreach ($post['custom_fields'] as $field) {
            if (isset($field['key']) && $field['key'] === $key) {
                return $field;
            }
        }

        return null;
    }

    /**
     * WordPress can only render the avatar if it's an absolute URL.  Anything else - a bare "mid_x.png",
     * an empty string, or the literal string "null" that we used to send for groups with no image - needs
     * replacing.
     */
    private function isUsable($value): bool
    {
        return is_string($value) && preg_match('#^https?://#i', $value) === 1;
    }
}
