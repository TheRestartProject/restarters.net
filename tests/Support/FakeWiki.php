<?php

namespace Tests\Support;

use App\Services\WikiHttpClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * Stands in for the wiki's API: logs users in by name and password, hands out tokens, changes passwords, and can
 * be told to turn the next few requests away as its web server's rate limit does.
 */
class FakeWiki
{
    /** @var array<string, string> wiki username => password */
    public array $passwords = [];

    /** @var array<int, array> the parameters of every request it was sent */
    public array $requests = [];

    public int $rateLimitNext = 0;

    public ?string $loggedInAs = null;

    public static function install($app): self
    {
        $wiki = new self();

        // A fresh stack each time, as the client pushes its middleware onto the one it's given.
        $app->bind(WikiHttpClient::HANDLER, fn () => HandlerStack::create(fn (RequestInterface $request) => $wiki->handle($request)));

        return $wiki;
    }

    public function handle(RequestInterface $request)
    {
        $params = $this->params($request);
        $this->requests[] = $params;

        if ($this->rateLimitNext > 0) {
            $this->rateLimitNext--;

            return Create::promiseFor(new Response(429, [], '<html><head><title>429 Too Many Requests</title></head></html>'));
        }

        return Create::promiseFor(new Response(200, ['Content-Type' => 'application/json'], json_encode($this->answer($params))));
    }

    private function answer(array $params): array
    {
        switch ($params['action'] ?? '') {
            case 'login':
                if (empty($params['lgtoken'])) {
                    return ['login' => ['result' => 'NeedToken', 'token' => 'logintoken']];
                }

                if (($this->passwords[$params['lgname']] ?? null) === $params['lgpassword']) {
                    $this->loggedInAs = $params['lgname'];

                    return ['login' => ['result' => 'Success', 'lgusername' => $params['lgname']]];
                }

                return ['login' => ['result' => 'Failed', 'reason' => 'Incorrect username or password entered. Please try again.']];
            case 'query':
                return ['query' => ['tokens' => ['csrftoken' => 'csrftoken+\\']]];
            case 'changeauthenticationdata':
                $this->passwords[$this->loggedInAs] = $params['password'];

                return ['changeauthenticationdata' => ['status' => 'success']];
        }

        return [];
    }

    private function params(RequestInterface $request): array
    {
        parse_str($request->getUri()->getQuery(), $query);
        $body = (string) $request->getBody();
        $fields = [];

        if (preg_match_all('/name="([^"]+)"\r\n(?:[^\r\n]+\r\n)*\r\n(.*?)\r\n--/s', $body, $parts, PREG_SET_ORDER)) {
            foreach ($parts as $part) {
                $fields[$part[1]] = $part[2];
            }
        } else {
            parse_str($body, $fields);
        }

        return $query + $fields;
    }
}
