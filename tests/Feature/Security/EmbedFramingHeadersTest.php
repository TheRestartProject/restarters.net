<?php

namespace Tests\Feature\Security;

use PHPUnit\Framework\TestCase;

/**
 * X-Frame-Options is set by nginx (docker/nginx-fly.conf), so assert the map there:
 * embeddable routes must send no X-Frame-Options, everything else SAMEORIGIN.
 */
class EmbedFramingHeadersTest extends TestCase
{
    private function frameOptionsFor(string $uri): ?string
    {
        $conf = file_get_contents(__DIR__.'/../../../docker/nginx-fly.conf');
        $this->assertSame(1, preg_match('/map \$request_uri \$x_frame_options \{(.*?)\}/s', $conf, $m));

        $default = null;
        foreach (preg_split('/\R/', $m[1]) as $line) {
            $line = trim($line);
            if (preg_match('/^~(\S+)\s+"(.*)";$/', $line, $r) && preg_match('~'.str_replace('~', '\~', $r[1]).'~', $uri)) {
                return $r[2];
            }
            if (preg_match('/^default\s+"(.*)";$/', $line, $d)) {
                $default = $d[1];
            }
        }

        return $default;
    }

    public function testEmbedRoutesAllowFraming(): void
    {
        foreach (['/group/stats/825', '/group/stats/825/mini', '/group-tag/stats/3', '/party/stats/12/wide', '/admin/stats/1', '/outbound/info/group/825/fixometer'] as $uri) {
            $this->assertSame('', $this->frameOptionsFor($uri), $uri);
        }
    }

    public function testNormalPagesStayProtected(): void
    {
        foreach (['/', '/group/view/825', '/group/stats-evil', '/user/login', '/party/view/12'] as $uri) {
            $this->assertSame('SAMEORIGIN', $this->frameOptionsFor($uri), $uri);
        }
    }
}
