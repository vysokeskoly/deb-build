<?php declare(strict_types=1);

namespace VysokeSkoly\Build;

trait ComposerParserTrait
{
    protected function parseComposer(string $path = './composer.json'): array
    {
        return json_decode(file_get_contents($path), true);
    }
}
