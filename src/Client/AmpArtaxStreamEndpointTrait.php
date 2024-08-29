<?php

declare(strict_types=1);

namespace Docker\Client;

use Amp\Artax\Response;
use Amp\CancellationTokenSource;
use Amp\Promise;
use Docker\API\Client;
use Docker\Stream\ArtaxCallbackStream;
use Jane\Component\OpenApiRuntime\Client\Exception\InvalidFetchModeException;
use Symfony\Component\Serializer\SerializerInterface;
use function Amp\call;

trait AmpArtaxStreamEndpointTrait
{
    abstract protected function transformResponseBody(\Psr\Http\Message\ResponseInterface $response, \Symfony\Component\Serializer\SerializerInterface $serializer, ?string $contentType = null);

    public function parseArtaxStreamResponse(
        Response $response,
        SerializerInterface $serializer,
        CancellationTokenSource $cancellationTokenSource,
        string $fetchMode = Client::FETCH_OBJECT
    ): Promise {
        if (!\in_array($fetchMode, [Client::FETCH_OBJECT, Client::FETCH_RESPONSE], true)) {
            throw new InvalidFetchModeException(\sprintf('Fetch mode %s is not supported', $fetchMode));
        }

        return call(function () use ($response, $serializer, $fetchMode, $cancellationTokenSource) {
            $responseTransformer = null;
            if (Client::FETCH_OBJECT === $fetchMode) {
                $responseTransformer = function ($chunk) use ($response, $serializer) {
                    $contentType = $response->hasHeader('Content-Type') ? $response->getHeader('Content-Type') : null;
                    return $this->transformResponseBody($chunk, $serializer, $contentType);
                };
            }

            return new ArtaxCallbackStream($response->getBody(), $cancellationTokenSource, $responseTransformer);
        });
    }
}
