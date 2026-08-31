<?php

use Native\Mobile\Edge\TailwindParser;

/**
 * Guards config/native-ui.php theme tokens: every color value must resolve
 * through TailwindParser (Tailwind name, hex, or opacity-modified variant)
 * to the wire-format hex the native ColorParsers expect.
 */
it('resolves every theme color token to wire-format hex', function (string $mode) {
    $tokens = config("native-ui.theme.{$mode}");

    expect($tokens)->toBeArray()->not->toBeEmpty();

    foreach ($tokens as $token => $value) {
        $resolved = TailwindParser::resolveColorValue($value);

        expect($resolved)
            ->not->toBeNull("Theme token [{$mode}.{$token}] value [{$value}] did not resolve")
            ->toMatch('/^#([0-9A-Fa-f]{6}|[0-9A-Fa-f]{8})$/');
    }
})->with(['light', 'dark']);
