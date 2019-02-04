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

namespace Froq\Http\Client;

use Froq\Http\Client\Agent\Agent;

/**
 * @package    Froq
 * @subpackage Froq\Http\Client
 * @object     Froq\Http\Client\Client
 * @author     Kerem Güneş <k-gun@mail.com>
 * @since      3.0
 */
final class Client
{
    /**
     * Async.
     * @var bool
     */
    protected $async;

    /**
     * Request.
     * @var Froq\Http\Client\Request
     */
    protected $request;

    /**
     * Response.
     * @var Froq\Http\Client\Response
     */
    protected $response;

    /**
     * Result.
     * @var ?string
     */
    protected $result;

    /**
     * Result info.
     * @var ?array
     */
    protected $resultInfo;

    /**
     * Callback.
     * @var ?callable
     */
    protected $callback;

    /**
     * Options.
     * @var array
     */
    protected $options = [
        'redir' => true, 'redirMax' => 3,
        'timeout' => 5,  'timeoutConnect' => 3,
    ];

    /**
     * Arguments.
     * @var array
     */
    protected $arguments = [
        'method' => 'GET'
    ];

    /**
     * Methods.
     * @var array
     */
    private static $methods = [
        'head', 'options', 'get', 'post', 'put', 'patch', 'delete'
    ];

    /**
     * Error.
     * @var ?Froq\Http\Client\ClientError
     */
    protected $error;

    /**
     * Agent.
     * @var Froq\Http\Client\Agent\Agent
     */
    protected $agent;

    /**
     * Constructor.
     * @param string        $url
     * @param array|null    $options
     * @param array|null    $arguments
     * @param callable|null $callback
     */
    public function __construct(string $url, array $options = null, array $arguments = null,
        callable $callback = null)
    {
        $this->setUrl($url);
        $options   && $this->setOptions($options);
        $arguments && $this->setArguments($arguments);
        $callback  && $this->setCallback($callback);
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->agent = null;
    }

    /**
     * Call magic.
     * @param  string $func
     * @param  array  $funcArgs
     * @return Froq\Http\Client\Response
     * @throws Froq\Http\Client\ClientException
     */
    public function __call(string $func, array $funcArgs): Response
    {
        $method = $func;
        if (!in_array($method, self::$methods)) {
            throw new ClientException(sprintf("No method '%s' found (callable methods: %s)",
                $method, join(',', self::$methods)));
        }

        $this->setMethod($method);

        return $this->send();
    }

    /**
     * Async.
     * @param  bool|null $option
     * @return bool
     */
    public function async(bool $option = null): bool
    {
        if ($option !== null) {
            $this->async = $option;
        }

        return !!$this->async;
    }

    /**
     * Get request.
     * @return ?Froq\Http\Client\Request
     */
    public function getRequest(): ?Request
    {
        return $this->request;
    }

    /**
     * Get response.
     * @return ?Froq\Http\Client\Response
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }

    /**
     * Get result.
     * @return ?string
     */
    public function getResult(): ?string
    {
        return $this->result;
    }

    /**
     * Get result info.
     * @param  string|null $key
     * @return any|null
     */
    public function getResultInfo(string $key = null)
    {
        return ($key === null) ? $this->resultInfo : $this->resultInfo[$key] ?? null;
    }

    /**
     * Set callback.
     * @param  callable $callback
     * @return self
     */
    public function setCallback(callable $callback): self
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * Get callback
     * @return ?callable
     */
    public function getCallback(): ?callable
    {
        // using $this in callback?
        // if ($this->callback != null) {
        //     $this->callback = \Closure::bind($this->callback, $this);
        // }

        return $this->callback;
    }

    /**
     * Set options.
     * @param  array $options
     * @return self
     */
    public function setOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * Get options.
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Set option.
     * @param string $name
     * @param any    $value
     */
    public function setOption(string $name, $value): self
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * Get option.
     * @param  string $name
     * @return any|null
     */
    public function getOption(string $name)
    {
        return $this->options[$name] ?? null;
    }

