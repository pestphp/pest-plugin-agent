<?php

declare(strict_types=1);

namespace Pest\Agent;

/**
 * @internal
 */
final class TestCodeGenerator
{
    /**
     * @param  array<int, string>  $uses
     */
    public function generate(string $code, array $uses, ?string $namespace = null): string
    {
        $contents = "<?php\n\n";

        if ($uses !== []) {
            $contents .= 'pest()->uses('.implode(', ', array_map(fn (string $class): string => '\\'.$class.'::class', $uses)).");\n\n";
        }

        $contents .= "it('verify', function () {\n    ".$code."\n});\n";

        if ($namespace !== null) {
            $contents .= "\n\\Pest\\TestSuite::getInstance()->tests->get(__FILE__)->namespace = '".$namespace."';\n";
        }

        return $contents;
    }
}
