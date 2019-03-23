<?php

declare(strict_types=1);

namespace Amoscato\Controller;

use Amoscato\Source\Stream\StreamAggregator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class StreamController extends AbstractController
{
    /**
     * @Route("/stream")
     *
     * @param StreamAggregator $streamAggregator
     *
     * @return JsonResponse
     */
    public function getStreamAction(StreamAggregator $streamAggregator)
    {
        return new JsonResponse(
            $streamAggregator->aggregate(),
            200,
            [
                'Access-Control-Allow-Origin' => 'http://localhost:1313',
            ]
        );
    }
}
