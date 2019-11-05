<?php

declare(strict_types=1);

namespace Amoscato\Source;

interface SourceInterface
{
    public function getType(): string;
}
