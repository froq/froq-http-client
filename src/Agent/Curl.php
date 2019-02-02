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

use Froq\Http\Client\{AbstractClient, ClientError};

/**
 * @package    Froq
 * @subpackage Froq\Http\Client\Agent
 * @object     Froq\Http\Client\Agent\Curl
 * @author     Kerem Güneş <k-gun@mail.com>
 * @since      3.0
 */
final class Curl extends Agent
{
    public function __construct(AbstractClient $client)
    {
        if (!extension_loaded('curl')) {
            throw new AgentException('curl module not found');
        }

        parent::__construct($client);

        $this->handle = curl_init();
        $this->handleType = 'curl';
    }

    public function run(): array
    {
        curl_setopt_array($this->handle, $this->options());

        $result =@ curl_exec($this->handle);
        if ($result === false) {
            return [null, null, new ClientError(curl_error($this->handle), curl_errno($this->handle))];
        }
        return [$result, curl_getinfo($this->handle), null];
    }
}
