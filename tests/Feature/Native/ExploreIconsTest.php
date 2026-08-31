<?php

use App\Icons\Android;
use App\Icons\Ios;
use App\NativeComponents\ExploreIcons;
use Native\Mobile\Testing\Native;

/** Matcher for an icon node rendering the given raw symbol name. */
function iconNode(string $symbol): callable
{
    return fn (array $n) => ($n['props']['name'] ?? null) === $symbol;
}

it('shows the SF Symbols catalog on iOS with layout chrome', function () {
    Native::visit('/explore/icons', platform: 'ios')
        ->assertSee('SF Symbols — '.count(Ios::cases()).' icons')
        ->assertDontSee('Material (filled)')
        ->assertNavTitle('Icons');
});

it('shows the Material catalog on Android', function () {
    Native::test(ExploreIcons::class, platform: 'android')
        ->assertSee('Material (filled) — '.count(Android::cases()).' icons')
        ->assertDontSee('SF Symbols');
});

it('falls back to SF Symbols when the platform is unknown', function () {
    Native::test(ExploreIcons::class)
        ->assertSee('SF Symbols');
});

it('publishes a virtual list window, not the whole catalog', function () {
    $cases = Ios::cases();
    $rowCount = (int) ceil(count($cases) / 3);

    Native::test(ExploreIcons::class, platform: 'ios')
        ->assertElement('virtual_list', function (array $node) use ($rowCount) {
            return ($node['props']['count'] ?? null) === $rowCount
                && ($node['props']['window_from'] ?? null) === 0
                && ($node['props']['window_to'] ?? null) === 79;
        })
        // First window (rows 0-79 → cases 0-239) renders the first icon
        // but not one far past the window.
        ->assertElement('icon', iconNode($cases[0]->value))
        ->assertMissingElement('icon', iconNode($cases[3000]->value));
});

it('re-materializes rows when native reports a window change', function () {
    $cases = Ios::cases();

    Native::test(ExploreIcons::class, platform: 'ios')
        ->call('setVirtualWindow', 500, 579)
        // Rows 500-579 hold cases[1500..1739]; the first window is gone.
        ->assertElement('icon', iconNode($cases[1500]->value))
        ->assertMissingElement('icon', iconNode($cases[0]->value));
});

it('opens the detail sheet when an icon is tapped', function () {
    $first = Ios::cases()[0];

    Native::test(ExploreIcons::class, platform: 'ios')
        ->assertElement('bottom_sheet', fn (array $n) => ($n['props']['visible'] ?? null) === false)
        ->press("showIcon('{$first->name}')")
        ->assertSet('selectedName', $first->name)
        ->assertElement('bottom_sheet', fn (array $n) => ($n['props']['visible'] ?? null) === true)
        ->assertSee("Ios::{$first->name}")
        ->assertSee($first->value);
});

it('closes the detail sheet on dismiss', function () {
    $first = Ios::cases()[0];

    Native::test(ExploreIcons::class, platform: 'ios')
        ->press("showIcon('{$first->name}')")
        ->dismissSheet('closeIconSheet')
        ->assertSet('selectedName', null)
        ->assertElement('bottom_sheet', fn (array $n) => ($n['props']['visible'] ?? null) === false);
});

it('narrows the catalog by search and resets the window', function () {
    Native::test(ExploreIcons::class, platform: 'ios')
        ->call('setVirtualWindow', 500, 579)
        ->call('findIcon', 'wifi')
        ->assertSet('virtualWindowFrom', 0)
        ->assertElement('icon', iconNode('wifi'))
        ->assertElement('icon', iconNode('wifi.slash'));
});

it('shows an empty state when nothing matches', function () {
    Native::test(ExploreIcons::class, platform: 'ios')
        ->call('findIcon', 'zzzznope')
        ->assertSee('No icons match "zzzznope"')
        ->assertMissingElement('virtual_list');
});

it('restores the full catalog when the query is cleared', function () {
    Native::test(ExploreIcons::class, platform: 'ios')
        ->call('findIcon', 'wifi')
        ->call('findIcon', '')
        ->assertSee('SF Symbols — '.count(Ios::cases()).' icons');
});
