<?php

namespace Amoscato\Bundle\IntegrationBundle\Exception;

/**
 * @see https://www.last.fm/api/errorcodes
 */
class LastfmBadResponseException extends \RuntimeException
{
    /** @var object */
    private $responseBody;

    /**
     * @param object $responseBody
     */
    public function __construct($responseBody)
    {
        parent::__construct($responseBody->message, $responseBody->error);

        $this->responseBody = $responseBody;
    }

    /**
     * @return object
     */
    public function getResponseBody()
    {
        return $this->responseBody;
    }
}
