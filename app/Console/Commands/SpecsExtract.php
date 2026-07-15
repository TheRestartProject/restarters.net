<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\Node;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class SpecsExtract extends Command
{
    protected $signature = 'specs:extract {--check : Compare against existing manifest instead of writing}';

    protected $description = 'Extract #[Feature] and #[UserStory] attributes into docs/specs/manifest.json';

    public function handle(): int
    {
        $this->info('Scanning for specifications...');

        $features = [];
        $this->scanPhpFiles($features);
        $this->scanTestFiles($features);

        $personas = $this->buildPersonaIndex($features);
        $coverage = $this->buildCoverageStats($features);

        $manifest = [
            'generatedAt' => gmdate('Y-m-d\TH:i:s\Z'),
            'features' => $features,
            'personas' => $personas,
            'coverage' => $coverage,
        ];

        $outputPath = base_path('docs/specs/manifest.json');

        if ($this->option('check')) {
            return $this->checkManifest($manifest, $outputPath);
        }

        if (! is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0755, true);
        }

        // Write without generatedAt for deterministic output, then re-add for the file
        $stableManifest = $manifest;
        unset($stableManifest['generatedAt']);
        $stableManifest = ['generatedAt' => $manifest['generatedAt']] + $stableManifest;

        file_put_contents($outputPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

        $storyCount = array_sum(array_map(fn ($f) => $f['storyCount'], $features));
        $featureCount = count($features);
        $personaCount = count($personas);

        $this->info("Extracted {$storyCount} stories across {$featureCount} features and {$personaCount} personas.");
        $this->info("Manifest written to docs/specs/manifest.json");

        $this->reportWarnings($features);

        return Command::SUCCESS;
    }

    private function scanPhpFiles(array &$features): void
    {
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $appPath = base_path('app');

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($appPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $code = file_get_contents($file->getPathname());
            $relativePath = str_replace(base_path() . '/', '', $file->getPathname());

            try {
                $ast = $parser->parse($code);
            } catch (\Exception $e) {
                $this->warn("Failed to parse {$relativePath}: {$e->getMessage()}");
                continue;
            }

            if ($ast === null) {
                continue;
            }

            $this->extractFromAst($ast, $relativePath, $features);
        }

        // Sort features alphabetically
        ksort($features);
        foreach ($features as &$feature) {
            usort($feature['stories'], fn ($a, $b) => strcmp($a['persona'], $b['persona']) ?: strcmp($a['method'], $b['method']));
            sort($feature['personas']);
        }
    }

    private function extractFromAst(array $ast, string $filePath, array &$features): void
    {
        $traverser = new NodeTraverser();
        $visitor = new class extends NodeVisitorAbstract {
            public ?string $namespace = null;
            public ?string $className = null;
            public ?array $classFeature = null;
            public array $methods = [];

            public function enterNode(Node $node)
            {
                if ($node instanceof Node\Stmt\Namespace_) {
                    $this->namespace = $node->name ? $node->name->toString() : null;
                }

                if ($node instanceof Node\Stmt\Class_) {
                    $this->className = $node->name ? $node->name->toString() : null;
                    $this->classFeature = $this->findFeatureAttribute($node);
                }

                if ($node instanceof Node\Stmt\ClassMethod) {
                    $stories = $this->findUserStoryAttributes($node);
                    $noStory = $this->findNoStoryAttribute($node);
                    $isPublic = $node->isPublic();

                    $this->methods[] = [
                        'name' => $node->name->toString(),
                        'stories' => $stories,
                        'noStory' => $noStory,
                        'isPublic' => $isPublic,
                    ];
                }

                return null;
            }

            private function findFeatureAttribute(Node\Stmt\Class_ $node): ?array
            {
                foreach ($node->attrGroups as $attrGroup) {
                    foreach ($attrGroup->attrs as $attr) {
                        $name = $attr->name->toString();
                        if ($name === 'Feature' || str_ends_with($name, '\\Feature')) {
                            $args = $this->parseAttributeArgs($attr);
                            return [
                                'name' => $args[0] ?? $args['name'] ?? '',
                                'description' => $args['description'] ?? $args[1] ?? '',
                            ];
                        }
                    }
                }
                return null;
            }

            private function findUserStoryAttributes(Node\Stmt\ClassMethod $node): array
            {
                $stories = [];
                foreach ($node->attrGroups as $attrGroup) {
                    foreach ($attrGroup->attrs as $attr) {
                        $name = $attr->name->toString();
                        if ($name === 'UserStory' || str_ends_with($name, '\\UserStory')) {
                            $args = $this->parseAttributeArgs($attr);
                            $stories[] = [
                                'story' => $args[0] ?? $args['story'] ?? '',
                                'persona' => $args['persona'] ?? $args[1] ?? '',
                                'feature' => $args['feature'] ?? '',
                                'theme' => $args['theme'] ?? '',
                            ];
                        }
                    }
                }
                return $stories;
            }

            private function findNoStoryAttribute(Node\Stmt\ClassMethod $node): ?string
            {
                foreach ($node->attrGroups as $attrGroup) {
                    foreach ($attrGroup->attrs as $attr) {
                        $name = $attr->name->toString();
                        if ($name === 'NoStory' || str_ends_with($name, '\\NoStory')) {
                            $args = $this->parseAttributeArgs($attr);
                            return $args['reason'] ?? $args[0] ?? '';
                        }
                    }
                }
                return null;
            }

            private function parseAttributeArgs(Node\Attribute $attr): array
            {
                $args = [];
                $positional = 0;
                foreach ($attr->args as $arg) {
                    $value = $this->resolveValue($arg->value);
                    if ($arg->name) {
                        $args[$arg->name->toString()] = $value;
                    } else {
                        $args[$positional++] = $value;
                    }
                }
                return $args;
            }

            private function resolveValue(Node\Expr $expr): mixed
            {
                if ($expr instanceof Node\Scalar\String_) {
                    return $expr->value;
                }
                if ($expr instanceof Node\Scalar\LNumber) {
                    return $expr->value;
                }
                if ($expr instanceof Node\Expr\ConstFetch) {
                    $name = $expr->name->toString();
                    return match (strtolower($name)) {
                        'true' => true,
                        'false' => false,
                        'null' => null,
                        default => $name,
                    };
                }
                return '(complex expression)';
            }
        };

        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        if ($visitor->className === null) {
            return;
        }

        $classFeature = $visitor->classFeature;
        $shortClass = $visitor->className;

        foreach ($visitor->methods as $method) {
            foreach ($method['stories'] as $story) {
                $featureName = $story['feature'] ?: ($classFeature ? $classFeature['name'] : 'Uncategorised');
                $featureDesc = $classFeature ? $classFeature['description'] : '';

                if (! isset($features[$featureName])) {
                    $features[$featureName] = [
                        'description' => $featureDesc,
                        'sources' => [],
                        'stories' => [],
                        'storyCount' => 0,
                        'personas' => [],
                    ];
                }

                if ($featureDesc && ! $features[$featureName]['description']) {
                    $features[$featureName]['description'] = $featureDesc;
                }

                if (! in_array($filePath, $features[$featureName]['sources'])) {
                    $features[$featureName]['sources'][] = $filePath;
                }

                $features[$featureName]['stories'][] = [
                    'story' => $story['story'],
                    'persona' => $story['persona'],
                    'theme' => $story['theme'] ?: 'General',
                    'method' => "{$shortClass}::{$method['name']}",
                    'file' => $filePath,
                    'tests' => [],
                ];

                if (! in_array($story['persona'], $features[$featureName]['personas'])) {
                    $features[$featureName]['personas'][] = $story['persona'];
                }

                $features[$featureName]['storyCount'] = count($features[$featureName]['stories']);
            }
        }
    }

    private function scanTestFiles(array &$features): void
    {
        $storyIndex = [];
        foreach ($features as $featureName => &$feature) {
            foreach ($feature['stories'] as $idx => &$story) {
                $storyIndex[$story['method']][] = [
                    'feature' => $featureName,
                    'index' => $idx,
                ];
            }
        }

        $testDirs = [
            base_path('tests'),
        ];

        foreach ($testDirs as $dir) {
            if (! is_dir($dir)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                $ext = $file->getExtension();
                if (! in_array($ext, ['php', 'js', 'ts'])) {
                    continue;
                }

                $content = file_get_contents($file->getPathname());
                $relativePath = str_replace(base_path() . '/', '', $file->getPathname());

                preg_match_all('/@story:(\w+::\w+)/', $content, $matches, PREG_SET_ORDER);

                foreach ($matches as $match) {
                    $methodRef = $match[1];
                    if (isset($storyIndex[$methodRef])) {
                        $testName = $this->extractTestName($content, $match[0], $ext);

                        foreach ($storyIndex[$methodRef] as $ref) {
                            $features[$ref['feature']]['stories'][$ref['index']]['tests'][] = [
                                'file' => $relativePath,
                                'test' => $testName,
                            ];
                        }
                    }
                }
            }
        }
    }

    private function extractTestName(string $content, string $storyRef, string $ext): string
    {
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            if (str_contains($line, $storyRef)) {
                if ($ext === 'php') {
                    if (preg_match('/function\s+(\w+)/', $line, $m)) {
                        return $m[1];
                    }
                    // Check previous lines for function declaration
                    $lineIdx = array_search($line, $lines);
                    for ($i = $lineIdx; $i >= max(0, $lineIdx - 5); $i--) {
                        if (preg_match('/function\s+(\w+)/', $lines[$i], $m)) {
                            return $m[1];
                        }
                    }
                } else {
                    if (preg_match("/(?:test|it)\s*\(\s*['\"](.+?)['\"]/", $line, $m)) {
                        return $m[1];
                    }
                }
            }
        }
        return '(unknown test)';
    }

    private function buildPersonaIndex(array $features): array
    {
        $personas = [];
        foreach ($features as $featureName => $feature) {
            foreach ($feature['stories'] as $story) {
                $persona = $story['persona'];
                if (! isset($personas[$persona])) {
                    $personas[$persona] = [
                        'features' => [],
                        'storyCount' => 0,
                    ];
                }
                if (! in_array($featureName, $personas[$persona]['features'])) {
                    $personas[$persona]['features'][] = $featureName;
                }
                $personas[$persona]['storyCount']++;
            }
        }

        ksort($personas);
        foreach ($personas as &$persona) {
            sort($persona['features']);
        }

        return $personas;
    }

    private function buildCoverageStats(array $features): array
    {
        $annotated = 0;
        $withTests = 0;

        foreach ($features as $feature) {
            foreach ($feature['stories'] as $story) {
                $annotated++;
                if (! empty($story['tests'])) {
                    $withTests++;
                }
            }
        }

        return [
            'annotatedStories' => $annotated,
            'storiesWithTests' => $withTests,
        ];
    }

    private function reportWarnings(array $features): void
    {
        $uncovered = [];
        foreach ($features as $featureName => $feature) {
            foreach ($feature['stories'] as $story) {
                if (empty($story['tests'])) {
                    $uncovered[] = "{$story['method']} ({$featureName})";
                }
            }
        }

        if ($uncovered) {
            $this->warn('Stories without test coverage:');
            foreach ($uncovered as $method) {
                $this->line("  - {$method}");
            }
        }
    }

    private function checkManifest(array $manifest, string $outputPath): int
    {
        if (! file_exists($outputPath)) {
            $this->error('No manifest found at docs/specs/manifest.json. Run specs:extract to generate it.');
            return Command::FAILURE;
        }

        $existing = json_decode(file_get_contents($outputPath), true);

        // Compare without generatedAt timestamp
        $compareNew = $manifest;
        $compareExisting = $existing;
        unset($compareNew['generatedAt'], $compareExisting['generatedAt']);

        if ($compareNew === $compareExisting) {
            $this->info('Manifest is up to date.');
            return Command::SUCCESS;
        }

        $this->error('Manifest is out of date. Run php artisan specs:extract to update it.');
        return Command::FAILURE;
    }
}
