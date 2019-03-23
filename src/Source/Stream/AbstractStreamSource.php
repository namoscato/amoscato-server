<?php

declare(strict_types=1);

namespace Amoscato\Source\Stream;

use Amoscato\Console\Helper\PageIterator;
use Amoscato\Console\Output\OutputDecorator;
use Amoscato\Database\PDOFactory;
use Amoscato\Ftp\FtpClient;
use Amoscato\Integration\Client\Client;
use Amoscato\Source\AbstractSource;
use Amoscato\Source\Stream\Query\StreamStatementProvider;
use PDO;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractStreamSource extends AbstractSource implements StreamSourceInterface
{
    /** @var PDOFactory */
    private $databaseFactory;

    /** @var FtpClient */
    private $ftpClient;

    /** @var StreamStatementProvider */
    protected $statementProvider;

    /** @var int */
    protected $weight = 1;

    /**
     * @param PDOFactory $databaseFactory
     * @param FtpClient $ftpClient
     * @param Client $client
     */
    public function __construct(PDOFactory $databaseFactory, FtpClient $ftpClient, Client $client)
    {
        parent::__construct($client);

        $this->databaseFactory = $databaseFactory;
        $this->ftpClient = $ftpClient;
    }

    /**
     * Returns the maximum number of items per page.
     *
     * @return int
     */
    abstract protected function getMaxPerPage(): int;

    /**
     * Extracts data for the specified page.
     *
     * @param int $perPage
     * @param PageIterator $iterator
     *
     * @return iterable
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
    public function setWeight($weight)
    {
        $this->weight = $weight;
    }

    /**
     * @return int
     */
    public function getWeight(): int
    {
        return $this->weight;
    }

    /**
     * {@inheritdoc}
     */
    public function load(OutputInterface $output, $limit = 1): bool
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

                $transformedItem = $this->transform($item);

                if (false === $transformedItem) { // Skip select items
                    continue;
                }

                /** @noinspection SlowArrayOperationsInLoopInspection */
                $values = array_merge(
                    [
                        $this->getType(),
                        $sourceId,
                    ],
                    $transformedItem,
                    $values
                );

                $output->writeVerbose("Transforming {$this->getType()} item: {$values[2]}");

                /** @noinspection DisconnectedForeachInstructionInspection */
                $iterator->incrementCount();
            }

            $iterator->next();
        }

        return $this->insertValues($output, $iterator->getCount(), $values);
    }

    /**
     * @return string|null
     */
    protected function getLatestSourceId(): ?string
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
     *
     * @return string
     */
    protected function getSourceId($item): string
    {
        return (string) $item->id;
    }

    /**
     * @return StreamStatementProvider
     */
    public function getStreamStatementProvider(): StreamStatementProvider
    {
        if (null === $this->statementProvider) {
            $this->statementProvider = new StreamStatementProvider($this->databaseFactory->getInstance());
        }

        return $this->statementProvider;
    }

    /**
     * @param OutputDecorator $output
     * @param int $count
     * @param array $values
     *
     * @return bool
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
     * @param OutputInterface $output
     * @param string $url
     *
     * @return string
     */
    public function cachePhoto(OutputInterface $output, $url): string
    {
        if (false === $data = file_get_contents($url)) {
            throw new RuntimeException("Unable to fetch photo '{$url}'");
        }

        $path = sprintf(
            '%s.%s',
            uniqid("{$this->getType()}_", true),
            pathinfo(parse_url($url)['path'], PATHINFO_EXTENSION)
        );

        return $this
            ->ftpClient
            ->upload($output, $data, $path, 'img');
    }

    /**
     * @param int $limit
     *
     * @return int
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
