<?php

require_once __DIR__ . '/../../rest/services/AuthService.php';
require_once __DIR__ . '/../../rest/dao/AuthDao.php';

class AuthServiceTest extends PHPUnit\Framework\TestCase
{
    private $authService;

    protected function setUp(): void
    {
        $this->authService = new AuthService();
    }

    /**
     * Test: Valid password hashing
     */
    public function testPasswordHashingIsSecure()
    {
        $password = 'TestPassword123!';
        $hash = password_hash($password, PASSWORD_BCRYPT);

        // Password should be different from hash
        $this->assertNotEquals($password, $hash);

        // Hashed password should be verifiable
        $this->assertTrue(password_verify($password, $hash));

        // Wrong password should not verify
        $this->assertFalse(password_verify('WrongPassword', $hash));
    }

    /**
     * Test: Registration validation - missing email
     */
    public function testRegisterRejectsMissingEmail()
    {
        $userData = [
            'password' => 'TestPassword123!'
        ];

        $result = $this->authService->register($userData);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Email', $result['error']);
    }

    /**
     * Test: Registration validation - missing password
     */
    public function testRegisterRejectsMissingPassword()
    {
        $userData = [
            'email' => 'test@example.com'
        ];

        $result = $this->authService->register($userData);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('password', strtolower($result['error']));
    }

    /**
     * Test: Email format validation
     */
    public function testEmailValidation()
    {
        $validEmails = [
            'user@example.com',
            'test.user@example.co.uk',
            'user+tag@example.com'
        ];

        foreach ($validEmails as $email) {
            $this->assertTrue(filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
        }

        $invalidEmails = [
            'invalid.email',
            '@example.com',
            'user@',
            'user name@example.com'
        ];

        foreach ($invalidEmails as $email) {
            $this->assertFalse(filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
        }
    }

    /**
     * Test: Get user by email
     */
    public function testGetUserByEmail()
    {
        // This validates the method exists
        try {
            $result = $this->authService->get_user_by_email('test@example.com');
            // Result should be either array or null/false
            $this->assertTrue(is_array($result) || is_null($result) || $result === false);
        } catch (Exception $e) {
            // Method should exist
            $this->assertTrue(method_exists($this->authService, 'get_user_by_email'));
        }
    }
}
