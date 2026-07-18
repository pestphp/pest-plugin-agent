<?php

declare(strict_types=1);

namespace Pest\Agent;

use InvalidArgumentException;
use Pest\Contracts\Plugins\HandlesArguments;
use Pest\Contracts\Plugins\Terminable;
use Pest\Plugins\Concerns\HandleArguments;
use Pest\TestSuite;

/**
 * @internal
 */
final class Plugin implements HandlesArguments, Terminable
{
    use HandleArguments;

    public function __construct(
        private readonly TestFileGenerator $fileGenerator = new TestFileGenerator,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function handleArguments(array $arguments): array
    {
        if (! $this->hasArgument('--agent', $arguments)) {
            return $arguments;
        }

        [$codes, $arguments] = $this->pullCodes($arguments);

        [$uses, $namespace] = $this->resolveUses(TestSuite::getInstance());

        foreach ($codes as $code) {
            $arguments[] = $this->fileGenerator->generate($code, $uses, $namespace);
        }

        return $arguments;
    }

    /**
     * {@inheritDoc}
     */
    public function terminate(): void
    {
        $this->fileGenerator->cleanup();
    }

    /**
     * @param  array<int, string>  $arguments
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    private function pullCodes(array $arguments): array
    {
        $codes = [];
        $remaining = [];
        $count = count($arguments);

        for ($index = 0; $index < $count; $index++) {
            $argument = $arguments[$index];

            if ($argument === '--agent') {
                $code = $arguments[++$index] ?? null;

                if ($code !== null && preg_match('/^--[a-z]/i', $code) === 1) {
                    $code = null;
                }
            } elseif (str_starts_with($argument, '--agent=')) {
                $code = mb_substr($argument, mb_strlen('--agent='));
            } else {
                $remaining[] = $argument;

                continue;
            }

            if ($code === null || mb_trim($code) === '') {
                throw new InvalidArgumentException('The [--agent] option requires a non-empty PHP code snippet.');
            }

            $codes[] = $code;
        }

        return [$codes, $remaining];
    }

    /**
     * @return array{0: array<int, string>, 1: string|null}
     */
    private function resolveUses(TestSuite $testSuite): array
    {
        $testsPath = $testSuite->rootPath.DIRECTORY_SEPARATOR.$testSuite->testPath;

        $baseUses = [
            ...$testSuite->tests->getUsesForPath($testSuite->rootPath),
            ...$testSuite->tests->getUsesForPath($testsPath),
        ];

        foreach ($this->testDirectories($testsPath) as $directory) {
            $uses = $testSuite->tests->getUsesForPath($testsPath.DIRECTORY_SEPARATOR.$directory);

            if ($uses !== []) {
                return [array_values(array_unique([...$baseUses, ...$uses])), $this->namespaceFor($directory)];
            }
        }

        return [array_values(array_unique($baseUses)), 'P\\Tests'];
    }

    /**
     * @return array<int, string>
     */
    private function testDirectories(string $testsPath): array
    {
        $preferred = ['Browser', 'Feature', 'Integration', 'Unit'];

        $directories = array_map(
            basename(...),
            glob($testsPath.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR) ?: [],
        );

        $directories = array_filter(
            $directories,
            static fn (string $directory): bool => ! in_array($directory, $preferred, true),
        );

        sort($directories);

        return [...$preferred, ...$directories];
    }

    private function namespaceFor(string $directory): string
    {
        $directory = (string) preg_replace('/[^\p{L}\p{N}]/u', '', $directory);

        return $directory === '' ? 'P\\Tests' : 'P\\Tests\\'.$directory;
    }
}
