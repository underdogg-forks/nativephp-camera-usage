<?php

use App\NativeComponents\SyncUpNative\SyncUpNativeChat;
use Native\Mobile\Testing\Native;

it('seeds the thread from the route param and hides the tab bar', function () {
    $screen = Native::visit('/syncup-native/chat/1')
        ->assertScreen(SyncUpNativeChat::class)
        ->assertSet('seeded', true)
        ->assertTabBarHidden();

    expect($screen->get('messages'))->not->toBeEmpty();
});

it('sends a trimmed draft and clears it', function () {
    $screen = Native::visit('/syncup-native/chat/1');
    $before = count($screen->get('messages'));

    $screen->call('setDraft', '  Hello there  ')
        ->call('send')
        ->assertSet('draft', '');

    $messages = $screen->get('messages');
    $last = end($messages);

    expect($messages)->toHaveCount($before + 1)
        ->and($last['text'])->toBe('Hello there')
        ->and($last['fromMe'])->toBeTrue()
        ->and($last['status'])->toBe('sent');
});

it('does not send an empty draft', function () {
    $screen = Native::visit('/syncup-native/chat/1');
    $before = count($screen->get('messages'));

    $screen->call('setDraft', '   ')->call('send');

    expect($screen->get('messages'))->toHaveCount($before);
});

it('clears the history only after confirmation', function () {
    $screen = Native::visit('/syncup-native/chat/1')
        ->call('askClearHistory')
        ->assertSet('showClearConfirm', true)
        ->assertSet('showMoreActions', false)
        ->call('cancelClearHistory')
        ->assertSet('showClearConfirm', false);

    expect($screen->get('messages'))->not->toBeEmpty();

    $screen->call('askClearHistory')
        ->call('confirmClearHistory')
        ->assertSet('showClearConfirm', false);

    expect($screen->get('messages'))->toBeEmpty();
});

it('toggles mute and closes the more-actions sheet', function () {
    Native::visit('/syncup-native/chat/1')
        ->call('openMenu')
        ->assertSet('showMoreActions', true)
        ->call('toggleMute')
        ->assertSet('isMuted', true)
        ->assertSet('showMoreActions', false)
        ->call('toggleMute')
        ->assertSet('isMuted', false);
});

it('shows a toast when starting a call', function () {
    Native::visit('/syncup-native/chat/1')
        ->call('startCall')
        ->assertNativeCalled('Dialog.Toast');
});
