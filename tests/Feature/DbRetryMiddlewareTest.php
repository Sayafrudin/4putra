<?php

namespace Tests\Feature;

use App\Http\Middleware\RetryDbConnection;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PDOException;
use Tests\TestCase;

class DbRetryMiddlewareTest extends TestCase
{
    private function sslException(): QueryException
    {
        return new QueryException(
            'mysql',
            'select * from sessions where id = ? limit 1',
            [],
            new PDOException('PDO::__construct(): SSL: An established connection was aborted by the software in your host machine')
        );
    }

    public function test_retries_once_when_connection_transiently_fails(): void
    {
        $middleware = new RetryDbConnection;
        $calls = 0;

        $response = $middleware->handle(new Request, function () use (&$calls) {
            $calls++;
            if ($calls === 1) {
                throw $this->sslException();
            }

            return new Response('OK');
        });

        $this->assertSame(2, $calls);
        $this->assertSame('OK', $response->getContent());
    }

    public function test_rethrows_when_retry_fails_again(): void
    {
        $middleware = new RetryDbConnection;
        $calls = 0;

        try {
            $middleware->handle(new Request, function () use (&$calls) {
                $calls++;
                throw $this->sslException();
            });
            $this->fail('QueryException harus dilempar ulang.');
        } catch (QueryException $e) {
            $this->assertSame(2, $calls);
        }
    }

    public function test_does_not_retry_non_connection_errors(): void
    {
        $middleware = new RetryDbConnection;
        $calls = 0;

        try {
            $middleware->handle(new Request, function () use (&$calls) {
                $calls++;
                throw new QueryException(
                    'mysql',
                    'insert into users values (?)',
                    [],
                    new PDOException('SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry')
                );
            });
            $this->fail('QueryException harus dilempar ulang.');
        } catch (QueryException $e) {
            $this->assertSame(1, $calls);
        }
    }

    public function test_global_stack_still_serves_public_and_session_requests(): void
    {
        $this->get('/up')->assertStatus(200);
        $this->get('/collections')->assertStatus(200);
    }
}
