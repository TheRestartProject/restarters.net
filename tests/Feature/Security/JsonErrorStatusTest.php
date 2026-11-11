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
            // Class names: data sets are serialised under process isolation, and exceptions can't be.
            'not logged in' => [AuthenticationException::class, 401],
            'not allowed' => [AuthorizationException::class, 403],
            'no such record' => [ModelNotFoundException::class, 404],
            'session expired with the page open' => [TokenMismatchException::class, 419],
        ];
    }

    /**
     * @dataProvider provider
     */
    public function testStatus(string $exception, int $status): void
    {
        $this->withExceptionHandling();
        Route::middleware('web')->post('/_json_status_test', fn () => throw new $exception());

        $this->postJson('/_json_status_test')->assertStatus($status);
    }
}
