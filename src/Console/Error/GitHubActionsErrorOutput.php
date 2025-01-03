<?php

declare(strict_types=1);

namespace Amoscato\Console\Error;

use Symfony\Component\Console\Output\OutputInterface;

readonly class GitHubActionsErrorOutput implements ErrorOutputInterface
{
    public function __construct(private OutputInterface $output)
    {
    }

    public function writeln(string $message): void
    {
        $command = new GitHubActionsCommand('error', $message);

        $this->output->writeln((string) $command);
    }
}
