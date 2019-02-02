<?php
/**
 * MIT License <https://opensource.org/licenses/mit>
 *
 * Copyright (c) 2015 Kerem Güneş
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
declare(strict_types=1);

namespace Froq\Http\Client\Agent;

use Froq\Http\Client\Client;

/**
 * @package    Froq
 * @subpackage Froq\Http\Client\Agent
 * @object     Froq\Http\Client\Agent\AbstractAgent
 * @author     Kerem Güneş <k-gun@mail.com>
 * @since      3.0
 */
abstract class AbstractAgent
{
    protected $client;

    protected $handle;
    protected $handleType;

    public function __construct(Client $client)
    {
        // $this->client = $client;
    }
    public static $i=0;
    public final function close(): void
    {
        if ($this->handle) {
            if ($this->handleType == 'curl') {
                curl_close($this->handle);
            } elseif ($this->handleType == 'fsock') {
                fclose($this->handle);
            }
            $this->handle = $this->handleType = null;
        }
    }

    abstract public function run(): array;
    abstract protected function options(): array;
}
