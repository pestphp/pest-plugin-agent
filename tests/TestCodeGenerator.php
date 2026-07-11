<?php

declare(strict_types=1);

use Pest\Agent\TestCodeGenerator;

it('generates a test without uses', function () {
    $generator = new TestCodeGenerator;

    $result = $generator->generate('expect(true)->toBeTrue();', []);

    expect($result)->toBe(<<<'PHP'
    <?php

    it('verify', function () {
        expect(true)->toBeTrue();
    });

    PHP);
});

it('generates a test with a single use', function () {
    $generator = new TestCodeGenerator;

    $result = $generator->generate('expect(true)->toBeTrue();', ['App\Models\User']);

    expect($result)->toBe(<<<'PHP'
    <?php

    pest()->uses(\App\Models\User::class);

    it('verify', function () {
        expect(true)->toBeTrue();
    });

    PHP);
});

it('generates a test with multiple uses', function () {
    $generator = new TestCodeGenerator;

    $result = $generator->generate('expect(true)->toBeTrue();', ['App\Models\User', 'Tests\TestCase']);

    expect($result)->toBe(<<<'PHP'
    <?php

    pest()->uses(\App\Models\User::class, \Tests\TestCase::class);

    it('verify', function () {
        expect(true)->toBeTrue();
    });

    PHP);
});

it('generates a test with multiline code', function () {
    $generator = new TestCodeGenerator;

    $result = $generator->generate("\$user = User::factory()->create();\n    expect(\$user)->toBeInstanceOf(User::class);", []);

    expect($result)->toBe(<<<'PHP'
    <?php

    it('verify', function () {
        $user = User::factory()->create();
        expect($user)->toBeInstanceOf(User::class);
    });

    PHP);
});

it('treats an empty uses array the same as no uses', function () {
    $generator = new TestCodeGenerator;

    $withEmpty = $generator->generate('expect(1)->toBe(1);', []);
    $withoutUses = $generator->generate('expect(1)->toBe(1);', []);

    expect($withEmpty)->toBe($withoutUses);
});

it('generates a test with a namespace', function () {
    $generator = new TestCodeGenerator;

    $result = $generator->generate('expect(true)->toBeTrue();', [], 'P\Tests\Feature');

    expect($result)->toBe(<<<'PHP'
    <?php

    it('verify', function () {
        expect(true)->toBeTrue();
    });

    \Pest\TestSuite::getInstance()->tests->get(__FILE__)->namespace = 'P\Tests\Feature';

    PHP);
});

it('generates a test with uses and a namespace', function () {
    $generator = new TestCodeGenerator;

    $result = $generator->generate('expect(true)->toBeTrue();', ['App\Models\User'], 'P\Tests\Feature');

    expect($result)->toBe(<<<'PHP'
    <?php

    pest()->uses(\App\Models\User::class);

    it('verify', function () {
        expect(true)->toBeTrue();
    });

    \Pest\TestSuite::getInstance()->tests->get(__FILE__)->namespace = 'P\Tests\Feature';

    PHP);
});
