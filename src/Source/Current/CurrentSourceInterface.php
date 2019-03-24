<?php

declare(strict_types=1);

namespace Amoscato\Source\Current;

use Amoscato\Source\SourceInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface CurrentSourceInterface extends SourceInterface
{
    /**
     * @param OutputInterface $output
     *
     * @return array|null
     */
    public function load(OutputInterface $output): ?array;
}
