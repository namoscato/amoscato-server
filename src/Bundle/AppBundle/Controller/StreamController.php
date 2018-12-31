<?php

namespace Amoscato\Bundle\AppBundle\Controller;

use Amoscato\Bundle\AppBundle\Stream\StreamAggregator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class StreamController extends AbstractController
{
    /**
     * @Route("/stream")
     * @param StreamAggregator $streamAggregator
     * @return JsonResponse
     */
    public function getStreamAction(StreamAggregator $streamAggregator)
    {
        return new JsonResponse(
            $streamAggregator->aggregate(),
            200,
            [
                'Access-Control-Allow-Origin' => 'http://localhost:1313'
            ]
        );
    }
}
