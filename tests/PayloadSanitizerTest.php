<?php

namespace Euginepj\ApiLogger\Tests;

use Euginepj\ApiLogger\Support\PayloadSanitizer;

class PayloadSanitizerTest extends TestCase
{
    /** @test */
    public function it_masks_sensitive_keys_in_payload()
    {
        $payload = [
            'username' => 'testuser',
            'password' => 'secret123',
            'token' => 'bearer-abc',
        ];

        $sanitized = PayloadSanitizer::cleanPayload($payload);

        $this->assertEquals('testuser', $sanitized['username']);
        $this->assertEquals('********', $sanitized['password']);
        $this->assertEquals('********', $sanitized['token']);
    }

    /** @test */
    public function it_masks_nested_sensitive_keys()
    {
        $payload = [
            'data' => [
                'user' => [
                    'card_number' => '4111111111111111',
                    'email' => 'user@example.com',
                ]
            ]
        ];

        $sanitized = PayloadSanitizer::cleanPayload($payload, ['card_number']);

        $this->assertEquals('********', $sanitized['data']['user']['card_number']);
        $this->assertEquals('user@example.com', $sanitized['data']['user']['email']);
    }

    /** @test */
    public function it_sanitizes_sensitive_headers()
    {
        $headers = [
            'authorization' => ['Bearer secret-token'],
            'content-type' => ['application/json'],
        ];

        $sanitized = PayloadSanitizer::cleanHeaders($headers);

        $this->assertEquals(['********'], $sanitized['authorization']);
        $this->assertEquals(['application/json'], $sanitized['content-type']);
    }

    /** @test */
    public function it_formats_and_truncates_response_body()
    {
        $content = str_repeat('A', 100);
        $formatted = PayloadSanitizer::formatResponseBody($content, 50);

        $this->assertStringContainsString('[TRUNCATED', $formatted);
    }
}
