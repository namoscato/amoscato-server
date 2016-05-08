<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

use Amoscato\Bundle\AppBundle\Stream\Query\PhotoStatementProvider;
use Amoscato\Bundle\IntegrationBundle\Client\Client;
use PDO;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Source implements SourceInterface
{
    const LIMIT = 100;

    /**
     * @var PhotoStatementProvider
     */
    protected $statementProvider;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $type;

    /**
     * @param PDO $database
     * @param Client $client
     */
    public function __construct(PDO $database, Client $client)
    {
        $this->statementProvider = new PhotoStatementProvider($database);
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
     * @param int $limit
     * @param int $page
     * @return array
     */
    abstract protected function extract($limit = self::LIMIT, $page = 1);

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
        $items = $this->extract();
        $values = [];
        
        foreach ($items as $item) {
            $values[] = $this->getType();

            $itemValues = $this->transform($item);

            $values = array_merge(
                $values,
                $itemValues
            );

            $output->writeVerbose("Transforming '" . $this->getType() . "' item: {$itemValues[4]}");
            
            $count++;
        }
        
        $statement = $this->statementProvider->insertRows($count);
        
        return $statement->execute($values);
    }
}
