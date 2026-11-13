<?php

/**
 * Document-level integrity check for the generated OpenAPI spec.
 *
 * darkaonline/l5-swagger regenerates storage/api-docs/api-docs.json from the
 * @OA annotations, but nothing validates the resulting DOCUMENT: the osteel
 * response-validator (tests/TestCase.php) only checks individual response
 * bodies for GET/PATCH/POST on /api/v2. This catches the class of defect the
 * 2026-07 API audit found — every v2 operation's security requirement pointing
 * at a securityScheme name that didn't exist (71 dangling references) — plus
 * dangling #/components/{schemas,responses} $refs and duplicate operationIds.
 *
 * Usage:  php tools/openapi-lint.php [path-to-spec.json]
 * Exit 0 = clean, 1 = integrity errors (printed to STDERR).
 */

$path = $argv[1] ?? __DIR__ . '/../storage/api-docs/api-docs.json';

if (!is_file($path)) {
    fwrite(STDERR, "openapi-lint: spec not found at $path — run `php artisan l5-swagger:generate` first.\n");
    exit(1);
}

$spec = json_decode(file_get_contents($path), true);
if (!is_array($spec)) {
    fwrite(STDERR, "openapi-lint: $path is not valid JSON.\n");
    exit(1);
}

$errors = [];

$schemes = array_keys($spec['components']['securitySchemes'] ?? []);
$schemas = $spec['components']['schemas'] ?? [];
$responses = $spec['components']['responses'] ?? [];
$parameters = $spec['components']['parameters'] ?? [];
$requestBodies = $spec['components']['requestBodies'] ?? [];

$componentBuckets = [
    'schemas' => $schemas,
    'responses' => $responses,
    'parameters' => $parameters,
    'requestBodies' => $requestBodies,
    'securitySchemes' => array_flip($schemes),
];

// 1) Every local $ref must resolve to a defined component.
$walk = function ($node, $trail) use (&$walk, &$errors, $componentBuckets) {
    if (!is_array($node)) {
        return;
    }
    foreach ($node as $key => $value) {
        if ($key === '$ref' && is_string($value) && str_starts_with($value, '#/components/')) {
            $parts = explode('/', $value);
            $bucket = $parts[2] ?? '';
            $name = $parts[3] ?? '';
            if (!isset($componentBuckets[$bucket]) || !array_key_exists($name, $componentBuckets[$bucket])) {
                $errors[] = "dangling \$ref '$value' at " . implode('.', $trail);
            }
        } else {
            $walk($value, array_merge($trail, [$key]));
        }
    }
};
$walk($spec['paths'] ?? [], ['paths']);
$walk($spec['components'] ?? [], ['components']);

// 2) Every security requirement name must match a defined securityScheme.
$opIds = [];
foreach (($spec['paths'] ?? []) as $route => $methods) {
    if (!is_array($methods)) {
        continue;
    }
    foreach ($methods as $verb => $op) {
        if (!is_array($op)) {
            continue;
        }
        foreach (($op['security'] ?? []) as $requirement) {
            foreach (array_keys((array) $requirement) as $name) {
                if (!in_array($name, $schemes, true)) {
                    $errors[] = "security requirement '$name' on {$verb} {$route} has no matching securityScheme (defined: " . implode(', ', $schemes) . ')';
                }
            }
        }
        if (isset($op['operationId'])) {
            $opIds[$op['operationId']][] = "{$verb} {$route}";
        }
    }
}

// 3) operationIds must be unique.
foreach ($opIds as $id => $where) {
    if (count($where) > 1) {
        $errors[] = "duplicate operationId '$id' used by: " . implode(' , ', $where);
    }
}

if ($errors) {
    fwrite(STDERR, "openapi-lint: " . count($errors) . " integrity error(s):\n");
    foreach ($errors as $e) {
        fwrite(STDERR, "  - $e\n");
    }
    exit(1);
}

$opCount = 0;
foreach (($spec['paths'] ?? []) as $methods) {
    $opCount += is_array($methods) ? count(array_filter($methods, 'is_array')) : 0;
}
echo "openapi-lint: OK — {$opCount} operations, " . count($schemas) . " schemas, "
    . count($responses) . " response components, " . count($schemes) . " security schemes; no dangling refs.\n";
exit(0);
