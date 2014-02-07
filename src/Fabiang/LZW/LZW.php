<?php

/**
 * Copyright 2014 Fabian Grutschus. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * The views and conclusions contained in the software and documentation are those
 * of the authors and should not be interpreted as representing official policies,
 * either expressed or implied, of the copyright holders.
 *
 * @author    Fabian Grutschus <f.grutschus@lubyte.de>
 * @copyright 2014 Fabian Grutschus. All rights reserved.
 * @license   BSD
 * @link      http://github.com/fabiang/lzw
 */

namespace Fabiang\LZW;

/**
 * LZW compression compression and decompression class.
 *
 * @package LZW
 */
class LZW
{

    const INTERNAL_ENCODING = 'UTF-16';

    private $dictionary;
    private $dictionaryToCreate;
    private $numBits;
    private $dataPosition;
    private $dataString;
    private $dataVal;
    private $enlargeIn;
    private $index;

    /**
     * Compress string with LZW.
     *
     * @param string $uncompressed Uncompressed string
     * @param string $encoding     Encoding of the string
     * @return string
     */
    public function compress($uncompressed, $encoding = 'UTF-8')
    {
        if (null === $uncompressed || '' === $uncompressed) {
            return '';
        }

        // reset to defaults
        $this->dictionary         = array();
        $this->dictionaryToCreate = array();
        $this->numBits            = 2;
        $this->dataPosition       = 0;
        $this->dataString         = '';
        $this->dataVal            = 0;
        $this->enlargeIn          = 2;

        $previousEncoding = mb_internal_encoding();
        mb_internal_encoding(self::INTERNAL_ENCODING);

        $uncompressed = mb_convert_encoding($uncompressed, self::INTERNAL_ENCODING, $encoding);

        $length   = mb_strlen($uncompressed);
        $dictSize = 3;
        $word     = '';

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($uncompressed, $i, 1);

            if (!array_key_exists($char, $this->dictionary)) {
                $this->dictionary[$char]         = $dictSize++;
                $this->dictionaryToCreate[$char] = true;
            }

            $wc = $word . $char;

            if (array_key_exists($wc, $this->dictionary)) {
                $word = $wc;
            } else {
                $this->buildString($word);
                $this->dictionary[$wc] = $dictSize++;
                $word                  = $char;
            }
        }

        if ('' !== $word) {
            $this->buildString($word);
        }

        $value = 2;
        $this->shiftRight($value, $this->numBits);

        while (true) {
            $this->dataVal = ($this->dataVal << 1);
            if (15 === $this->dataPosition) {
                $this->dataString .= $this->chr($this->dataVal);
                break;
            } else {
                $this->dataPosition++;
            }
        }

