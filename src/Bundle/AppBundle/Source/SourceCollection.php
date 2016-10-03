<?php

namespace Amoscato\Bundle\AppBundle\Source;

use ArrayObject;
use InvalidArgumentException;

class SourceCollection extends ArrayObject
{
    /** @var string */
    private $interfaceName;

    /**
     * @param string $interfaceName
     * @param array $input optional
     * @param int $flags optional
     * @param string $iterator_class optional
     */
    public function __construct($interfaceName, $input = null, $flags = 0, $iterator_class = 'ArrayIterator')
    {
        $this->interfaceName = $interfaceName;

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
     * @param object $source
     * @return string
     */
    private function getKey($source)
    {
        if (!is_subclass_of($source, $this->interfaceName)) {
            throw new InvalidArgumentException("Source must implement {$this->interfaceName}");
        }

        return $source->getType();
    }
}
