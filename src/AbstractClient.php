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

/**
 * @package    Froq
 * @subpackage Froq\Http\Client
 * @object     Froq\Http\Client\AbstractClient
 * @author     Kerem Güneş <k-gun@mail.com>
 * @since      3.0
 */
abstract class AbstractClient
{
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
     * @var string
     */
    protected $result;

    /**
     * Result info.
     * @var array
     */
    protected $resultInfo;

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
     * Callback.
     * @var callable
     */
    protected $callback;

    /**
     * Methods.
     * @var array
     */
    private static $methods = [
        'head', 'options', 'get', 'post', 'put', 'patch', 'delete'
    ];

    /**
     * Constructor.
     * @param string|null $url
     * @param array|null  $options
     * @param array|null  $arguments
     */
    public final function __construct(string $url = null, array $options = null, array $arguments = null)
    {
        if ($url != null) {
            $this->setArgument('url', $url);
        }
        $options && $this->setOptions($options);
        $arguments && $this->setArguments($arguments);
    }

    /**
     * Call magic.
     * @param  string $method
     * @param  array  $methodArguments
     * @return void
     * @throws Froq\Http\Client\ClientException
     */
    public final function __call(string $method, array $methodArguments): void
    {
        if (!in_array($method, self::$methods)) {
            throw new ClientException(sprintf("No method '%s' found (callable methods: %s)",
                $method, join(',', self::$methods)));
        }

        $methodArguments[1] = $methodArguments[1] ?? [];
        $methodArguments[1]['method'] = $method; // request method actually
        call_user_func_array([$this, 'send'], $methodArguments);
    }

    /**
     * Get request.
     * @return ?Froq\Http\Client\Request
     */
    public final function getRequest(): ?Request
    {
        return $this->request;
    }

    /**
     * Get response.
     * @return ?Froq\Http\Client\Response
     */
    public final function getResponse(): ?Response
    {
        return $this->response;
    }

    /**
     * Get result.
     * @return ?string
     */
    public final function getResult(): ?string
    {
        return $this->result;
    }

    /**
     * Get result info.
     * @param  string|null $key
     * @return any|null
     */
    public final function getResultInfo(string $key = null)
    {
        return ($key === null) ? $this->resultInfo : $this->resultInfo[$key] ?? null;
    }

    /**
     * Set options.
     * @param  array $options
     * @return self
     */
    public final function setOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * Get options.
     * @return array
     */
    public final function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Set option.
     * @param string $name
     * @param any    $value
     */
    public final function setOption(string $name, $value): self
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * Get option.
     * @param  string $name
     * @return any|null
     */
    public final function getOption(string $name)
    {
        return $this->options[$name] ?? null;
    }

    /**
     * Set arguments.
     * @param  array $arguments
     * @return self
     */
    public final function setArguments(array $arguments): self
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
    public final function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Set argument.
     * @param  string $name
     * @param  any    $value
     * @return self
     */
    public final function setArgument(string $name, $value): self
    {
        return $this->setArguments([$name => $value]);
    }

    /**
     * Get argument.
     * @param  string $name
     * @return any
     */
    public final function getArgument(string $name)
    {
        return $this->getArguments()[$name] ?? null;
    }

    /**
     * Set callback.
     * @param  callable $callback
     * @return self
     */
    public final function setCallback(callable $callback): self
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * Get callback
     * @return ?callable
     */
    public final function getCallback(): ?callable
    {
        return $this->callback;
    }

    /**
     * Get methods.
     * @return array
     */
    public final function getMethods(): array
    {
        return self::$methods;
    }

    /**
     * Add header.
     * @param  string  $name
     * @param  ?string $value
     * @return self
     */
    public function addHeader(string $name, ?string $value): self
    {
        return $this->setArgument('headers', [$name => $value]);
    }

    /**
     * Set user agent.
     * @param  ?string $userAgent Null allows to remove User-Agent header.
     * @return self
     */
    public final function setUserAgent(?string $userAgent): self
    {
        return $this->addHeader('User-Agent', $userAgent);
    }

    /**
     * Set authorization.
     * @param ?string $type
     * @param string  $credentials
     * @param bool    $encodeBasicCredentials
     */
    public final function setAuthorization(?string $type, string $credentials = '',
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
    public final function ok(): bool
    {
        return ($this->response->getStatusCode() == 200);
    }

    /**
     * Is success.
     * @return bool
     */
    public final function isSucces(): bool
    {
        return ($statusCode = $this->response->getStatusCode())
            && ($statusCode >= 200 && $statusCode <= 299);
    }

    /**
     * Is failure.
     * @return bool
     */
    public final function isFailure(): bool
    {
        return ($this->response->getStatusCode() >= 400);
        // return ($statusCode = $this->response->getStatusCode())
        //     && (($statusCode >= 400 && $statusCode <= 499) ||
        //         ($statusCode >= 500 && $statusCode <= 599));
    }

    /**
     * Is redirect.
     * @return bool
     */
    public final function isRedirect(): bool
    {
        return ($statusCode = $this->response->getStatusCode())
            && ($statusCode >= 300 && $statusCode <= 399);
    }

    /**
     * Reset.
     * @return void
     */
    public final function reset(): void
    {
        $this->request = new Request();
        $this->response = new Response();

        $this->result = $this->resultInfo = null;
    }

    /**
     * Send.
     * @param  string        $url
     * @param  array|null    $arguments
     * @param  callable|null $callback
     * @return void
     */
    abstract public function send(string $url, array $arguments = null, callable $callback = null): void;
}
