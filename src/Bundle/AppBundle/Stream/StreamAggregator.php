<?php

namespace Amoscato\Bundle\AppBundle\Stream;

use Amoscato\Bundle\AppBundle\Stream\Query\StreamStatementProvider;
use Amoscato\Bundle\AppBundle\Stream\Source\StreamSourceInterface;
use Amoscato\Database\PDOFactory;

class StreamAggregator
{
    const DEFAULT_SIZE = 1000.0;

    /** @var PDOFactory */
    private $databaseFactory;

    /** @var StreamSourceInterface[] */
    private $streamSources;

    /**
     * @param PDOFactory $pdoFactory
     * @param \Traversable $streamSources
     */
    public function __construct(PDOFactory $pdoFactory, \Traversable $streamSources)
    {
        $this->databaseFactory = $pdoFactory;
        $this->streamSources = $streamSources;
    }

    /**
     * @param float $size optional
     * @return array
     */
    public function aggregate($size = self::DEFAULT_SIZE)
    {
        $streamStatementProvider = $this->getStreamStatementProvider();
        $typeResults = [];
        $weightedTypeHash = self::getWeightedTypeHash($this->streamSources);

        foreach ($this->streamSources as $source) {
            $statement = $streamStatementProvider->selectStreamRows(
                $source->getType(),
                self::getSourceLimit($weightedTypeHash, $size, $source)
            );

            $statement->execute();

            $typeResults[$source->getType()] = $statement->fetchAll(\PDO::FETCH_ASSOC);
        }

        $result = [];

        for ($i = 0; $i < $size; $i++) {
            $randomIndex = mt_rand(0, count($weightedTypeHash) - 1);

            if ($item = array_shift($typeResults[$weightedTypeHash[$randomIndex]])) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * @return StreamStatementProvider
     */
    public function getStreamStatementProvider()
    {
        return new StreamStatementProvider($this->databaseFactory->getInstance());
    }

    /**
     * Returns the weighted type hash for the specified set of sources
     * @param StreamSourceInterface[] $sources
     * @return string[]
     */
    public static function getWeightedTypeHash($sources)
    {
        $weightedTypeHash = [];

        foreach ($sources as $source) {
            for ($i = 0; $i < $source->getWeight(); $i++) {
                $weightedTypeHash[] = $source->getType();
            }
        }

        return $weightedTypeHash;
    }

    /**
     * Returns the limit for the specified source
     * @param string[] $weightedTypeHash
     * @param int $size
     * @param StreamSourceInterface $source
     * @return int
     */
    public static function getSourceLimit(array &$weightedTypeHash, $size, StreamSourceInterface $source)
    {
        return ceil($size / count($weightedTypeHash) * $source->getWeight());
    }
}
