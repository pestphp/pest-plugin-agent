<?php

declare(strict_types=1);

use Pest\Agent\Plugin;
use Pest\TestSuite;

it('leaves the arguments untouched when --agent is not given', function () {
    $plugin = new Plugin;

    $arguments = ['vendor/bin/pest', '--colors=always'];

    expect($plugin->handleArguments($arguments))->toBe($arguments);
});

it('replaces --agent with the path of a generated test file', function () {
    $plugin = new Plugin;

    $arguments = $plugin->handleArguments(['vendor/bin/pest', '--agent=expect(true)->toBeTrue();']);

    expect($arguments)->toHaveCount(2)
        ->and($arguments[0])->toBe('vendor/bin/pest')
        ->and($arguments[1])->toBeFile()
        ->and(file_get_contents($arguments[1]))->toContain('expect(true)->toBeTrue();');

    $plugin->terminate();
});

it('supports the space separated --agent value format', function () {
    $plugin = new Plugin;

    $arguments = $plugin->handleArguments(['vendor/bin/pest', '--agent', 'expect(true)->toBeTrue();']);

    expect($arguments)->toHaveCount(2)
        ->and($arguments[1])->toBeFile()
        ->and(file_get_contents($arguments[1]))->toContain('expect(true)->toBeTrue();');

    $plugin->terminate();
});

it('generates a test file per --agent argument', function () {
    $plugin = new Plugin;

    $arguments = $plugin->handleArguments([
        'vendor/bin/pest',
        '--agent=expect(1)->toBe(1);',
        '--agent=expect(2)->toBe(2);',
    ]);

    expect($arguments)->toHaveCount(3)
        ->and($arguments[1])->toBeFile()
        ->and($arguments[2])->toBeFile()
        ->and(file_get_contents($arguments[1]))->toContain('expect(1)->toBe(1);')
        ->and(file_get_contents($arguments[2]))->toContain('expect(2)->toBe(2);');

    $plugin->terminate();
});

it('preserves the other arguments and their order', function () {
    $plugin = new Plugin;

    $arguments = $plugin->handleArguments([
        'vendor/bin/pest',
        '--agent=expect(1)->toBe(1);',
        '--colors=always',
        '--bail',
    ]);

    expect($arguments)->toHaveCount(4)
        ->and($arguments[0])->toBe('vendor/bin/pest')
        ->and($arguments[1])->toBe('--colors=always')
        ->and($arguments[2])->toBe('--bail')
        ->and($arguments[3])->toBeFile();

    $plugin->terminate();
});

it('throws when --agent is given without a value', function () {
    $plugin = new Plugin;

    expect(fn () => $plugin->handleArguments(['vendor/bin/pest', '--agent']))
        ->toThrow(InvalidArgumentException::class);
});

it('throws when a trailing --agent has no value', function () {
    $plugin = new Plugin;

    expect(fn () => $plugin->handleArguments(['vendor/bin/pest', '--agent=expect(true)->toBeTrue();', '--agent']))
        ->toThrow(InvalidArgumentException::class);
});

it('throws when --agent is followed by another option instead of code', function () {
    $plugin = new Plugin;

    expect(fn () => $plugin->handleArguments(['vendor/bin/pest', '--agent', '--colors=always']))
        ->toThrow(InvalidArgumentException::class);
});

it('throws when the --agent code is empty', function () {
    $plugin = new Plugin;

    expect(fn () => $plugin->handleArguments(['vendor/bin/pest', '--agent=']))
        ->toThrow(InvalidArgumentException::class);
});

it('throws when the --agent code is only whitespace', function () {
    $plugin = new Plugin;

    expect(fn () => $plugin->handleArguments(['vendor/bin/pest', '--agent', '   ']))
        ->toThrow(InvalidArgumentException::class);
});

it('removes the generated test files on terminate', function () {
    $plugin = new Plugin;

    $arguments = $plugin->handleArguments([
        'vendor/bin/pest',
        '--agent=expect(1)->toBe(1);',
        '--agent=expect(2)->toBe(2);',
    ]);

    $plugin->terminate();

    expect($arguments[1])->not->toBeFile()
        ->and($arguments[2])->not->toBeFile();
});

it('handles terminate when no file was generated', function () {
    $plugin = new Plugin;

    expect(fn () => $plugin->terminate())->not->toThrow(Exception::class);
});

it('injects the resolved uses and namespace into the generated file', function () {
    $testSuite = TestSuite::getInstance();

    $testSuite->tests->use([stdClass::class], [], [
        $testSuite->rootPath.DIRECTORY_SEPARATOR.$testSuite->testPath.DIRECTORY_SEPARATOR.'Feature',
    ], []);

    $plugin = new Plugin;

    $arguments = $plugin->handleArguments(['vendor/bin/pest', '--agent=expect(true)->toBeTrue();']);

    expect(file_get_contents($arguments[1]))
        ->toContain('\stdClass::class')
        ->toContain('$testCaseFactory->namespace = '.var_export('P\Tests\Feature', true).';');

    $plugin->terminate();
});