    /**
     * Set arguments.
     * @param  array $arguments
     * @return self
     */
    public function setArguments(array $arguments): self
    {
        foreach ($arguments as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $subName => $subValue) {
                    $this->arguments[$name][$subName] = $subValue;
                }
            } else { $this->arguments[$name] = $value; }
        }

        return $this;
    }

    /**
     * Get arguments.
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Set argument.
     * @param  string $name
     * @param  any    $value
     * @return self
     */
    public function setArgument(string $name, $value): self
    {
        return $this->setArguments([$name => $value]);
    }

    /**
     * Get argument.
     * @param  string $name
     * @return any|null
     */
    public function getArgument(string $name)
    {
        return $this->arguments[$name] ?? null;
    }

    /**
     * Get methods.
     * @return array
     */
    public function getMethods(): array
    {
        return self::$methods;
    }

    /**
     * Is error.
     * @return bool
     */
    public function isError(): bool
    {
        return !!$this->error;
    }

    /**
     * Get error.
     * @return ?Froq\Http\Client\ClientError
     */
    public function getError(): ?ClientError
    {
        return $this->error;
    }

    /**
     * Set agent.
     * @param  Froq\Http\Client\Agent\Agent $agent
     * @return self
     */
    public function setAgent(Agent $agent): self
    {
        $this->agent = $agent;

        return $this;
    }

    /**
     * Get agent.
     * @return ?Froq\Http\Client\Agent\Agent
     */
    public function getAgent(): ?Agent
    {
        return $this->agent;
    }

    /**
     * Add header.
     * @param  string  $name
     * @param  ?string $value
     * @return self
     */
    public function addHeader(string $name, ?string $value): self
    {
        if (strtolower($name) == 'host') {
            throw new ClientException('You cannot set Host header');
        }

        return $this->setArgument('headers', [$name => $value]);
    }

    /**
     * Set url.
     * @param  string $url
     * @return self
     */
    public function setUrl(string $url): self
    {
        return $this->setArgument('url', $url);
    }

    /**
     * Set method.
     * @param  string $method
     * @return self
     */
    public function setMethod(string $method): self
    {
        return $this->setArgument('method', $method);
    }

    /**
     * Set user agent.
     * @param  ?string $userAgent Null allows to remove User-Agent header.
     * @return self
     */
    public function setUserAgent(?string $userAgent): self
    {
        return $this->addHeader('User-Agent', $userAgent);
    }

    /**
     * Set authorization.
     * @param ?string $type
     * @param string  $credentials
     * @param bool    $encodeBasicCredentials
     */
    public function setAuthorization(?string $type, string $credentials = '',
        bool $encodeBasicCredentials = true): self
    {
        if ($type != '') {
            // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Authorization#Directives
            $authorization = $type .' '. ($encodeBasicCredentials && strtolower($type) == 'basic'
                ? base64_encode($credentials) : $credentials);
        } elseif ($credentials != '') {
            $authorization = $credentials;
        }

        return $this->addHeader('Authorization', $authorization ?? null);
    }

    /**
     * Ok.
     * @return bool
     */
    public function ok(): bool
    {
        return ($this->response->getStatusCode() === 200);
    }

    /**
     * Is success.
     * @return bool
     */
    public function isSucces(): bool
    {
        return ($statusCode = $this->response->getStatusCode())
            && ($statusCode >= 200 && $statusCode <= 299);
    }

    /**
     * Is failure.
     * @return bool
     */
    public function isFailure(): bool
    {
        return ($this->response->getStatusCode() >= 400);
        // nope..
        // return ($statusCode = $this->response->getStatusCode())
        //     && (($statusCode >= 400 && $statusCode <= 499) ||
        //         ($statusCode >= 500 && $statusCode <= 599));
    }

    /**
     * Is redirect.
     * @return bool
     */
    public function isRedirect(): bool
    {
        return ($statusCode = $this->response->getStatusCode())
            && ($statusCode >= 300 && $statusCode <= 399);
    }

    /**
     * Process pre-send.
     * @return void
     */
    public function processPreSend(): void
    {
        // could be given in constructor
        $url = $this->getArgument('url');
        if ($url == null) {
            throw new ClientException('No valid url given');
        }

        [$url, $urlParams] = Util::parseUrl($url);
        $this->request->setUrl($url)
                      ->setUrlParams($urlParams);

        $arguments = $this->getArguments();
        if (!empty($arguments)) {
            $this->request->setMethod($arguments['method']);

            if (isset($arguments['headers'])) {
                $this->request->setHeaders($arguments['headers']);
            }
            if (isset($arguments['urlParams'])) {
                $this->request->setUrlParams($arguments['urlParams']);
            }

            // body accepted for all methods..
            // @see https://stackoverflow.com/questions/978061/http-get-with-request-body
            $body = $arguments['body'] ?? null;
            if ($body !== null) {
                $rawBody = $body;
                $bodyType = gettype($body);
                if ($bodyType == 'array' || $bodyType == 'object') {
                    $contentType = (string) $this->request->getHeader('Content-Type');
                    $rawBody = strpos($contentType, '/json') || strpos($contentType, '+json')
                        ? Util::jsonEncode($rawBody, $arguments['jsonOptions'] ?? [])
                        : Util::buildQuery($rawBody);
                }

                $this->request->setBody($body)
                              ->setRawBody($rawBody);
            }
        }
    }

    /**
     * Process post-send.
     * @param  array $arguments
     * @return void
     */
    public function processPostSend(array $arguments): void
    {
        [$result, $resultInfo, $error] = $arguments;
        if ($error) {
            $this->error = $error;
        } else {
            $this->result = $result;
            $this->resultInfo = $resultInfo;

            // set request headers
            $headers =@ $this->resultInfo['request_header'];
            if ($headers != null) {
                $this->request->setHeaders(Util::parseHeaders($headers, false));
            }

            $result = explode("\r\n\r\n", $this->result);
            // drop redirect etc. headers
            while (count($result) > 2) {
                array_shift($result);
            }

            // split headers/body parts
            @ [$headers, $body] = $result;

            if ($headers != null) {
                $headers = Util::parseHeaders($headers);
                if (isset($headers[0])) {
                    $this->response->setStatus($headers[0]);
                }

                $this->response->setHeaders($headers);
            }

            if ($body != null) {
                $rawBody = $body;
                $contentEncoding = $this->response->getHeader('Content-Encoding');
                $contentType = (string) $this->response->getHeader('Content-Type');

                // decode gzip (if zipped)
                if ($contentEncoding == 'gzip' || (strpos($contentType, '/octet-stream')
                    && substr($this->request->getUrl(), -3) == '.gz')) {
                    $body = $rawBody = gzdecode($body);
                }

                // decode json
                if (strpos($contentType, '/json') || strpos($contentType, '+json')) {
                    $body = Util::jsonDecode($body, $arguments['jsonOptions'] ?? []);
                } elseif (strpos($contentType, '/xml')) {
                    $body = Util::parseXml($body, $arguments['xmlOptions'] ?? []);
                }

                $this->response->setBody($body)
                               ->setRawBody($rawBody);
            }
        }
    }

    /**
     * Reset.
     * @return void
     */
    public function reset(): void
    {
        $this->request = new Request();
        $this->response = new Response();

        $this->result = $this->resultInfo = $this->error = null;
    }

    /**
     * Send.
     * @return Froq\Http\Client\Response
     */
    public function send(): Response
    {
        return MessageEmitter::send($this);
    }

    /**
     * Send async.
     * @return Froq\Http\Client\Response[]
     */
    public function sendAsync(): array
    {
        return MessageEmitter::sendAsync([$this]);
    }
}
