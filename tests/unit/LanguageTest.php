<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Testcase chuẩn ngôn ngữ: file lang vi là gốc, en phải khớp key,
 * tiếng Việt phải có dấu (chứa ký tự Unicode tiếng Việt).
 */
class LanguageTest extends CIUnitTestCase
{
    private string $langPath = APPPATH . 'Language';

    /** TC18: Mọi file lang vi bắt buộc phải tồn tại ở en và ngược lại */
    public function testViAndEnFilesMatch(): void
    {
        $viFiles = array_map('basename', glob($this->langPath . '/vi/*.php'));
        $enFiles = array_map('basename', glob($this->langPath . '/en/*.php'));

        $this->assertNotEmpty($viFiles, 'Chưa có file lang tiếng Việt nào');

        foreach ($viFiles as $file) {
            $this->assertContains($file, $enFiles, "Thiếu bản dịch EN cho file: {$file}");
        }
    }

    /** TC19: Key trong vi/en của cùng 1 file phải khớp nhau */
    public function testLangKeysMatchBetweenViAndEn(): void
    {
        foreach (glob($this->langPath . '/vi/*.php') as $viFile) {
            $file   = basename($viFile);
            $enFile = $this->langPath . '/en/' . $file;

            if (! is_file($enFile)) {
                continue; // đã được kiểm tra ở TC18
            }

            $viKeys = array_keys(require $viFile);
            $enKeys = array_keys(require $enFile);

            $missingInEn = array_diff($viKeys, $enKeys);
            $this->assertSame(
                [],
                $missingInEn,
                "File en/{$file} thiếu key: " . implode(', ', $missingInEn)
            );
        }
    }

    /** TC20: Các chuỗi tiếng Việt phải có dấu (ít nhất 1 ký tự Unicode VN) */
    public function testVietnameseStringsHaveDiacritics(): void
    {
        $viAuth = require $this->langPath . '/vi/Auth.php';
        $viApp  = require $this->langPath . '/vi/App.php';

        $pattern = '/[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ]/iu';

        $hasDiacritics = 0;
        foreach (array_merge($viAuth, $viApp) as $value) {
            if (is_string($value) && preg_match($pattern, $value)) {
                $hasDiacritics++;
            }
        }

        $this->assertGreaterThan(0, $hasDiacritics, 'File lang tiếng Việt không có dấu');
    }

    /** TC21: Các key Auth bắt buộc cho trang login tồn tại */
    public function testRequiredAuthKeysExist(): void
    {
        $vi = require $this->langPath . '/vi/Auth.php';

        foreach (['loginTitle', 'email', 'password', 'login', 'invalidCredentials', 'loginSuccess', 'demoAccounts'] as $key) {
            $this->assertArrayHasKey($key, $vi, "Thiếu key bắt buộc: Auth.{$key}");
            $this->assertNotEmpty($vi[$key]);
        }
    }
}
