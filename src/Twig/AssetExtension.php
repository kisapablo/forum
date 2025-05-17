<?php

namespace Blog\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AssetExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('asset_url', [$this, 'getAssetUrl'])
        ];
    }

    public function getAssetUrl(string $path): string
    {
        return 'http://localhost:8081/'.$path;
    }
}
