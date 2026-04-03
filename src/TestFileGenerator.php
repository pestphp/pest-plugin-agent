<?php

declare(strict_types=1);

namespace Pest\AI;

/**
 * @internal
 */
final class TestFileGenerator
{
    private ?string $path = null;

    public function __construct(
        private readonly TestCodeGenerator $codeGenerator = new TestCodeGenerator,
    ) {}

    /**
     * @param  array<int, string>  $uses
     */
    public function generate(string $code, array $uses, ?string $namespace = null): string
    {
        $this->path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'.pest_ai_verify_'.bin2hex(random_bytes(8)).'.php';

        file_put_contents($this->path, $this->codeGenerator->generate($code, $uses, $namespace));

        return $this->path;
    }

    public function cleanup(): void
    {
        if ($this->path !== null && file_exists($this->path)) {
            @unlink($this->path);
        }

        $this->path = null;
    }
}
