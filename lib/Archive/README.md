# (Read-only) PHP Archive: format agnostic archive file representation

[![Build Status](https://travis-ci.org/iqb/Morgue-Archive.png?branch=master)](https://travis-ci.org/iqb/Morgue-Archive)
[![Code Coverage](https://scrutinizer-ci.com/g/iqb/Morgue-Archive/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/iqb/Morgue-Archive)
[![Software License](https://img.shields.io/badge/License-LGPL%20V3-brightgreen.svg?style=flat-square)](LICENSE)

## Issues/pull requests

This repository is a subtree split of the [iqb/Morgue](https://github.com/iqb/Morgue) repository
 so it can be required as a stand alone package via composer.
To open an issues or pull request, please go to the [iqb/Morgue](https://github.com/iqb/Morgue) repository.

## Installation

Via [composer](https://getcomposer.org):

```php
composer require iqb/morgue-archive
```

## Usage

The `Archive` and `ArchiveEntry` classes represent the generalized structure of archive files (e.g. .zip or .rar files).

An archive can be read by a file type specific `ArchiveReaderInterface` implementation,
 modified in a file type agnostic way
 and then persisted with a file type specific `ArchiveWriterInterface` implementation. 
 
Further details can be found in the [iqb/Morgue](https://github.com/iqb/Morgue) repository that contains
at least a working implementation for the ZIP archive format.
