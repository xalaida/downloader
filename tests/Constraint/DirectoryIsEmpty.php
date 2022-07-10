<?php

namespace Nevadskiy\Downloader\Tests\Constraint;

use FilesystemIterator;
use PHPUnit\Framework\Constraint\Constraint;
use function sprintf;

class DirectoryIsEmpty extends Constraint
{
    /**
     * @inheritdoc
     */
    protected function matches($other): bool
    {
        $iterator = new FilesystemIterator($other);

        return ! $iterator->valid();
    }

    /**
     * @inheritdoc
     */
    protected function failureDescription($other): string
    {
        return sprintf('directory "%s" is empty', $other);
    }

    /**
     * @inheritdoc
     */
    public function toString(): string
    {
        return 'directory is empty';
    }
}
