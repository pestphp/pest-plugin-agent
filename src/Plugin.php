<?php

declare(strict_types=1);

namespace Pest\AgentBrowser;

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
        $code = $this->popArgumentValue('--agent-browser', $arguments);

        if ($code === null) {
            return $arguments;
        }

        $testSuite = TestSuite::getInstance();

        [$uses, $namespace] = $this->resolveUses($testSuite);

        $arguments[] = $this->fileGenerator->generate($code, $uses, $namespace);

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
     * @return array{0: array<int, string>, 1: string|null}
     */
    private function resolveUses(TestSuite $testSuite): array
    {
        $rootUses = $testSuite->tests->getUsesForPath($testSuite->rootPath);
        $basePath = $testSuite->rootPath.DIRECTORY_SEPARATOR.$testSuite->testPath.DIRECTORY_SEPARATOR;

        foreach (['Browser', 'Feature', 'Integration', 'Unit'] as $directory) {
            $uses = $testSuite->tests->getUsesForPath($basePath.$directory);

            if ($uses !== []) {
                return [[...$rootUses, ...$uses], 'P\\Tests\\'.$directory];
            }
        }

        return [$rootUses, 'P\\Tests'];
    }
}
