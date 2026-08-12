<?php

namespace Tests\Unit;

use App\Services\WebhookService;
use CodeIgniter\Test\CIUnitTestCase;

class WebhookServiceTest extends CIUnitTestCase
{
    public function testSignatureIsStableAndUsesHmacSha256(): void
    {
        $payload = '{"id":7,"status":"paid"}';
        $signature = WebhookService::sign($payload, 'test-secret-123456');
        $this->assertSame(hash_hmac('sha256', $payload, 'test-secret-123456'), $signature);
        $this->assertNotSame($signature, WebhookService::sign($payload, 'other-secret-123456'));
    }

    public function testEndpointUrlAndEventsAreRestricted(): void
    {
        $this->assertTrue(WebhookService::isSafeUrl('https://partner.example/hooks'));
        $this->assertFalse(WebhookService::isSafeUrl('http://partner.example/hooks'));
        $this->assertTrue(WebhookService::validEvents(['booking.created', '*']));
        $this->assertFalse(WebhookService::validEvents(['booking.created', 'private.secret']));
    }

    public function testSecretIsEncryptedAtRestAndCanBeRecovered(): void
    {
        $ciphertext = WebhookService::encryptSecret('test-secret-123456');
        $this->assertNotSame('test-secret-123456', $ciphertext);
        $this->assertSame('test-secret-123456', WebhookService::decryptSecret($ciphertext));
    }
}
