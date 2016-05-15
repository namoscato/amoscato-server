<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

use Amoscato\Bundle\AppBundle\Stream\Query\PhotoStatementProvider;
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

    /** @var PhotoStatementProvider */
    protected $statementProvider;

    /** @var Client */
    protected $client;

    /** @var string */
    protected $type;

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

                $output->writeVerbose("Transforming " . $this->getType() . " item: {$values[5]}");

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
            ->getPhotoStatementProvider()
            ->selectLatestSourceId();

        $statement->bindValue(
            ':type',
            $this->getType()
        );

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
     * @return PhotoStatementProvider
     */
    public function getPhotoStatementProvider()
    {
        if (isset($this->statementProvider)) {
            return $this->statementProvider;
        }

        return new PhotoStatementProvider(
            $this->databaseFactory->getInstance()
        );
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
            ->getPhotoStatementProvider()
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
