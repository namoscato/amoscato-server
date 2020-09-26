<?php

declare(strict_types=1);

namespace Amoscato\Source\Stream;

use Amoscato\Source\SourceInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface StreamSourceInterface extends SourceInterface
{
    public function getWeight(): int;

    public function load(OutputInterface $output, int $limit = 1): bool;
}
