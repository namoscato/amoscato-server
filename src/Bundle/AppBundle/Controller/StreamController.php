<?php

namespace Amoscato\Bundle\AppBundle\Controller;

use Amoscato\Bundle\AppBundle\Stream\Query\StreamStatementProvider;
use Amoscato\Bundle\AppBundle\Stream\Source\SourceCollection;
use Amoscato\Bundle\AppBundle\Stream\Source\SourceInterface;
use Amoscato\Database\PDOFactory;
use PDO;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route(service="amoscato.controller.stream")
 */
class StreamController extends Controller
{
    const STREAM_SIZE = 1000.0;

    /** @var PDOFactory */
    private $databaseFactory;

    /** @var SourceInterface[] */
    private $sourceCollection;

    /**
     * @param PDOFactory $pdoFactory
     * @param SourceCollection $sourceCollection
     */
    public function __construct(PDOFactory $pdoFactory, SourceCollection $sourceCollection)
    {
        $this->databaseFactory = $pdoFactory;
        $this->sourceCollection = $sourceCollection;
    }

    /**
     * @Route("/stream")
     */
    public function getStreamAction()
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
                ceil(self::STREAM_SIZE / $weightedTypeHashCount * $source->getWeight())
            );

            $statement->execute();

            $typeResults[$source->getType()] = $statement->fetchAll(PDO::FETCH_ASSOC);
        }

        $data = [];

        for ($i = 0; $i < self::STREAM_SIZE; $i++) {
            $randomIndex = rand(0, $weightedTypeHashCount - 1);

            if ($item = array_shift($typeResults[$weightedTypeHash[$randomIndex]])) {
                $data[] = $item;
            }
        }

        return new JsonResponse(
            $data,
            200,
            [
                'Access-Control-Allow-Origin' => 'http://localhost:1313'
            ]
        );
    }

    /**
     * @return StreamStatementProvider
     */
    public function getStreamStatementProvider()
    {
        return new StreamStatementProvider($this->databaseFactory->getInstance());
    }
}
