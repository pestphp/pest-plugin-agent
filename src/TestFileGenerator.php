<?php

declare(strict_types=1);

namespace Pest\Agent;

use RuntimeException;

/**
 * @internal
 */
final class TestFileGenerator
{
    /**
     * @var array<int, string>
     */
    private array $paths = [];

    public function __construct(
        private readonly TestCodeGenerator $codeGenerator = new TestCodeGenerator,
    ) {}

    /**
     * @param  array<int, string>  $uses
     */
    public function generate(string $code, array $uses, ?string $namespace = null): string
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'PestAgent'.bin2hex(random_bytes(8)).'.php';

        if (@file_put_contents($path, $this->codeGenerator->generate($code, $uses, $namespace)) === false) {
            throw new RuntimeException(sprintf('Unable to write the temporary test file [%s].', $path));
        }

        $this->paths[] = $path;

        return $path;
    }

    public function cleanup(): void
    {
        foreach ($this->paths as $path) {
            if (file_exists($path)) {
                @unlink($path);
            }
        }

        $this->paths = [];
    }
}
