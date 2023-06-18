<?php

namespace Nevadskiy\Downloader\ExtensionGuesser;

interface ExtensionGuesser
{
    /**
     * Guess an extension by the given MIME type.
     */
    public function getExtension(string $mimeType): ?string;
}
