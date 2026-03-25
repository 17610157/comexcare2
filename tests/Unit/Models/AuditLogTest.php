<?php

namespace Tests\Unit\Models;

use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_audit_log(): void
    {
        $auditLog = AuditLog::create([
            'user_id' => 1,
            'action' => 'login',
            'endpoint' => '/login',
            'method' => 'POST',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'request_data' => ['email' => 'test@example.com'],
            'response_code' => 200,
            'duration_ms' => 150.50,
            'created_at' => now(),
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'login',
            'endpoint' => '/login',
            'method' => 'POST',
        ]);
    }

    public function test_request_data_is_cast_to_array(): void
    {
        $auditLog = AuditLog::create([
            'user_id' => 1,
            'action' => 'view',
            'endpoint' => '/dashboard',
            'method' => 'GET',
            'ip_address' => '127.0.0.1',
            'request_data' => ['plaza' => 'PLAZA1'],
            'response_code' => 200,
            'created_at' => now(),
        ]);

        $this->assertIsArray($auditLog->request_data);
        $this->assertEquals('PLAZA1', $auditLog->request_data['plaza']);
    }

    public function test_duration_ms_is_cast_to_decimal(): void
    {
        $auditLog = AuditLog::create([
            'user_id' => 1,
            'action' => 'sync',
            'endpoint' => '/sync',
            'method' => 'POST',
            'ip_address' => '127.0.0.1',
            'response_code' => 200,
            'duration_ms' => '123.45',
            'created_at' => now(),
        ]);

        $this->assertEquals('123.45', $auditLog->duration_ms);
    }

    public function test_scope_for_user(): void
    {
        AuditLog::create([
            'user_id' => 1,
            'action' => 'login',
            'endpoint' => '/login',
            'method' => 'POST',
            'response_code' => 200,
            'created_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => 2,
            'action' => 'logout',
            'endpoint' => '/logout',
            'method' => 'POST',
            'response_code' => 200,
            'created_at' => now(),
        ]);

        $user1Logs = AuditLog::forUser(1)->get();

        $this->assertCount(1, $user1Logs);
        $this->assertEquals(1, $user1Logs->first()->user_id);
    }

    public function test_scope_for_action(): void
    {
        AuditLog::create([
            'user_id' => 1,
            'action' => 'login',
            'endpoint' => '/login',
            'method' => 'POST',
            'response_code' => 200,
            'created_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => 1,
            'action' => 'view',
            'endpoint' => '/dashboard',
            'method' => 'GET',
            'response_code' => 200,
            'created_at' => now(),
        ]);

        $loginLogs = AuditLog::forAction('login')->get();

        $this->assertCount(1, $loginLogs);
        $this->assertEquals('login', $loginLogs->first()->action);
    }

    public function test_scope_for_endpoint(): void
    {
        AuditLog::create([
            'user_id' => 1,
            'action' => 'view',
            'endpoint' => '/admin/users',
            'method' => 'GET',
            'response_code' => 200,
            'created_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => 1,
            'action' => 'view',
            'endpoint' => '/admin/roles',
            'method' => 'GET',
            'response_code' => 200,
            'created_at' => now(),
        ]);

        $adminLogs = AuditLog::forEndpoint('admin')->get();

        $this->assertCount(2, $adminLogs);
    }

    public function test_scope_successful(): void
    {
        AuditLog::create([
            'user_id' => 1,
            'action' => 'login',
            'endpoint' => '/login',
            'method' => 'POST',
            'response_code' => 200,
            'created_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => 1,
            'action' => 'login',
            'endpoint' => '/login',
            'method' => 'POST',
            'response_code' => 422,
            'created_at' => now(),
        ]);

        $successful = AuditLog::successful()->get();

        $this->assertCount(1, $successful);
    }

    public function test_scope_failed(): void
    {
        AuditLog::create([
            'user_id' => 1,
            'action' => 'login',
            'endpoint' => '/login',
            'method' => 'POST',
            'response_code' => 200,
            'created_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => 1,
            'action' => 'login',
            'endpoint' => '/login',
            'method' => 'POST',
            'response_code' => 404,
            'created_at' => now(),
        ]);

        $failed = AuditLog::failed()->get();

        $this->assertCount(1, $failed);
    }

    public function test_scope_slow(): void
    {
        AuditLog::create([
            'user_id' => 1,
            'action' => 'sync',
            'endpoint' => '/sync',
            'method' => 'POST',
            'response_code' => 200,
            'duration_ms' => 500,
            'created_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => 1,
            'action' => 'view',
            'endpoint' => '/dashboard',
            'method' => 'GET',
            'response_code' => 200,
            'duration_ms' => 50,
            'created_at' => now(),
        ]);

        $slow = AuditLog::slow(100)->get();

        $this->assertCount(1, $slow);
    }

    public function test_user_relationship(): void
    {
        $user = \App\Models\User::factory()->create();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'login',
            'endpoint' => '/login',
            'method' => 'POST',
            'response_code' => 200,
            'created_at' => now(),
        ]);

        $auditLog = AuditLog::first();

        $this->assertNotNull($auditLog->user);
        $this->assertEquals($user->id, $auditLog->user->id);
    }
}
