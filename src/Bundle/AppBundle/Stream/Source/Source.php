<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

use Amoscato\Bundle\AppBundle\Stream\Query\StreamStatementProvider;
use Amoscato\Bundle\IntegrationBundle\Client\Client;
use Amoscato\Console\Helper\PageIterator;
use Amoscato\Database\PDOFactory;
use PDO;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Source implements SourceInterface
{
    const LIMIT = 100;

    /** @var int */
    protected $perPage = self::LIMIT;

    /** @var PDOFactory */
    private $databaseFactory;

    /** @var StreamStatementProvider */
    protected $statementProvider;

    /** @var Client */
    protected $client;

    /** @var string */
    protected $type;

    /** @var int */
    protected $weight = 1;

    /**
     * @param PDOFactory $databaseFactory
     * @param Client $client
     */
    public function __construct(PDOFactory $databaseFactory, Client $client)
    {
        $this->databaseFactory = $databaseFactory;
        $this->client = $client;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $weight
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
    }

    /**
     * @return int
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param int $perPage
     * @param PageIterator $iterator
     * @return array
     */
    abstract protected function extract($perPage, PageIterator $iterator);

    /**
     * @param object $item
     * @return array
     */
    abstract protected function transform($item);

    /**
     * @param OutputInterface $output
     * @return bool
     */
    public function load(OutputInterface $output)
    {
        /** @var \Amoscato\Console\Output\ConsoleOutput $output */

        $iterator = new PageIterator(self::LIMIT);
        $values = [];

        $latestSourceId = $this->getLatestSourceId();

        while ($iterator->valid()) {
            $items = $this->extract($this->perPage, $iterator);

            foreach ($items as $item) {
                $sourceId = $this->getSourceId($item);

                if ($latestSourceId === $sourceId) { // Break if item is already in database
                    $output->writeDebug("Item {$latestSourceId} is already in the database");
                    break 2;
                }

                $transformedItem = $this->transform($item);

                if ($transformedItem === false) { // Skip select items
                    continue;
                }

                $values = array_merge(
                    [
                        $this->getType(),
                        $sourceId
                    ],
                    $transformedItem,
                    $values
                );

                $output->writeVerbose("Transforming " . $this->getType() . " item: {$values[2]}");

                $iterator->incrementCount();
            }

            $iterator->next();
        }

        return $this->insertValues($output, $iterator->getCount(), $values);
    }

    /**
     * @return string|null
     */
    protected function getLatestSourceId()
    {
        $statement = $this
            ->getStreamStatementProvider()
            ->selectLatestSourceId($this->getType());

        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return null;
        }

        return $result['source_id'];
    }

    /**
     * @param object $item
     * @return string
     */
    protected function getSourceId($item)
    {
        return (string) $item->id;
    }

    /**
     * @return StreamStatementProvider
     */
    public function getStreamStatementProvider()
    {
        if (!isset($this->statementProvider)) {
            $this->statementProvider = new StreamStatementProvider($this->databaseFactory->getInstance());
        }

        return $this->statementProvider;
    }

    /**
     * @param OutputInterface $output
     * @param int $count
     * @param array $values
     * @return bool
     */
    protected function insertValues(OutputInterface $output, $count, array $values)
    {
        /** @var \Amoscato\Console\Output\ConsoleOutput $output */

        $output->writeln("Loading {$count} " . $this->getType() . " items");

        if ($count === 0) {
            return true;
        }

        $statement = $this
            ->getStreamStatementProvider()
            ->insertRows($count);

        $result = $statement->execute($values);

        if ($result === false) {
            $output->writeln("Error loading " . $this->getType() . " items");
            $output->writeDebug(var_export($statement->errorInfo(), true));
            return false;
        }

        return true;
    }
}
