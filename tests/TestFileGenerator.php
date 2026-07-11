<?php

declare(strict_types=1);

use Pest\Agent\TestFileGenerator;

it('creates a temporary file with generated code', function () {
    $generator = new TestFileGenerator;

    $path = $generator->generate('expect(true)->toBeTrue();', []);

    expect($path)->toBeFile()
        ->and(file_get_contents($path))->toBe(<<<'PHP'
        <?php

        it('verify', function () {
            expect(true)->toBeTrue();
        });

        PHP);

    $generator->cleanup();
});

it('creates a file with a unique name per run', function () {
    $generator = new TestFileGenerator;

    $path1 = $generator->generate('expect(1)->toBe(1);', []);
    $path2 = $generator->generate('expect(2)->toBe(2);', []);

    expect($path1)->not->toBe($path2);

    @unlink($path1);
    $generator->cleanup();
});

it('creates the file in the system temp directory', function () {
    $generator = new TestFileGenerator;

    $path = $generator->generate('expect(true)->toBeTrue();', []);

    expect($path)->toStartWith(sys_get_temp_dir());

    $generator->cleanup();
});

it('creates a file with a .php extension', function () {
    $generator = new TestFileGenerator;

    $path = $generator->generate('expect(true)->toBeTrue();', []);

    expect($path)->toEndWith('.php');

    $generator->cleanup();
});

it('passes uses to the generated code', function () {
    $generator = new TestFileGenerator;

    $path = $generator->generate('expect(true)->toBeTrue();', ['App\Models\User']);

    expect(file_get_contents($path))->toBe(<<<'PHP'
    <?php

    pest()->uses(\App\Models\User::class);

    it('verify', function () {
        expect(true)->toBeTrue();
    });

    PHP);

    $generator->cleanup();
});

it('passes namespace to the generated code', function () {
    $generator = new TestFileGenerator;

    $path = $generator->generate('expect(true)->toBeTrue();', [], 'P\Tests\Feature');

    expect(file_get_contents($path))->toBe(<<<'PHP'
    <?php

    it('verify', function () {
        expect(true)->toBeTrue();
    });

    \Pest\TestSuite::getInstance()->tests->get(__FILE__)->namespace = 'P\Tests\Feature';

    PHP);

    $generator->cleanup();
});

it('removes the file on cleanup', function () {
    $generator = new TestFileGenerator;

    $path = $generator->generate('expect(true)->toBeTrue();', []);

    expect($path)->toBeFile();

    $generator->cleanup();

    expect($path)->not->toBeFile();
});

it('handles cleanup when no file was generated', function () {
    $generator = new TestFileGenerator;

    $generator->cleanup();

    expect(true)->toBeTrue();
});

it('handles cleanup when file was already deleted', function () {
    $generator = new TestFileGenerator;

    $path = $generator->generate('expect(true)->toBeTrue();', []);
    @unlink($path);

    $generator->cleanup();

    expect($path)->not->toBeFile();
});
