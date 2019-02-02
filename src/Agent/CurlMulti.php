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

use Froq\Http\Client\{Client, ClientError};

/**
 * @package    Froq
 * @subpackage Froq\Http\Client\Agent
 * @object     Froq\Http\Client\Agent\CurlMulti
 * @author     Kerem Güneş <k-gun@mail.com>
 * @since      3.0
 */
final class CurlMulti extends Agent
{
    protected $handles;

    public function __construct(Client $client)
    {
        if (!extension_loaded('curl')) {
            throw new AgentException('curl module not found');
        }

        parent::__construct($client);

        $this->handle = curl_multi_init();
        $this->handleType = 'curlmulti';
    }

    public function run(): array
    {
        $done = [];
        $aaa = [];

        foreach ($this->handles as $handle) {
            curl_setopt_array($handle, $handle->options());

            $error = curl_multi_add_handle($this->handle, $handle);
            if ($error) {
                throw new AgentException(curl_multi_strerror($error), $error);
            }
        }

        // $resource = $handle->getHandle();
        // $resourceId = (int) $resource;
        // if (!isset($done[$resourceId])) {
        //     $timeout = (float) $handle->getClient()->getOption('timeout');
        //     if (curl_multi_select($this->handle, $timeout) == -1) {
        //         usleep(1);
        //     }
        //     while (curl_multi_exec($this->handle, $running) == CURLM_CALL_MULTI_PERFORM)
        //     {}

        //     while ($info = curl_multi_info_read($this->handle)) {
        //         if ($info['msg'] == CURLMSG_DONE) {
        //             $done[(int) $info['handle']] = true;
        //         }
        //     }
        //     $aaa[$resourceId] = curl_multi_getcontent($resource);
        // }

        $active = null;
        do {
            $mrc = curl_multi_exec($this->handle, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($this->handle) == -1) {
                usleep(1);
            }

            do {
                $mrc = curl_multi_exec($this->handle, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }

        foreach ($this->handles as $handle) {
            curl_multi_remove_handle($this->handle, $handle);
        }
        curl_multi_close($this->handle);
    }
}
