<?php

namespace Tests\Unit;

use App\Models\JobModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * TC — Job queue đơn giản: push, reserve, complete, failed, release
 */
class JobModelTest extends CIUnitTestCase
{
    protected JobModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new JobModel();
        \Config\Database::connect()->table('jobs')->truncate();
    }

    protected function tearDown(): void
    {
        \Config\Database::connect()->table('jobs')->truncate();
        parent::tearDown();
    }

    public function testPushAndReserve(): void
    {
        $id = $this->model->push('email', ['to' => 'a@example.com']);
        $this->assertGreaterThan(0, $id);

        $reserved = $this->model->reserve('email');
        $this->assertCount(1, $reserved);
        $this->assertNotNull($reserved[0]->reserved_at);
    }

    public function testReserveOnlyAvailableJobs(): void
    {
        $this->model->push('email', ['to' => 'now@example.com']);
        $this->model->push('email', ['to' => 'future@example.com'], 3600);

        $reserved = $this->model->reserve('email');
        $this->assertCount(1, $reserved);
        $this->assertSame('now@example.com', json_decode($reserved[0]->payload, true)['to']);
    }

    public function testMarkCompleted(): void
    {
        $id = $this->model->push('email', ['to' => 'a@example.com']);
        $this->assertTrue($this->model->markCompleted($id));
        $this->assertNull($this->model->find($id));
    }

    public function testMarkFailedAndRelease(): void
    {
        $id = $this->model->push('email', ['to' => 'a@example.com']);
        $this->model->reserve('email');

        $this->assertTrue($this->model->markFailed($id, 'SMTP error'));
        $job = $this->model->find($id);
        $this->assertNotNull($job->failed_at);
        $this->assertNull($job->reserved_at);

        // Sau khi failed, reserve không lấy lại được
        $this->assertCount(0, $this->model->reserve('email'));
    }

    public function testReleaseIncrementsAttempts(): void
    {
        $id = $this->model->push('email', ['to' => 'a@example.com'], 0, 3);
        $this->model->reserve('email');

        $this->assertTrue($this->model->release($id));
        $job = $this->model->find($id);
        $this->assertSame(1, (int) $job->attempts);
        $this->assertNull($job->reserved_at);
    }
}
