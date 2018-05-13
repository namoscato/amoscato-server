<?php

namespace Amoscato\Bundle\AppBundle\Stream;

use Amoscato\Bundle\AppBundle\Stream\Query\StreamStatementProvider;
use Amoscato\Database\PDOFactory;

class StreamAggregator
{
    /** @var PDOFactory */
    private $databaseFactory;

    /** @var \Amoscato\Bundle\AppBundle\Stream\Source\StreamSourceInterface[] */
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
    public function aggregate($size = 1000.0)
    {
        $weightedTypeHash = [];
        $weightedTypeHashCount = 0;

        foreach ($this->streamSources as $source) {
            for ($i = 0; $i < $source->getWeight(); $i++) {
                $weightedTypeHash[] = $source->getType();
                $weightedTypeHashCount++;
            }
        }

        $streamStatementProvider = $this->getStreamStatementProvider();
        $typeResults = [];

        foreach ($this->streamSources as $source) {
            $statement = $streamStatementProvider->selectStreamRows(
                $source->getType(),
                ceil($size / $weightedTypeHashCount * $source->getWeight())
            );

            $statement->execute();

            $typeResults[$source->getType()] = $statement->fetchAll(\PDO::FETCH_ASSOC);
        }

        $result = [];

        for ($i = 0; $i < $size; $i++) {
            $randomIndex = rand(0, $weightedTypeHashCount - 1);

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
}
