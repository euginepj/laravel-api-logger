<?php

namespace Euginepj\ApiLogger\Tests;

use Euginepj\ApiLogger\Models\ApiRequestLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;

class ApiLogViewerControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    #[Test]
    public function it_filters_logs_by_user_id_using_hash_or_numeric(): void
    {
        ApiRequestLog::create([
            'method' => 'GET',
            'url' => 'http://localhost/api/test1',
            'path' => '/api/test1',
            'status_code' => 200,
            'duration_ms' => 50,
            'ip_address' => '127.0.0.1',
            'user_id' => 35,
            'created_at' => now(),
        ]);

        ApiRequestLog::create([
            'method' => 'GET',
            'url' => 'http://localhost/api/test2',
            'path' => '/api/test2',
            'status_code' => 200,
            'duration_ms' => 40,
            'ip_address' => '127.0.0.1',
            'user_id' => 99,
            'created_at' => now(),
        ]);

        $requestWithHash = Request::create('/admin/api-logs', 'GET', ['search' => '#35']);
        $controller = new \Euginepj\ApiLogger\Http\Controllers\ApiLogViewerController();
        $view = $controller->index($requestWithHash);
        $logs = $view->getData()['logs'];

        $this->assertCount(1, $logs);
        $this->assertEquals(35, $logs->first()->user_id);

        $requestWithoutHash = Request::create('/admin/api-logs', 'GET', ['search' => '35']);
        $view2 = $controller->index($requestWithoutHash);
        $logs2 = $view2->getData()['logs'];

        $this->assertCount(1, $logs2);
        $this->assertEquals(35, $logs2->first()->user_id);
    }

    #[Test]
    public function it_filters_logs_by_status_errors(): void
    {
        ApiRequestLog::create([
            'method' => 'GET',
            'url' => 'http://localhost/api/ok',
            'path' => '/api/ok',
            'status_code' => 200,
            'duration_ms' => 50,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        ApiRequestLog::create([
            'method' => 'GET',
            'url' => 'http://localhost/api/client-error',
            'path' => '/api/client-error',
            'status_code' => 404,
            'duration_ms' => 30,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        ApiRequestLog::create([
            'method' => 'POST',
            'url' => 'http://localhost/api/server-error',
            'path' => '/api/server-error',
            'status_code' => 500,
            'duration_ms' => 80,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $request = Request::create('/admin/api-logs', 'GET', ['status' => 'errors']);
        $controller = new \Euginepj\ApiLogger\Http\Controllers\ApiLogViewerController();
        $view = $controller->index($request);
        $logs = $view->getData()['logs'];

        $this->assertCount(2, $logs);
        $statusCodes = $logs->pluck('status_code')->all();
        $this->assertContains(404, $statusCodes);
        $this->assertContains(500, $statusCodes);
        $this->assertNotContains(200, $statusCodes);
    }
}
