<?php

declare(strict_types=1);

namespace Docker\Endpoint;

use Docker\API\Client;
use Docker\API\Endpoint\ExecStart as BaseEndpoint;
use Docker\Stream\DockerRawStream;
use Jane\Component\OpenApiRuntime\Client\Exception\InvalidFetchModeException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ExecStart extends BaseEndpoint
{
    public function parsePSR7Response(ResponseInterface $response, SerializerInterface $serializer, string $fetchMode = Client::FETCH_OBJECT)
    {
        if (Client::FETCH_OBJECT === $fetchMode) {
            if (200 === $response->getStatusCode() && DockerRawStream::HEADER === $response->getHeaderLine('Content-Type')) {
                return new DockerRawStream($response->getBody());
            }

            $contentType = $response->hasHeader('Content-Type') ? current($response->getHeader('Content-Type')) : null;
            return $this->transformResponseBody($response, $serializer, $contentType);
        }

        if (Client::FETCH_RESPONSE === $fetchMode) {
            return $response;
        }

        throw new InvalidFetchModeException(\sprintf('Fetch mode %s is not supported', $fetchMode));
    }
}
