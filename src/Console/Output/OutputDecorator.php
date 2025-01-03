<?php

declare(strict_types=1);

namespace Amoscato\Console\Output;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Adds additional functionality to {@see OutputInterface}.
 *
 * @method static OutputDecorator create(OutputInterface $output)
 */
class OutputDecorator extends AbstractOutputDecorator
{
    public function writeVerbosity(string|iterable $messages, int $verbosity = self::VERBOSITY_NORMAL, int $options = self::OUTPUT_NORMAL): void
    {
        if ($verbosity <= $this->getVerbosity()) {
            $this->writeln($messages, $options);
        }
    }

    public function writeVerbose(string|iterable $messages, int $options = self::OUTPUT_NORMAL): void
    {
        $this->writeVerbosity($messages, self::VERBOSITY_VERBOSE, $options);
    }

    public function writeVeryVerbose(string|iterable $messages, int $options = self::OUTPUT_NORMAL): void
    {
        $this->writeVerbosity($messages, self::VERBOSITY_VERY_VERBOSE, $options);
    }

    public function writeDebug(string|iterable $messages, int $options = self::OUTPUT_NORMAL): void
    {
        $this->writeVerbosity($messages, self::VERBOSITY_DEBUG, $options);
    }
}
