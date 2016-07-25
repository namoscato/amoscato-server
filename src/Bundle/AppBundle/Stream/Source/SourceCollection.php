<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

use ArrayObject;
use InvalidArgumentException;

class SourceCollection extends ArrayObject
{
    /**
     * @param array $input optional
     * @param int $flags optional
     * @param string $iterator_class optional
     */
    public function __construct($input = null, $flags = 0, $iterator_class = 'ArrayIterator')
    {
        $normalizedInput = $input;

        if (is_array($input)) {
            $normalizedInput = [];

            foreach ($input as &$source) {
                $normalizedInput[$this->getKey($source)] = $source;
            }
        }

        parent::__construct($normalizedInput, $flags, $iterator_class);
    }

    /**
     * @param mixed $index
     * @param mixed $value
     * @throws InvalidArgumentException
     */
    public function offsetSet($index, $value)
    {
        parent::offsetSet($this->getKey($value), $value);
    }

    /**
     * @param SourceInterface $source
     * @return string
     */
    private function getKey($source)
    {
        if (!$source instanceof SourceInterface) {
            throw new InvalidArgumentException('Source must implement Amoscato\Bundle\AppBundle\Stream\Source\SourceInterface');
        }

        return $source->getType();
    }
}
