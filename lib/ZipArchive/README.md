# (Read-only) PHP stream wrapper for using only a portion of a stream.

[![Build Status](https://travis-ci.org/iqb/SubStream.png?branch=master)](https://travis-ci.org/iqb/SubStream)
[![Code Coverage](https://scrutinizer-ci.com/g/iqb/SubStream/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/iqb/SubStream)
[![Software License](https://img.shields.io/badge/License-LGPL%20V3-brightgreen.svg?style=flat-square)](LICENSE)

## Issues/pull requests

This repository is a subtree split of the [iqb/Archive](https://github.com/iqb/Archive) repository so it can be required as a stand alone package via composer.
To open an issues or pull request, please go to the [iqb/Archive](https://github.com/iqb/Archive) repository.

## Installation

Via [composer](https://getcomposer.org):

```php
composer require iqb/substream
```

## Usage

The stream wrapper is registered for the ``iqb.substream://`` protocol.
To use as substream, just open a new like that:

```php

use const iqb\stream\SUBSTREAM_SCHEME;

$originalStream = \fopen('filename', 'r');
$offset = 25;
$length = 100;

$substream = \fopen(SUBSTREAM_SCHEME . "://$offset:$length/" . (int)$originalStream);

\fseek($orignalStream, 50);
\fseek($substream, 25);

// Will not fail
assert(\fread($originalStream, 50) === \fread($substream, 50));
```
