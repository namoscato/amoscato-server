<?php

namespace Amoscato\Bundle\AppBundle\Controller;

use Amoscato\Bundle\AppBundle\Stream\StreamAggregator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route(service="amoscato.controller.stream")
 */
class StreamController extends Controller
{
    /** @var StreamAggregator */
    private $streamAggregator;

    /**
     * @param StreamAggregator $streamAggregator
     */
    public function __construct(StreamAggregator $streamAggregator)
    {
        $this->streamAggregator = $streamAggregator;
    }

    /**
     * @Route("/stream")
     */
    public function getStreamAction()
    {
        $data = $this->streamAggregator->aggregate();

        return new JsonResponse(
            $data,
            200,
            [
                'Access-Control-Allow-Origin' => 'http://localhost:1313'
            ]
        );
    }
}
