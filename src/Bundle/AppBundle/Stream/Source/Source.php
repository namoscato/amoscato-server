<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

use Amoscato\Bundle\AppBundle\Stream\Query\PhotoStatementProvider;
use Amoscato\Bundle\IntegrationBundle\Client\Client;
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
     * @param int $page optional
     * @return array
     */
    abstract protected function extract($perPage, $page = 1);

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
        /** @var \Amoscato\Console\ConsoleOutput $output */

        $count = 0;
        $page = 1;
        $previousCount = 0;
        $values = [];

        $latestSourceId = $this->getLatestSourceId();

        do {
            $items = $this->extract($this->perPage, $page++);

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

                $count++;
            }

            if ($previousCount === $count) { // Prevent infinite loop
                break;
            }

            $previousCount = $count;
        } while ($count < self::LIMIT);

        $output->writeln("Loading {$count} " . $this->getType() . " items");

        if ($count === 0) {
            return true;
        }

        $statement = $this
            ->getPhotoStatementProvider()
            ->insertRows($count);
        
        return $statement->execute($values);
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
}
