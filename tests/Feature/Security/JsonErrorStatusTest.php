<?php

namespace Tests\Feature\Security;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * JSON requests (the old pages' axios calls, API clients) get the status that fits the failure. These exceptions
 * have no status code of their own, so they came back as 500s - and, not being bugs, weren't reported either.
 */
class JsonErrorStatusTest extends TestCase
{
    public function provider(): array
    {
        return [
            'not logged in' => [new AuthenticationException(), 401],
            'not allowed' => [new AuthorizationException(), 403],
            'no such record' => [new ModelNotFoundException(), 404],
            'session expired with the page open' => [new TokenMismatchException('CSRF token mismatch.'), 419],
        ];
    }

    /**
     * @dataProvider provider
     */
    public function testStatus(\Throwable $exception, int $status): void
    {
        $this->withExceptionHandling();
        Route::middleware('web')->post('/_json_status_test', fn () => throw $exception);

        $this->postJson('/_json_status_test')->assertStatus($status);
    }
}
