<?php

declare(strict_types=1);

namespace Amoscato\Integration\Exception;

use RuntimeException;

/**
 * @see https://www.last.fm/api/errorcodes
 */
class LastfmBadResponseException extends RuntimeException
{
    public const CODE_INVALID_PARAMETERS = 6;

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
