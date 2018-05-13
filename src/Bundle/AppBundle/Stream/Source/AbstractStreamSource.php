<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

use Amoscato\Bundle\AppBundle\Ftp\FtpClient;
use Amoscato\Bundle\AppBundle\Source\AbstractSource;
use Amoscato\Bundle\AppBundle\Stream\Query\StreamStatementProvider;
use Amoscato\Bundle\IntegrationBundle\Client\Client;
use Amoscato\Console\Helper\PageIterator;
use Amoscato\Database\PDOFactory;
use PDO;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractStreamSource extends AbstractSource implements StreamSourceInterface
{
    const LIMIT = 100;

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
     * @return int
     */
    public function getPerPage()
    {
        return self::LIMIT;
    }

    /**
     * @param int $perPage
     * @param PageIterator $iterator
     * @return array
     */
    abstract protected function extract($perPage, PageIterator $iterator);

    /**
     * @param object $item
     * @param OutputInterface $output
     * @return array|bool
     */
    abstract protected function transform($item, OutputInterface $output);

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
            $items = $this->extract($this->getPerPage(), $iterator);

            foreach ($items as $item) {
                $sourceId = $this->getSourceId($item);

                if ($latestSourceId === $sourceId) { // Break if item is already in database
                    $output->writeDebug("Item {$latestSourceId} is already in the database");
                    break 2;
                }

                $transformedItem = $this->transform($item, $output);

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

                $output->writeVerbose("Transforming {$this->getType()} item: {$values[2]}");

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
        if (null === $this->statementProvider) {
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

        $output->writeln("Loading {$count} {$this->getType()} items");

        if ($count === 0) {
            return true;
        }

        $statement = $this
            ->getStreamStatementProvider()
            ->insertRows($count);

        $result = $statement->execute($values);

        if ($result === false) {
            $output->writeln("Error loading {$this->getType()} items");
            $output->writeDebug(var_export($statement->errorInfo(), true));
            return false;
        }

        return true;
    }

    /**
     * @param OutputInterface $output
     * @param string $url
     * @return string
     */
    public function cachePhoto(OutputInterface $output, $url)
    {
        if (false === $data = file_get_contents($url)) {
            throw new \RuntimeException("Unable to fetch photo '{$url}'");
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
}
