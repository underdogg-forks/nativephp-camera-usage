<?php

use App\NativeComponents\RefreshableDemo;
use Native\Mobile\Testing\Native;

it('seeds six cards on mount', function () {
    $screen = Native::test(RefreshableDemo::class)
        ->assertSet('refreshCount', 0)
        ->assertSee('Pull down to refresh ↓');

    expect($screen->get('cards'))->toHaveCount(6);
});

it('prepends a card on each refresh', function () {
    $screen = Native::test(RefreshableDemo::class)
        ->call('refresh')
        ->assertSet('refreshCount', 1);

    $cards = $screen->get('cards');

    expect($cards)->toHaveCount(7)
        ->and($cards[0]['title'])->toStartWith('Refreshed at')
        ->and($cards[0]['subtitle'])->toBe('Pulled #1');
});

it('caps the card list at twelve entries', function () {
    $screen = Native::test(RefreshableDemo::class);

    foreach (range(1, 8) as $i) {
        $screen->call('refresh');
    }

    $screen->assertSet('refreshCount', 8);

    expect($screen->get('cards'))->toHaveCount(12);
});
