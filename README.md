# zenstruck/stream

[![CI Status](https://github.com/zenstruck/stream/workflows/CI/badge.svg)](https://github.com/zenstruck/stream/actions?query=workflow%3ACI)
[![codecov](https://codecov.io/gh/zenstruck/stream/branch/1.x/graph/badge.svg?token=3JO1UJPHSE)](https://codecov.io/gh/zenstruck/stream)

Object wrapper for PHP resources.

## Installation

```bash
composer require zenstruck/stream
```

## API

### Create Stream Object

```php
use Zenstruck\Stream;

// wrap
$stream = Stream::wrap($resource);
$stream = Stream::wrap('string');
$stream = Stream::wrap($stream);

// open
$stream = Stream::open('some/file.txt', 'r');

// php://memory
$stream = Stream::inMemory();

// php://output
$stream = Stream::inOutput();

// \tmpfile()
$stream = Stream::tempFile();

// autoclose on $stream __destruct
$stream->autoClose(); // Stream
```

### Use Stream Object

```php
/** @var \Zenstruck\Stream $stream */

// metadata
$stream->id(); // int - the resource id
$stream->type(); // string - the resource type
$stream->metadata(); // array - the resources metadata
$stream->metadata('wrapper_type'); // mixed - specific resource metadata key
$stream->uri(); // string - shortcut for `$stream->metadata('uri')`

// read
$stream->get(); // resource - the raw, wrapped resource
$stream->contents(); // string - the contents of the resource (auto-rewound)

// write
$stream->write($resource); // self - write another resource to the stream
$stream->write('string'); // self - write a string to the stream
$stream->write($anotherStream); // self - write another \Zenstruck\Stream instance to the stream

// manipulate
$stream->close(); // no-return - close the resource (if open)
$stream->rewind(); // self - rewind the stream
```
