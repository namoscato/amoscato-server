<?php

namespace Amoscato\Bundle\AppBundle\Source;

interface SourceCollectionAwareInterface
{
    /**
     * @param SourceCollection $sourceCollection
     */
    public function setSourceCollection(SourceCollection $sourceCollection);
}
