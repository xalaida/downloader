<?php

namespace Nevadskiy\Downloader\Tests\Fake;

use Psr\Log\AbstractLogger;

class FakeLogger extends AbstractLogger
{
    public $records = [];

    public function log($level, $message, array $context = [])
    {
        $this->records[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];
    }

    public function records(): array
    {
        return $this->records;
    }

    public function hasMessage(string $message, string $level = null): bool
    {
        return $this->hasRecordThatPasses(function (array $record) use ($message, $level) {
            if ($message !== $record['message']) {
                return false;
            }

            if ($level && $level !== $record['level']) {
                return false;
            }

            return true;
        });
    }

    public function hasRecordThatPasses(callable $predicate): bool
    {
        foreach ($this->records as $record) {
            if ($predicate($record)) {
                return true;
            }
        }

        return false;
    }
}
