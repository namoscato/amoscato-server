<?php

declare(strict_types=1);

namespace Amoscato\Source\Stream;

use Amoscato\Source\SourceInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface StreamSourceInterface extends SourceInterface
{
    public function getWeight(): int;

    /**
     * @param int $limit
     */
    public function load(OutputInterface $output, $limit = 1): bool;
}
