<?php

use App\Services\SearchReplaceService;

it('applies case-insensitive search and replace rules', function (): void {
    $service = new SearchReplaceService;

    $rules = [
        ['find' => 'Foo', 'replace' => 'Bar'],
        ['find' => 'acme', 'replace' => 'emca'],
    ];

    $result = $service->apply($rules, 'FOO en aCme en acme.');

    expect($result)->toBe('Bar en emca en emca.');
});
