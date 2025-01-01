<?php

declare(strict_types=1);

namespace Amoscato\Source\Stream;

use Amoscato\Console\Helper\PageIterator;
use Amoscato\Console\Output\OutputDecorator;
use Amoscato\Database\PDOFactory;
use Amoscato\Integration\Client\Client;
use Amoscato\Source\AbstractSource;
use Amoscato\Source\Stream\Query\StreamStatementProvider;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractStreamSource extends AbstractSource implements StreamSourceInterface
{
    /** @var PDOFactory */
    private $databaseFactory;

    /** @var StreamStatementProvider */
    protected $statementProvider;

    /** @var int */
    protected $weight = 1;

    public function __construct(PDOFactory $databaseFactory, Client $client)
    {
        parent::__construct($client);

        $this->databaseFactory = $databaseFactory;
    }

    /**
     * Returns the maximum number of items per page.
     */
    abstract protected function getMaxPerPage(): int;

    /**
     * Extracts data for the specified page.
     *
     * @param int $perPage
     */
    abstract protected function extract($perPage, PageIterator $iterator): iterable;

    /**
     * @param object $item
     *
     * @return array|bool
     */
    abstract protected function transform($item);

    /**
     * @param int $weight
     */
    public function setWeight($weight): void
    {
        $this->weight = $weight;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function load(OutputInterface $output, int $limit = 1): bool
    {
        $output = OutputDecorator::create($output);

        $iterator = new PageIterator($limit);
        $perPage = $this->getPerPage($limit);
        $values = [];

        $latestSourceId = $this->getLatestSourceId();

        while ($iterator->valid()) {
            $items = $this->extract($perPage, $iterator);

            foreach ($items as $item) {
                $sourceId = $this->getSourceId($item);

                if ($latestSourceId === $sourceId) { // Break if item is already in database
                    $output->writeDebug("Item {$latestSourceId} is already in the database");
                    break 2;
                }

                $transformedItems = $this->transform($item);

                if (false === $transformedItems) { // Skip select items
                    continue;
                }

                if (!$transformedItems instanceof \ArrayObject) {
                    $transformedItems = [$transformedItems];
                }

                $count = 0;

                foreach ($transformedItems as $transformedItem) {
                    /* @noinspection SlowArrayOperationsInLoopInspection */
                    $values = array_merge(
                        [
                            $this->getType(),
                            $count > 0 ? "{$sourceId}_{$count}" : $sourceId,
                        ],
                        $transformedItem,
                        $values
                    );

                    $output->writeVerbose("Transforming {$this->getType()} item: {$values[2]}");

                    /* @noinspection DisconnectedForeachInstructionInspection */
                    $iterator->incrementCount();
                    ++$count;
                }
            }

            $iterator->next();
        }

        return $this->insertValues($output, $iterator->getCount(), $values);
    }

    protected function getLatestSourceId(): ?string
    {
        $statement = $this
            ->getStreamStatementProvider()
            ->selectLatestSourceId($this->getType());

        $statement->execute();

        $result = $statement->fetch(\PDO::FETCH_ASSOC);

        if (!$result) {
            return null;
        }

        return $result['source_id'];
    }

    /**
     * @param object $item
     */
    protected function getSourceId($item): string
    {
        return (string) $item->id;
    }

    public function getStreamStatementProvider(): StreamStatementProvider
    {
        if (null === $this->statementProvider) {
            $this->statementProvider = new StreamStatementProvider($this->databaseFactory->getInstance());
        }

        return $this->statementProvider;
    }

    /**
     * @param int $count
     */
    protected function insertValues(OutputDecorator $output, $count, array $values): bool
    {
        $output->writeln("Loading {$count} {$this->getType()} items");

        if (0 === $count) {
            return true;
        }

        $statement = $this
            ->getStreamStatementProvider()
            ->insertRows($count);

        $result = $statement->execute($values);

        if (false === $result) {
            $output->writeln("Error loading {$this->getType()} items");
            $output->writeDebug(var_export($statement->errorInfo(), true));

            return false;
        }

        return true;
    }

    /**
     * @param int $limit
     */
    protected function getPerPage($limit): int
    {
        $perPage = $this->getMaxPerPage();

        if ($limit < $perPage) {
            $perPage = $limit;
        }

        return $perPage;
    }
}
