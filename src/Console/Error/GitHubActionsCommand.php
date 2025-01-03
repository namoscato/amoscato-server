<?php

declare(strict_types=1);

namespace Amoscato\Console\Error;

class GitHubActionsCommand implements \Stringable
{
    private const string DELIMITER = '::';

    public function __construct(
        private readonly string $command,
        private readonly string $message,
    ) {
    }

    public function __toString(): string
    {
        return sprintf(
            '%s%s%s%s',
            self::DELIMITER,
            $this->command,
            self::DELIMITER,
            $this->getEscapedMessage()
        );
    }

    /**
     * @see https://github.com/actions/toolkit/blob/adb9c4a7f4451235d770bf863019d24fe4d3fe2f/packages/core/src/command.ts#L80-L85
     */
    private function getEscapedMessage(): string
    {
        return str_replace(
            ['%', "\r", "\n"],
            ['%25', '%0D', '%0A'],
            $this->message
        );
    }
}
