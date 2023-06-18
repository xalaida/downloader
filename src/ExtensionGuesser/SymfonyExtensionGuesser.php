<?php

namespace Nevadskiy\Downloader\ExtensionGuesser;

use Symfony\Component\Mime\MimeTypes;

class SymfonyExtensionGuesser implements ExtensionGuesser
{
    /**
     * @inheritdoc
     */
    public function getExtension(string $mimeType): ?string
    {
        return MimeTypes::getDefault()->getExtensions($mimeType)[0] ?? null;
    }
}
