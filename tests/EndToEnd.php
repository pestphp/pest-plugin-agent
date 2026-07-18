<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

function runPestWithAgent(string ...$options): Process
{
    $process = new Process(
        [PHP_BINARY, 'vendor/pestphp/pest/bin/pest', ...$options, '--colors=never'],
        dirname(__DIR__),
        ['PARATEST' => false, 'TEST_TOKEN' => false, 'UNIQUE_TEST_TOKEN' => false],
    );

    $process->run();

    return $process;
}

it('runs a passing snippet end-to-end', function () {
    $process = runPestWithAgent('--agent=expect(1 + 1)->toBe(2);');

    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toContain('1 passed');
});

it('reports a failing snippet end-to-end', function () {
    $process = runPestWithAgent('--agent=expect(true)->toBeFalse();');

    expect($process->getExitCode())->not->toBe(0)
        ->and($process->getOutput())->toContain('1 failed');
});

it('runs multiple snippets end-to-end', function () {
    $process = runPestWithAgent('--agent=expect(1)->toBe(1);', '--agent=expect(2)->toBe(2);');

    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toContain('2 passed');
});
