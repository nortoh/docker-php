<?php

declare(strict_types=1);

namespace Docker\Tests\Resource;

use Docker\API\Client;
use Docker\API\Model\AuthConfig;
use Docker\Context\ContextBuilder;
use Docker\Stream\BuildStream;
use Docker\Stream\CreateImageStream;
use Docker\Stream\PushStream;
use Docker\Tests\TestCase;

class ImageResourceTest extends TestCase
{
    /**
     * Return a container manager.
     */
    private function getManager()
    {
        return self::getDocker();
    }

    public function testBuild(): void
    {
        $contextBuilder = new ContextBuilder();
        $contextBuilder->from('ubuntu:precise');
        $contextBuilder->add('/test', 'test file content');

        $context = $contextBuilder->getContext();
        $buildStream = $this->getManager()->imageBuild($context->read(), ['t' => 'test-image']);

        assert($buildStream instanceof BuildStream);
        $this->assertInstanceOf(BuildStream::class, $buildStream);

        $lastMessage = '';

        $buildStream->onFrame(function ($frame) use (&$lastMessage): void {
            $lastMessage = $frame->getStream();
        });
        $buildStream->wait();

        $this->assertStringContainsString('Successfully', $lastMessage);
    }

    public function testCreate(): void
    {
        $createImageStream = $this->getManager()->imageCreate('', [
            'fromImage' => 'registry:latest',
        ]);

        assert($createImageStream instanceof CreateImageStream);
        $this->assertInstanceOf(CreateImageStream::class, $createImageStream);

        $firstMessage = null;

        $createImageStream->onFrame(function ($createImageInfo) use (&$firstMessage): void {
            if (null === $firstMessage) {
                $firstMessage = $createImageInfo->getStatus();
            }
        });
        $createImageStream->wait();

        $this->assertStringContainsString('Pulling from library/registry', $firstMessage ?? '');
    }

    public function testPushStream(): void
    {
        $contextBuilder = new ContextBuilder();
        $contextBuilder->from('ubuntu:precise');
        $contextBuilder->add('/test', 'test file content');

        $context = $contextBuilder->getContext();
        $this->getManager()->imageBuild($context->read(), ['t' => 'localhost:5000/test-image'], [], Client::FETCH_OBJECT);

        $registryConfig = new AuthConfig();
        $registryConfig->setServeraddress('localhost:5000');
        $pushImageStream = $this->getManager()->imagePush('localhost:5000/test-image', [], [
            'X-Registry-Auth' => $registryConfig,
        ]);

        assert($pushImageStream instanceof PushStream);
        $this->assertInstanceOf(PushStream::class, $pushImageStream);

        $firstMessage = null;

        $pushImageStream->onFrame(function ($pushImageInfo) use (&$firstMessage): void {
            if (null === $firstMessage) {
                $firstMessage = $pushImageInfo->getStatus();
            }
        });
        $pushImageStream->wait();

        $this->assertStringContainsString('repository [localhost:5000/test-image]', $firstMessage ?? '');
    }
}
