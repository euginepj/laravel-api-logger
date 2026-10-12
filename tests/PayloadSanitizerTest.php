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

        $sanitized = PayloadSanitizer::clean($payload);

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

        $sanitized = PayloadSanitizer::clean($payload);

        $this->assertEquals('********', $sanitized['data']['user']['card_number']);
        $this->assertEquals('user@example.com', $sanitized['data']['user']['email']);
    }
}
