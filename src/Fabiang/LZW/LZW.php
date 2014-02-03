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

    /**
     * Compress string with LZW.
     *
     * @param string $s
     * @return string
     */
    public static function compress($s)
    {
        $encoding = mb_internal_encoding();
        mb_internal_encoding('UTF-8');

        $dict   = array();
        $data   = self::split($s);
        $out    = array();
        $phrase = $data[0];
        $code   = 256;

        for ($i = 1, $c = count($data); $i < $c; $i++) {
            $currChar = $data[$i];

            if (array_key_exists($phrase . $currChar, $dict)) {
                $phrase .= $currChar;
            } else {
                if (mb_strlen($phrase) > 1) {
                    $add = $dict[$phrase];
                } else {
                    $add = self::ord(mb_substr($phrase, 0, 1));
                }
                $out[]                     = $add;
                $dict[$phrase . $currChar] = $code;
                $code++;
                $phrase                    = $currChar;
            }
        }

        $out[] = mb_strlen($phrase) > 1 ? $dict[$phrase] : self::ord(mb_substr($phrase, 0, 1));

        for ($i = 0, $c = count($out); $i < $c; $i++) {
            $out[$i] = self::chr($out[$i]);
        }

        mb_internal_encoding($encoding);
        return implode('', $out);
    }

    /**
     * Decompress string with LZW.
     *
     * @param string $s
     * @return void
     */
    public static function decompress($s)
    {
        mb_internal_encoding('UTF-8');

        $dict      = array();
        $currChar  = mb_substr($s, 0, 1);
        $oldPhrase = $currChar;
        $out       = array($currChar);
        $code      = 256;
        $length    = mb_strlen($s);

        for ($i = 1; $i < $length; $i++) {
            $currCode = implode('', unpack(
                'N*',
                str_pad(iconv('UTF-8', 'UTF-16BE', mb_substr($s, $i, 1)), 4, "\x00", STR_PAD_LEFT)
            ));
            
            if ($currCode < 256) {
                $phrase = mb_substr($s, $i, 1);
            } else {
                $phrase = array_key_exists($currCode, $dict) ? $dict[$currCode] : ($oldPhrase . $currChar);
            }
            
            $out[]       = $phrase;
            $currChar    = mb_substr($phrase, 0, 1);
            $dict[$code] = $oldPhrase . $currChar;
            $code++;
            $oldPhrase   = $phrase;
        }

        return implode('', $out);
    }

    /**
     * Split multibyte string.
     *
     * @param string $s
     * @return array
     */
    private static function split($s)
    {
        $strlen = mb_strlen($s);
        $array  = array();
        while ($strlen) {
            $array[] = mb_substr($s, 0, 1, "UTF-8");
            $s       = mb_substr($s, 1, $strlen, "UTF-8");
            $strlen  = mb_strlen($s);
        }
        return $array;
    }

    /**
     * Get unicode for character.
     * 
     * @param string $u
     * @return integer
     */
    private static function ord($u)
    {
        $k  = mb_convert_encoding($u, 'UCS-2LE', 'UTF-8');
        $k1 = ord(substr($k, 0, 1));
        $k2 = ord(substr($k, 1, 1));
        return $k2 * 256 + $k1;
    }

    /**
     * Get character for unicode.
     * 
     * @param integer $int
     * @return string
     */
    private static function chr($int)
    {
        return mb_convert_encoding(pack('n', $int), 'UTF-8', 'UTF-16BE');
    }

}
