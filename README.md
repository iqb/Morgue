# Morgue: modular PHP archive file reader and writer

[![Build Status](https://travis-ci.org/iqb/Morgue.png?branch=master)](https://travis-ci.org/iqb/Morgue)
[![Code Coverage](https://scrutinizer-ci.com/g/iqb/Morgue/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/iqb/Morgue)
[![Software License](https://img.shields.io/badge/License-LGPL%20V3-brightgreen.svg?style=flat-square)](LICENSE)

This package aims to become a drop-in replacement for the ZipArchive class provided by the PHP zip extension.

The extensions ZipArchive class wraps the [libzip](https://libzip.org/) C library.
This provides a widely used, tested, fast and reliable way to read/modify ZIP files.
The main limitation in using libzip is the inability of ZipArchive to work with PHP stream wrappers/filters,
 e.g. open a ZIP file directly from a HTTP URL. 

### References
- [PKZip .ZIP Application Note Overview page](https://support.pkware.com/display/PKZIP/APPNOTE)
- ZIP specification [v6.3.3](https://www.pkware.com/documents/APPNOTE/APPNOTE-6.3.3.TXT) ([mirror](https://www.loc.gov/preservation/digital/formats/digformatspecs/APPNOTE(20120901)_Version_6.3.3.txt)), [v6.3.4](https://www.pkware.com/documents/APPNOTE/APPNOTE-6.3.4.TXT) 
- [libZIP additional extra field definitions](https://github.com/nih-at/libzip/blob/master/docs/extrafld.txt)
