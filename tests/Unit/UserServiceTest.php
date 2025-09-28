<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\Api\V1\UserService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userService = new UserService;
    }

    public function test_find_existing_user_by_email(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'phone' => '081234567890',
        ]);

        $foundUser = $this->userService->findOrCreateByEmailOrPhone('existing@example.com', null);

        $this->assertEquals($existingUser->id, $foundUser->id);
        $this->assertEquals('existing@example.com', $foundUser->email);
    }

    public function test_find_existing_user_by_phone(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'test@example.com',
            'phone' => '081234567890',
        ]);

        $foundUser = $this->userService->findOrCreateByEmailOrPhone(null, '081234567890');

        $this->assertEquals($existingUser->id, $foundUser->id);
        $this->assertEquals('081234567890', $foundUser->phone);
    }

    public function test_find_existing_user_by_email_with_phone_provided(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'phone' => '081111111111',
        ]);

        // Should find by email even if phone is different
        $foundUser = $this->userService->findOrCreateByEmailOrPhone('existing@example.com', '082222222222');

        $this->assertEquals($existingUser->id, $foundUser->id);
        $this->assertEquals('existing@example.com', $foundUser->email);
    }

    public function test_create_new_user_with_email_and_phone(): void
    {
        $user = $this->userService->findOrCreateByEmailOrPhone('new@example.com', '081234567890', 'John Doe');

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('new@example.com', $user->email);
        $this->assertEquals('081234567890', $user->phone);
        $this->assertEquals('John Doe', $user->name);
        $this->assertTrue($user->is_active);
        $this->assertNotNull($user->password);

        // Should be persisted in database
        $this->assertDatabaseHas('users', [
            'email' => 'new@example.com',
            'phone' => '081234567890',
            'name' => 'John Doe',
            'is_active' => true,
        ]);
    }

    public function test_create_new_user_with_email_only(): void
    {
        $user = $this->userService->findOrCreateByEmailOrPhone('email-only@example.com', null, 'Email User');

        $this->assertEquals('email-only@example.com', $user->email);
        $this->assertNull($user->phone);
        $this->assertEquals('Email User', $user->name);
        $this->assertTrue($user->is_active);
    }

    public function test_create_new_user_with_phone_only(): void
    {
        $user = $this->userService->findOrCreateByEmailOrPhone(null, '081234567890', 'Phone User');

        $this->assertNull($user->email);
        $this->assertEquals('081234567890', $user->phone);
        $this->assertEquals('Phone User', $user->name);
        $this->assertTrue($user->is_active);
    }

    public function test_create_new_user_with_auto_generated_name(): void
    {
        $user = $this->userService->findOrCreateByEmailOrPhone('auto-name@example.com', null);

        $this->assertEquals('auto-name@example.com', $user->email);
        $this->assertStringStartsWith('User ', $user->name);
        $this->assertEquals(11, strlen($user->name)); // "User " + 6 random chars
    }

    public function test_throws_exception_when_no_email_or_phone_provided(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Either email or phone must be provided');

        $this->userService->findOrCreateByEmailOrPhone(null, null);
    }

    public function test_throws_exception_when_empty_email_and_phone_provided(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Either email or phone must be provided');

        $this->userService->findOrCreateByEmailOrPhone('', '');
    }

    public function test_user_has_random_password_when_created(): void
    {
        $user1 = $this->userService->findOrCreateByEmailOrPhone('user1@example.com', null);
        $user2 = $this->userService->findOrCreateByEmailOrPhone('user2@example.com', null);

        // Passwords should be different (extremely unlikely to be the same with 16 random chars)
        $this->assertNotEquals($user1->password, $user2->password);

        // Passwords should be hashed
        $this->assertNotEmpty($user1->password);
        $this->assertStringStartsWith('$2y$', $user1->password); // bcrypt prefix
    }
}
