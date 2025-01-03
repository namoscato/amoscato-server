<?php

declare(strict_types=1);

namespace Amoscato\Console\Error;

interface ErrorOutputInterface
{
    public function writeln(string $message): void;
}
