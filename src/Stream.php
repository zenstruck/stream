<?php

/*
 * This file is part of the zenstruck/stream package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Stream implements \Stringable
{
    /** @var resource */
    private $resource;
    private bool $autoClose = false;

    /**
     * @param resource $resource
     */
    private function __construct($resource)
    {
        if (!\is_resource($resource)) {
            throw new \InvalidArgumentException(\sprintf('"%s" is not a resource.', \get_debug_type($resource)));
        }

        $this->resource = $resource;
    }

    public function __destruct()
    {
        if ($this->autoClose) {
            $this->close();
        }
    }

    public function __toString(): string
    {
        return $this->contents();
    }

    /**
     * @param string|resource|self $what
     */
    public static function wrap(mixed $what): self
    {
        if ($what instanceof self) {
            return $what;
        }

        if (\is_string($what)) {
            return self::inMemory()->write($what)->rewind();
        }

        return new self($what);
    }

    public static function inMemory(): self
    {
        return self::open('php://memory', 'rw');
    }

    public static function inOutput(): self
    {
        return self::open('php://output', 'rw');
    }

    public static function tempFile(): self
    {
        if (false === $handle = @\tmpfile()) {
            throw new \RuntimeException('Unable to create temporary handle.');
        }

        return new self($handle);
    }

    /**
     * @see \fopen()
     *
     * @param resource|null $context
     */
    public static function open(string $filename, string $mode, bool $useIncludePath = false, $context = null): self
    {
        if (false === $handle = @\fopen($filename, $mode, $useIncludePath, $context)) {
            throw new \RuntimeException(\sprintf('Unable to fopen "%s" with mode "%s".', $filename, $mode));
        }

        return new self($handle);
    }

    public function autoClose(): self
    {
        $this->autoClose = true;

        return $this;
    }

    /**
     * @return resource
     */
    public function get()
    {
        if (!\is_resource($this->resource)) {
            throw new \RuntimeException('Resource is closed.');
        }

        return $this->resource;
    }

    /**
     * @see \get_resource_id()
     */
    public function id(): int
    {
        return \get_resource_id($this->get());
    }

    /**
     * @see \get_resource_type()
     */
    public function type(): string
    {
        return \get_resource_type($this->resource);
    }

    /**
     * @see \stream_get_contents()
     */
    public function contents(?int $length = null, int $offset = -1): string
    {
        if ($this->isSeekable()) {
            $this->rewind();
        }

        if (false === $contents = @\stream_get_contents($this->get(), $length, $offset)) {
            throw new \RuntimeException('Unable to get contents of stream.');
        }

        return $contents;
    }

    /**
     * @see \file_put_contents()
     *
     * @param resource|null $context
     */
    public function putContents(string $filename, int $flags = 0, $context = null): self
    {
        if (false === @\file_put_contents($filename, $this->get(), $flags, $context)) {
            throw new \RuntimeException(\sprintf('Unable to dump contents of stream to "%s".', $filename));
        }

        return $this;
    }

    /**
     * @see \rewind()
     */
    public function rewind(): self
    {
        if (!$this->isSeekable()) {
            throw new \RuntimeException('Stream does not support seeking.');
        }

        if (false === @\rewind($this->get())) {
            throw new \RuntimeException('Unable to rewind stream.');
        }

        return $this;
    }

    public function isOpen(): bool
    {
        return \is_resource($this->resource);
    }

    public function isClosed(): bool
    {
        return !$this->isOpen();
    }

    public function isSeekable(): bool
    {
        return $this->metadata('seekable');
    }

    /**
     * @see \fwrite()
     * @see \stream_copy_to_stream()
     *
     * @param string|resource|self $data
     */
    public function write(mixed $data, ?int $length = null, int $offset = 0): self
    {
        if (\is_string($data)) {
            return $this->writeString($data, $length);
        }

        if (\is_resource($data) || $data instanceof self) {
            return $this->writeStream($data, $length, $offset);
        }

        throw new \InvalidArgumentException(\sprintf('"%s" is not a string or a resource.', \get_debug_type($data)));
    }

    /**
     * @see \stream_get_meta_data()
     *
     * @return mixed|array<string,mixed>
     */
    public function metadata(?string $key = null): mixed
    {
        $metadata = \stream_get_meta_data($this->get());

        if (!$key) {
            return $metadata;
        }

        if (!\array_key_exists($key, $metadata)) {
            throw new \InvalidArgumentException(\sprintf('Key "%s" not available.', $key));
        }

        return $metadata[$key];
    }

    public function uri(): string
    {
        return $this->metadata('uri');
    }

    /**
     * @see \fclose
     */
    public function close(): void
    {
        if (\is_resource($this->resource)) {
            \fclose($this->resource);
        }
    }

    private function writeString(string $data, ?int $length = null): self
    {
        if (false === @\fwrite($this->get(), $data, $length)) { // @phpstan-ignore-line
            throw new \RuntimeException('Unable to write to stream.');
        }

        return $this;
    }

    /**
     * @param resource|self $data
     */
    private function writeStream(mixed $data, ?int $length = null, int $offset = 0): self
    {
        if (false === @\stream_copy_to_stream(self::wrap($data)->get(), $this->get(), $length, $offset)) {
            throw new \RuntimeException('Unable to copy stream.');
        }

        return $this;
    }
}
