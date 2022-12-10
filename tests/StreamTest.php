<?php

namespace Zenstruck\Tests;

use PHPUnit\Framework\TestCase;
use Zenstruck\Stream;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class StreamTest extends TestCase
{
    /**
     * @test
     */
    public function can_create_in_memory_resource(): void
    {
        $this->assertSame('some data', Stream::inMemory()->write('some data')->contents());
        $this->assertSame('different data', \stream_get_contents(Stream::inMemory()->write('different data')->rewind()->get()));
    }

    /**
     * @test
     */
    public function can_create_from_string(): void
    {
        $this->assertSame('some data', Stream::wrap('some data')->contents());
        $this->assertSame('different data', \stream_get_contents(Stream::wrap('different data')->get()));
    }

    /**
     * @test
     */
    public function cannot_write_invalid_data(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Stream::inMemory()->write([]);
    }

    /**
     * @test
     */
    public function can_write_resource(): void
    {
        $res = Stream::inMemory()->write(Stream::inMemory()->write('foo')->rewind()->get());

        $this->assertSame('foo', $res->contents());
    }

    /**
     * @test
     */
    public function can_create_temp_file(): void
    {
        $stream = Stream::tempFile();
        $path = $stream->uri();

        $stream->write('foo bar');

        $this->assertSame('foo bar', $stream->contents());
        $this->assertFileExists($path);

        $stream->close();

        $this->assertFileDoesNotExist($path);
    }

    /**
     * @test
     */
    public function cannot_wrap_invalid_data(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Stream::wrap([]);
    }

    /**
     * @test
     */
    public function can_wrap_self(): void
    {
        $res = Stream::inMemory();

        $this->assertSame($res, Stream::wrap($res));
    }

    /**
     * @test
     */
    public function invalid_open(): void
    {
        $this->expectException(\RuntimeException::class);

        Stream::open('some-file', 'r');
    }

    /**
     * @test
     */
    public function cannot_get_closed_resource(): void
    {
        $res = Stream::inMemory();

        $res->close();

        $this->expectException(\RuntimeException::class);

        $res->get();
    }

    /**
     * @test
     */
    public function can_create_in_output(): void
    {
        \ob_start();
        Stream::inOutput()->write('foobar')->close();
        $content = \ob_get_clean();

        $this->assertSame('foobar', $content);
    }

    /**
     * @test
     */
    public function can_get_metadata(): void
    {
        $stream = Stream::inMemory();

        $this->assertSame('php://memory', $stream->metadata()['uri']);
        $this->assertSame('php://memory', $stream->metadata('uri'));
        $this->assertTrue($stream->isSeekable());
    }

    /**
     * @test
     */
    public function cannot_access_invalid_metadata(): void
    {
        $stream = Stream::inMemory();

        $this->expectException(\InvalidArgumentException::class);

        $stream->metadata('invalid');
    }

    /**
     * @test
     */
    public function can_get_type(): void
    {
        $stream = Stream::inMemory();

        $this->assertSame('stream', $stream->type());
        $stream->close();
        $this->assertSame('Unknown', $stream->type());
    }

    /**
     * @test
     */
    public function can_get_id(): void
    {
        $stream = Stream::inMemory();

        $this->assertIsInt($stream->id());
    }
}
