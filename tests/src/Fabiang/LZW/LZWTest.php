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
 * @link      http://github.com/fabiang/xmpp
 */

namespace Fabiang\LZW;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2014-02-03 at 11:03:36.
 */
class LZWTest extends \PHPUnit_Framework_TestCase
{

    /**
     *
     * @var LZW
     */
    protected $object;

    public function setUp()
    {
        $this->object = new LZW;
    }

    /**
     * Test compression.
     *
     * @covers Fabiang\LZW\LZW
     * @dataProvider provideCompressionString
     * @return void
     */
    public function testCompress($decoded, $encoded)
    {
        $result = $this->object->compress($decoded, 'UTF-8');
        $this->assertSame($encoded, base64_encode($result));
    }

    /**
     * Test that special vars are returned as empty string.
     *
     * @param mixed $uncompressed
     * @covers Fabiang\LZW\LZW::compress
     * @dataProvider provideEmptyCompressionStrings
     * @return void
     */
    public function testCompressEmpty($uncompressed)
    {
        $this->assertSame('', $this->object->compress($uncompressed));
    }

    /**
     * Test decompression.
     *
     * @param type $decoded
     * @param type $encoded
     * @covers Fabiang\LZW\LZW
     * @dataProvider provideCompressionString
     * @return void
     */
    public function testDecompress($decoded, $encoded)
    {
        $this->assertSame($decoded, $this->object->decompress(base64_decode($encoded)));
    }

    /**
     * Test decompression.
     *
     * @param mixed $compressed
     * @covers Fabiang\LZW\LZW::decompress
     * @dataProvider provideEmptyCompressionStrings
     * @return void
     */
    public function testDeocmpressEmpty($compressed)
    {
        $this->assertSame('', $this->object->decompress($compressed));
    }

    /**
     * Test compresses and decompresses all printable UTF-16 characters
     *
     * @covers Fabiang\LZW\LZW::compress
     * @covers Fabiang\LZW\LZW::decompress
     * @large
     * @return void
     */
    public function testCompressAndDecompressAllUTF16()
    {
        $this->markTestIncomplete('Does not work yet.');
        $testString = '';

        for ($i = 32; $i < 127; $i++) {
            $testString .= $this->chr($i);
        }
        for ($i = 128 + 32; $i < 55296; $i++) {
            $testString .= $this->chr($i);
        }
        for ($i = 63744; $i < 65536; $i++) {
            $testString .= $this->chr($i);
        }

        $compressed = $this->object->compress($testString);
        $this->assertSame($testString, $this->object->decompress($compressed));
    }

    private function chr($u)
    {
        return html_entity_decode("&#$u;", ENT_NOQUOTES, 'UTF-8');
    }

    /**
     * Data provider for compression strings.
     *
     * @return array
     */
    public function provideCompressionString()
    {
        return array(
            array(
                'ABC',
                'IIIQwkAA'
            ),
            array(
                'foobar foobar foobar',
                'GYexCMEMCcAJQjeYrSA='
            ),
            array(
                'someumlauts ÄÜÖß',
                'M4ewtgpgrmA2CGUAuwAEARgOwNYPtAAA'
            ),
            array(
                'somespecialchars !"§$%&/()=?+~#\',.-;:_',
                'M4ewtgpsAOEMYEsCGAbOALJAnYACAhAEQDlAJAKQBkA9ABQCUAvAPwDUAfgMQDkANAHQBaANwAuAPpAA'
            ),
            array(
                'very special unicode unicode: …„“‚‘',
                'G4UwTgngBAzgDiAxgSwIYBsoFcB2zED2AJiNnoSQFxSBkBIHgEgOASBYBIBgEQAA'
            ),
            array(
                '…',
                'mQEQAA=='
            ),
            array(
                ' !"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz|}~',
                'AQQgRAxAJApAZAcgBQEoBUBqANAWgHQD0ADAIwBMAzACwCsAbAOwAcAnAFwDcAPALwB8AfgACAQQBCAYQAiAUQBiAcQASASQBSAaQA'
                . 'yAWQByAeQAKARQBKAZQAqAVQBqAdQAaATQBaAbQA6AXQB6APoABgCGAEYAxgAmAKYAZgDmABYAlgBWANYANgC2AHYA9gAOAI4ATg'
                . 'DOAC4ArgBuAO4AHgCeAF4APgC+AH5AAAA='
            )
        );
    }

    /**
     * Provide some uncompressed values that should be empty strings.
     *
     * @return array
     */
    public function provideEmptyCompressionStrings()
    {
        return array(
            array(''),
            array(null)
        );
    }

}
