<?php

use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Elements\NativeRootStack;
use Native\Mobile\Edge\Layouts\Builders\NavBar;
use Native\Mobile\Edge\Layouts\Builders\NavBarOptions;

/**
 * Resolve a NavBar's declarative config the same way the framework does at
 * publish time: NavBarOptions → NavBar → root attrs → NativeRootStack props.
 *
 * @return array<string, mixed>
 */
function resolveStackProps(NavBar $bar): array
{
    $stack = NativeRootStack::make();
    $stack->applyAttributes($bar->toRootProps());

    $resolve = new ReflectionMethod($stack, 'resolveProps');
    $resolve->setAccessible(true);

    return $resolve->invoke($stack, new CallbackRegistry);
}

it('flows scrollBehavior from NavBarOptions to the scroll_behavior wire prop', function () {
    $bar = NavBar::make()->mergeOptions(
        NavBarOptions::make()
            ->displayMode('large')
            ->scrollBehavior('collapse')
    );

    expect($bar->toRootProps())->toHaveKey('scrollBehavior', 'collapse')
        ->and(resolveStackProps($bar))->toHaveKey('scroll_behavior', 'collapse');
});

it('accepts each supported scroll behavior', function (string $mode) {
    $bar = NavBar::make()->scrollBehavior($mode);

    expect(resolveStackProps($bar))->toHaveKey('scroll_behavior', $mode);
})->with(['collapse', 'pinned', 'enterAlways']);

it('omits the scroll_behavior prop when never configured', function () {
    $bar = NavBar::make()->displayMode('large');

    expect(resolveStackProps($bar))->not->toHaveKey('scroll_behavior');
});

it('lets a screen NavBarOptions override the layout default', function () {
    $bar = NavBar::make()
        ->scrollBehavior('pinned')
        ->mergeOptions(NavBarOptions::make()->scrollBehavior('enterAlways'));

    expect(resolveStackProps($bar))->toHaveKey('scroll_behavior', 'enterAlways');
});
