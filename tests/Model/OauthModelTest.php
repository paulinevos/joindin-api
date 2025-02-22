<?php

namespace Joindin\Api\Test\Model;

use Exception;
use Joindin\Api\Model\OAuthModel;
use Joindin\Api\Request;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class OauthModelTest extends TestCase
{
    public function setup(): void
    {
        $this->pdo              = $this->getMockBuilder(PDO::class)
                                       ->disableOriginalConstructor()
                                       ->getMock();
        $this->request          = $this->getMockBuilder(Request::class)
                                       ->disableOriginalConstructor()
                                       ->getMock();
        $this->request->base    = "";
        $this->request->version = "2.1";

        $this->oauth = new OAuthModel($this->pdo, $this->request);
    }

    public function testWrongPasswordReturnsFalse()
    {
        $stmt = $this->getMockBuilder(PDOStatement::class)->getMock();
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn(
            [
                'password' => md5('password1'),
                'verified' => 1,
            ]
        );
        $this->pdo->method('prepare')->willReturn($stmt);
        $this->assertFalse(
            $this->oauth->createAccessTokenFromPassword("client", "testing", "password")
        );
    }

    public function testWrongUserThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Not verified');
        $this->expectExceptionCode(401);

        $stmt = $this->getMockBuilder(PDOStatement::class)->getMock();
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn(
            [
                'verified' => 0,
            ]
        );

        $this->pdo->method('prepare')->willReturn($stmt);

        $this->oauth->createAccessTokenFromPassword("client", "testing", "password");
    }

    public function testLoggingInWorks()
    {
        $pass = 'password';
        $stmt = $this->getMockBuilder(PDOStatement::class)->getMock();
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn(
            [
                'password' => password_hash(md5($pass), PASSWORD_BCRYPT),
                'verified' => 1,
                'ID'       => 1234,
            ]
        );
        $this->pdo->method('prepare')->willReturn($stmt);
        $token = $this->oauth->createAccessTokenFromPassword("client", "testing", $pass);

        $this->assertArrayHasKey('access_token', $token);
        $this->assertArrayHasKey('user_uri', $token);
    }
}
