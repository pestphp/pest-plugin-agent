<?php

declare(strict_types=1);

use Pest\Agent\TestFileGenerator;

beforeEach(function () {
    $this->generator = new TestFileGenerator;
});

afterEach(function () {
    $this->generator->cleanup();
});

it('creates a temporary file with generated code', function () {
    $path = $this->generator->generate('expect(true)->toBeTrue();', []);

    expect($path)->toBeFile()
        ->and(file_get_contents($path))->toBe(<<<'PHP'
        <?php

        it('verify', function () {
            expect(true)->toBeTrue();
        });

        PHP);
});

it('creates a file with a unique name per run', function () {
    $path1 = $this->generator->generate('expect(1)->toBe(1);', []);
    $path2 = $this->generator->generate('expect(2)->toBe(2);', []);

    expect($path1)->not->toBe($path2);
});

it('removes every generated file on cleanup', function () {
    $path1 = $this->generator->generate('expect(1)->toBe(1);', []);
    $path2 = $this->generator->generate('expect(2)->toBe(2);', []);

    $this->generator->cleanup();

    expect($path1)->not->toBeFile()
        ->and($path2)->not->toBeFile();
});

it('creates the file in the system temp directory', function () {
    $path = $this->generator->generate('expect(true)->toBeTrue();', []);

    expect($path)->toStartWith(sys_get_temp_dir());
});

it('creates a file with a .php extension', function () {
    $path = $this->generator->generate('expect(true)->toBeTrue();', []);

    expect($path)->toEndWith('.php');
});

it('passes uses to the generated code', function () {
    $path = $this->generator->generate('expect(true)->toBeTrue();', ['App\Models\User']);

    expect(file_get_contents($path))->toBe(<<<'PHP'
    <?php

    pest()->uses(\App\Models\User::class);

    it('verify', function () {
        expect(true)->toBeTrue();
    });

    PHP);
});

it('passes namespace to the generated code', function () {
    $path = $this->generator->generate('expect(true)->toBeTrue();', [], 'P\Tests\Feature');

    expect(file_get_contents($path))->toBe(<<<'PHP'
    <?php

    it('verify', function () {
        expect(true)->toBeTrue();
    });

    if (($testCaseFactory = \Pest\TestSuite::getInstance()->tests->get(__FILE__)) !== null) {
        $testCaseFactory->namespace = 'P\\Tests\\Feature';
    }

    PHP);
});

it('removes the file on cleanup', function () {
    $path = $this->generator->generate('expect(true)->toBeTrue();', []);

    expect($path)->toBeFile();

    $this->generator->cleanup();

    expect($path)->not->toBeFile();
});

it('handles cleanup when no file was generated', function () {
    expect(fn () => $this->generator->cleanup())->not->toThrow(Exception::class);
});

it('handles cleanup when file was already deleted', function () {
    $path = $this->generator->generate('expect(true)->toBeTrue();', []);
    @unlink($path);

    $this->generator->cleanup();

    expect($path)->not->toBeFile();
});
