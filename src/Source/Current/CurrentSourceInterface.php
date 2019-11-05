<?php

declare(strict_types=1);

namespace Amoscato\Source\Current;

use Amoscato\Source\SourceInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface CurrentSourceInterface extends SourceInterface
{
    public function load(OutputInterface $output): ?array;
}
