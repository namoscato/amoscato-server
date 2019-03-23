<?php

declare(strict_types=1);

namespace Amoscato\Source\Stream;

use Amoscato\Source\SourceInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface StreamSourceInterface extends SourceInterface
{
    /**
     * @return int
     */
    public function getWeight(): int;

    /**
     * @param OutputInterface $output
     * @param int $limit
     *
     * @return bool
     */
    public function load(OutputInterface $output, $limit = 1): bool;
}
