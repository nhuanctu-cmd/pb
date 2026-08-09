<?php

namespace Tests\Unit;

use App\Services\MediaService;
use App\Models\MediaFileModel;
use CodeIgniter\Files\File;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * TC — MediaService: upload file ảnh tạo thumbnail, sanitize, xóa
 */
class MediaServiceTest extends CIUnitTestCase
{
    protected MediaService $service;
    protected int $tenantId = 1;
    protected bool $createdTenant = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MediaService();
        $db = \Config\Database::connect();
        // The media service correctly enforces the tenant foreign key; create
        // the temporary tenant required by this isolated test fixture.
        if (! $db->table('tenants')->where('id', $this->tenantId)->countAllResults()) {
            $db->table('tenants')->insert([
                'id'     => $this->tenantId,
                'code'   => 'TEST99',
                'name'   => 'Media Test Tenant',
                'status' => 'active',
            ]);
            $this->createdTenant = true;
        }
        $db->table('media_files')->where('tenant_id', $this->tenantId)->delete();
    }

    protected function tearDown(): void
    {
        // Xóa file test nếu có
        foreach (glob(FCPATH . 'uploads/*/*/*') ?: [] as $f) {
            if (str_contains($f, '_test_')) {
                @unlink($f);
            }
        }
        $db = \Config\Database::connect();
        $db->table('media_files')->where('tenant_id', $this->tenantId)->delete();
        if ($this->createdTenant) {
            $db->table('tenants')->where('id', $this->tenantId)->delete();
        }
        parent::tearDown();
    }

    private function createImageFile(): File
    {
        $tmpPath = sys_get_temp_dir() . '/media_test_' . uniqid() . '.png';
        $img = imagecreatetruecolor(400, 300);
        imagepng($img, $tmpPath);
        imagedestroy($img);
        return new File($tmpPath, true);
    }

    public function testUploadImageCreatesRecordAndThumbnail(): void
    {
        $file = $this->createImageFile();
        $result = $this->service->upload($file, $this->tenantId, 1);

        $this->assertNotNull($result);
        $this->assertSame($this->tenantId, (int) $result->tenant_id);
        $this->assertStringContainsString('uploads/', $result->file_path);
        $this->assertFileExists(FCPATH . $result->file_path);

        // Thumbnail tồn tại
        $thumbPath = dirname(FCPATH . $result->file_path) . '/thumb_' . basename($result->file_path);
        $this->assertFileExists($thumbPath);
    }

    public function testGetByTenant(): void
    {
        $file = $this->createImageFile();
        $this->service->upload($file, $this->tenantId, 1);

        $media = $this->service->getByTenant($this->tenantId, 'image');
        $this->assertCount(1, $media);

        $mediaAll = $this->service->getByTenant($this->tenantId);
        $this->assertCount(1, $mediaAll);
    }

    public function testDeleteSoftDeletes(): void
    {
        $file = $this->createImageFile();
        $result = $this->service->upload($file, $this->tenantId, 1);

        $this->service->delete((int) $result->id);

        $this->assertNull((new MediaFileModel())->find($result->id));
    }
}
