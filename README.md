# PHP ZipArchive drop-in replacement

[![Build Status](https://travis-ci.org/iqb/ZipArchive.png?branch=master)](https://travis-ci.org/iqb/ZipArchive)
[![Code Coverage](https://scrutinizer-ci.com/g/iqb/ZipArchive/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/iqb/ZipArchive)
[![Software License](https://img.shields.io/badge/License-LGPL%20V3-brightgreen.svg?style=flat-square)](LICENSE)

This package aims to become a drop-in replacement for the ZipArchive class provided by the PHP zip extension.

The extensions ZipArchive class wraps the [libzip](https://libzip.org/) C library.
This provides a widely used, tested, fast and reliable way to read/modify ZIP files.
The main limitation in using libzip is the inability of ZipArchive to work with PHP stream wrappers/filters,
 e.g. open a ZIP file directly from a HTTP URL. 

### References
- [ZIP specification v 6.3.3](https://www.loc.gov/preservation/digital/formats/digformatspecs/APPNOTE(20120901)_Version_6.3.3.txt)
- [Additional extra field definitions](https://github.com/nih-at/libzip/blob/master/docs/extrafld.txt)
