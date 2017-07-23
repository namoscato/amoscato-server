<?php

namespace Amoscato\Bundle\AppBundle\Stream;

use Amoscato\Bundle\AppBundle\Source\SourceCollectionAwareInterface;
use Amoscato\Bundle\AppBundle\Stream\Query\StreamStatementProvider;
use Amoscato\Bundle\AppBundle\Source\SourceCollection;
use Amoscato\Database\PDOFactory;
use PDO;

class StreamAggregator implements SourceCollectionAwareInterface
{
    /** @var PDOFactory */
    private $databaseFactory;

    /** @var \Amoscato\Bundle\AppBundle\Stream\Source\SourceInterface[] */
    private $sourceCollection;

    /**
     * @param PDOFactory $pdoFactory
     */
    public function __construct(PDOFactory $pdoFactory)
    {
        $this->databaseFactory = $pdoFactory;
    }

    /**
     * @param float $size optional
     * @return array
     */
    public function aggregate($size = 1000.0)
    {
        $weightedTypeHash = [];
        $weightedTypeHashCount = 0;

        foreach ($this->sourceCollection as $source) {
            for ($i = 0; $i < $source->getWeight(); $i++) {
                $weightedTypeHash[] = $source->getType();
                $weightedTypeHashCount++;
            }
        }

        $streamStatementProvider = $this->getStreamStatementProvider();
        $typeResults = [];

        foreach ($this->sourceCollection as $source) {
            $statement = $streamStatementProvider->selectStreamRows(
                $source->getType(),
                ceil($size / $weightedTypeHashCount * $source->getWeight())
            );

            $statement->execute();

            $typeResults[$source->getType()] = $statement->fetchAll(PDO::FETCH_ASSOC);
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

    /**
     * @param SourceCollection $sourceCollection
     */
    public function setSourceCollection(SourceCollection $sourceCollection)
    {
        $this->sourceCollection = $sourceCollection;
    }
}
