<?php

declare(strict_types=1);

namespace Amoscato\Console\Output;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractOutputDecorator implements OutputInterface
{
    protected OutputInterface $output;

    public static function create(OutputInterface $output): self
    {
        if ($output instanceof static) {
            return $output;
        }

        return new static($output);
    }

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function write(string|iterable $messages, bool $newline = false, int $options = 0): void
    {
        $this->output->write($messages, $newline, $options);
    }

    public function writeln(string|iterable $messages, int $options = 0): void
    {
        $this->output->writeln($messages, $options);
    }

    public function setVerbosity(int $level): void
    {
        $this->output->setVerbosity($level);
    }

    public function getVerbosity(): int
    {
        return $this->output->getVerbosity();
    }

    public function isQuiet(): bool
    {
        return $this->output->isQuiet();
    }

    public function isSilent(): bool
    {
        return $this->output->isSilent();
    }

    public function isVerbose(): bool
    {
        return $this->output->isVerbose();
    }

    public function isVeryVerbose(): bool
    {
        return $this->output->isVeryVerbose();
    }

    public function isDebug(): bool
    {
        return $this->output->isDebug();
    }

    public function setDecorated(bool $decorated): void
    {
        $this->output->setDecorated($decorated);
    }

    public function isDecorated(): bool
    {
        return $this->output->isDecorated();
    }

    public function setFormatter(OutputFormatterInterface $formatter): void
    {
        $this->output->setFormatter($formatter);
    }

    public function getFormatter(): OutputFormatterInterface
    {
        return $this->output->getFormatter();
    }
}
