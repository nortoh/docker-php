<?php

declare(strict_types=1);

namespace Docker\Endpoint;

use Docker\API\Client;
use Docker\API\Endpoint\ContainerAttachWebsocket as BaseEndpoint;
use Docker\Stream\AttachWebsocketStream;
use Jane\Component\OpenApiRuntime\Client\Exception\InvalidFetchModeException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ContainerAttachWebsocket extends BaseEndpoint
{
    public function getExtraHeaders(): array
    {
        return \array_merge(parent::getExtraHeaders(), [
            'Host' => 'localhost',
            'Origin' => 'php://docker-php',
            'Upgrade' => 'websocket',
            'Connection' => 'Upgrade',
            'Sec-WebSocket-Version' => '13',
            'Sec-WebSocket-Key' => \base64_encode(\uniqid()),
        ]);
    }

    public function parsePSR7Response(ResponseInterface $response, SerializerInterface $serializer, string $fetchMode = Client::FETCH_OBJECT)
    {
        if (Client::FETCH_OBJECT === $fetchMode) {
            if (101 === $response->getStatusCode()) {
                return new AttachWebsocketStream($response->getBody());
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
