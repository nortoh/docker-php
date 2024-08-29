<?php

declare(strict_types=1);

namespace Docker\Tests\Stream;

use Docker\API\Model\BuildInfo;
use Docker\Stream\MultiJsonStream;
use Docker\Tests\TestCase;
use GuzzleHttp\Psr7\BufferStream;
use Symfony\Component\Serializer\SerializerInterface;

class MultiJsonStreamTest extends TestCase
{
    public static function jsonStreamDataProvider()
    {
        return [
            [
                '{}{"abc":"def"}',
                ['{}', '{"abc":"def"}'],
            ],
            [
                 '{"test": "abc\"\""}',
                ['{"test":"abc\"\""}'],
            ],
            [
                '{"test": "abc\"{{-}"}',
                ['{"test":"abc\"{{-}"}'],
            ],
        ];
    }

    /**
     * @param $jsonStream
     * @param $jsonParts
     * @dataProvider jsonStreamDataProvider
     */
    public function testReadJsonEscapedDoubleQuote(string $jsonStream, array $jsonParts): void
    {
        $stream = new BufferStream();
        $stream->write($jsonStream);

        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();

        $serializer
            ->expects($this->exactly(\count($jsonParts)))
            ->method('deserialize')
                ->willReturnOnConsecutiveCalls(...\array_map(function ($part) {
                    return [$part, BuildInfo::class, 'json', []];
                }, $jsonParts))
        ;

        $stub = $this->getMockBuilder(MultiJsonStream::class)->setConstructorArgs([$stream, $serializer])->getMockForAbstractClass();

        assert($stub instanceof MultiJsonStream);
        $stub->expects($this->any())
            ->method('getDecodeClass')
            ->willReturn('BuildInfo');

        $stub->wait();
    }
}
