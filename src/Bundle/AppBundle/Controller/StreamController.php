<?php

namespace Amoscato\Bundle\AppBundle\Controller;

use Amoscato\Bundle\AppBundle\Stream\StreamAggregator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class StreamController extends Controller
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
