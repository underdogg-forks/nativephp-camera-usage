<?php

use App\NativeComponents\EventChannelTest;
use Native\Mobile\Testing\Native;

it('receives text through the change binding and reports its length', function () {
    Native::test(EventChannelTest::class)
        ->input('onType', 'hello channel')
        ->assertSet('len', 13)
        ->assertSet('sample', 'hello channel');
});

it('handles payloads past the old 64KB frame cap', function () {
    $payload = str_repeat('a', 70_000);

    Native::test(EventChannelTest::class)
        ->call('onType', $payload)
        ->assertSet('len', 70_000)
        ->assertSet('sample', str_repeat('a', 48));
});

it('clears the stats', function () {
    Native::test(EventChannelTest::class)
        ->call('onType', 'something')
        ->press('clearStats')
        ->assertSet('len', 0)
        ->assertSet('sample', '');
});