        mb_internal_encoding($previousEncoding);
        return $this->dataString;
    }

    /**
     * Decompress string with LZW.
     *
     * @param string $compressed
     * @return void
     */
    public function decompress($compressed, $encoding = 'UTF-8')
    {
        $previousEncoding = mb_internal_encoding();
        mb_internal_encoding(self::INTERNAL_ENCODING);

        $this->dictionary   = array(0, 1, 2);
        $this->numBits      = 3;
        $this->enlargeIn    = 4;
        $this->dataString   = $compressed;
        $this->dataPosition = 32768;
        $this->dataVal      = $this->ord(mb_substr($compressed, 0, 1));
        $this->index        = 1;

        $dictSize = 4;
        $bits     = $this->power(2);

        $next = $bits;
        switch ($next) {
            case 0:
                $bits = $this->power(8);
                $char = $this->chr($bits);
                break;
            case 1:
                $bits = $this->power(16);
                $char = $this->chr($bits);
                break;
            case 2:
                mb_internal_encoding($previousEncoding);
                return '';
        }

        $this->dictionary[3] = $char;
        $result              = $char;
        $word                   = $result;

        while (true) {
            if ($this->index > mb_strlen($this->dataString)) {
                mb_internal_encoding($previousEncoding);
                return '';
            }

            $char = $this->power($this->numBits);
            switch ($char) {
                case 0:
                    $bits                          = $this->power(8);
                    $this->dictionary[$dictSize++] = $this->chr($bits);
                    $char                          = $dictSize - 1;
                    $this->enlargeIn--;
                    break;
                case 1:
                    $bits                          = $this->power(16);
                    $this->dictionary[$dictSize++] = $this->chr($bits);
                    $char                          = $dictSize - 1;
                    $this->enlargeIn--;
                    break;
                case 2:
                    $result = mb_convert_encoding($result, $encoding, self::INTERNAL_ENCODING);
                    mb_internal_encoding($previousEncoding);
                    return $result;
            }

            $this->checkEnlargeIn(false);

            if (array_key_exists($char, $this->dictionary)) {
                $entry = $this->dictionary[$char];
            } else {
                if ($char === $dictSize) {
                    $entry = $word . mb_substr($word, 0, 1);
                } else {
                    mb_internal_encoding($previousEncoding);
                    return null;
                }
            }

            $result .= $entry;

            // Add w+entry[0] to the dictionary.
            $this->dictionary[$dictSize++] = $word . mb_substr($entry, 0, 1);
            $this->enlargeIn--;

            $word = $entry;
            $this->checkEnlargeIn(false);
        }
    }

    /**
     *
     *
     * @param integer $exp Exponent
     * @return integer
     */
    private function power($exp)
    {
        $bits     = 0;
        $maxpower = pow(2, $exp);
        $power    = 1;

        while ($power != $maxpower) {
            $resb = $this->dataVal & $this->dataPosition;
            $this->dataPosition >>= 1;
            if (0 === $this->dataPosition) {
                $this->dataPosition = 32768;
                $this->dataVal      = $this->ord(mb_substr($this->dataString, $this->index++, 1));
            }
            $bits |= ($resb > 0 ? 1 : 0) * $power;
            $power <<= 1;
        }

        return $bits;
    }

    /**
     * Build string from dictionary.
     *
     * @param string $word current word
     * @return void
     */
    private function buildString($word)
    {
        if (array_key_exists($word, $this->dictionaryToCreate)) {
            $charcode = $this->ord(mb_substr($word, 0, 1));
            if ($charcode < 256) {
                $this->shiftLeftNoValue($this->numBits);
                $value = $charcode;
                $this->shiftRight($value, 8);
            } else {
                $value = 1;
                $this->shiftLeft($value, $this->numBits);
                $value = $charcode;
                $this->shiftRight($value, 16);
            }

            $this->checkEnlargeIn();

            unset($this->dictionaryToCreate[$word]);
        } else {
            $value = $this->dictionary[$word];
            $this->shiftRight($value, $this->numBits);
        }

        $this->checkEnlargeIn();
    }

    /**
     * Shift right.
     *
     * @param mixed   $value Value to shift
     * @param integer $till  Loop till thie value
     * @return void
     */
    private function shiftRight(&$value, $till)
    {
        for ($j = 0; $j < $till; $j++) {
            $this->dataVal = ($this->dataVal << 1) | ($value & 1);
            if (15 === $this->dataPosition) {
                $this->dataString .= $this->chr($this->dataVal);
                $this->dataPosition = 0;
                $this->dataVal      = 0;
            } else {
                $this->dataPosition++;
            }
            $value = $value >> 1;
        }
    }

    /**
     * Shift left.
     *
     * @param integer $value
     * @param integer $till
     * @return void
     */
    private function shiftLeft($value, $till)
    {
        for ($j = 0; $j < $till; $j++) {
            $this->dataVal = ($this->dataVal << 1) | $value;
            if (15 === $this->dataPosition) {
                $this->dataString .= $this->chr($this->dataVal);
                $this->dataPosition = 0;
                $this->dataVal      = 0;
            } else {
                $this->dataPosition++;
            }
            $value = 0;
        }
    }

    /**
     * Shift left without a value.
     *
     * @param integer $till
     */
    private function shiftLeftNoValue($till)
    {
        for ($j = 0; $j < $till; $j++) {
            $this->dataVal = ($this->dataVal << 1);
            if (15 === $this->dataPosition) {
                $this->dataString .= $this->chr($this->dataVal);
                $this->dataPosition = 0;
                $this->dataVal      = 0;
            } else {
                $this->dataPosition++;
            }
        }
    }

    /**
     * Check enlarge in.
     *
     * @param boolean $decrease Descrease enlargeIn by one before testing.
     * @return void
     */
    private function checkEnlargeIn($decrease = true)
    {
        if (true === $decrease) {
            $this->enlargeIn--;
        }
        if (0 === $this->enlargeIn) {
            $this->enlargeIn = pow(2, $this->numBits);
            $this->numBits++;
        }
    }

    /**
     * ord() with multi-byte support.
     *
     * @param string $char
     * @return integer
     */
    private function ord($char)
    {
        $k  = mb_convert_encoding($char, 'UCS-2LE', self::INTERNAL_ENCODING);
        $k1 = ord(substr($k, 0, 1));
        $k2 = ord(substr($k, 1, 1));
        return $k2 * 256 + $k1;
    }

    /**
     * Get character for an integer (multi-byte support).
     *
     * @param integer $u
     * @return string
     */
    private function chr($u)
    {
        return mb_convert_encoding('&#' . intval($u) . ';', self::INTERNAL_ENCODING, 'HTML-ENTITIES');
    }

}
