# fabiang/lzw [![Build Status](https://travis-ci.org/fabiang/lzw.png)](https://travis-ci.org/fabiang/lzw)

Compression library for Lempel–Ziv–Welch (LZW) for PHP. Read more [about LZW on Wikipedia](http://en.wikipedia.org/wiki/Lempel%E2%80%93Ziv%E2%80%93Welch).

This library supports mulit-byte encodings and is **work in progress**.

## SYSTEM REQUIREMENTS

- PHP 5.3
- ext-mbstring - for handling multibyte string (should be available on most systems)

## LICENCE

BSD-2-Clause. See the [LICENCE](LICENCE.md).

## INSTALLATION

New to Composer? Read the [introduction](https://getcomposer.org/doc/00-intro.md#introduction). Add the following to your composer file:

    {
        "require": {
            "fabiang/lzw": "*"
        }
    }
