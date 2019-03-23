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
    /**
     * @param string|string[] $messages
     * @param int $verbosity optional
     * @param int $options optional
     */
    public function writeVerbosity($messages, $verbosity = self::VERBOSITY_NORMAL, $options = self::OUTPUT_NORMAL): void
    {
        if ($verbosity <= $this->getVerbosity()) {
            $this->writeln($messages, $options);
        }
    }

    /**
     * @param string|string[] $messages
     * @param int $options optional
     */
    public function writeVerbose($messages, $options = self::OUTPUT_NORMAL): void
    {
        $this->writeVerbosity($messages, self::VERBOSITY_VERBOSE, $options);
    }

    /**
     * @param string|string[] $messages
     * @param int $options optional
     */
    public function writeVeryVerbose($messages, $options = self::OUTPUT_NORMAL): void
    {
        $this->writeVerbosity($messages, self::VERBOSITY_VERY_VERBOSE, $options);
    }

    /**
     * @param string|string[] $messages
     * @param int $options optional
     */
    public function writeDebug($messages, $options = self::OUTPUT_NORMAL): void
    {
        $this->writeVerbosity($messages, self::VERBOSITY_DEBUG, $options);
    }
}
