<?php

declare(strict_types=1);

namespace Amoscato\Source;

interface SourceInterface
{
    /**
     * @return string
     */
    public function getType(): string;
}
