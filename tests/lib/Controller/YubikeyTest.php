<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\authYubiKey\Controller;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Module\authYubiKey\Auth\Source\YubiKey;
use SimpleSAML\Module\authYubiKey\Controller;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * Set of tests for the controllers in the "authYubiKey" module.
 *
 * @covers \SimpleSAML\Module\authYubiKey\Controller\Yubikey
 */
class YubikeyTest extends TestCase
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;

    /** @var \SimpleSAML\Session */
    protected Session $session;


    /**
     * Set up for each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->config = Configuration::loadFromArray(
            [
                'module.enable' => ['authYubiKey' => true],
            ],
            '[ARRAY]',
            'simplesaml'
        );

        $this->session = Session::getSessionFromRequest();

        Configuration::setPreLoadedConfig($this->config, 'config.php');
    }


    /**
     * Test that accessing the otp-endpoint without state results in an error-response
     *
     * @return void
     */
    public function testOtpNoState(): void
    {
        $request = Request::create(
            '/login',
            'GET'
        );

        $c = new Controller\Yubikey($this->config, $this->session);

        $this->expectException(Error\BadRequest::class);
        $this->expectExceptionMessage("BADREQUEST('%REASON%' => 'Missing AuthState parameter.')");

        $c->main($request);
    }


    /**
     * Test that accessing the otp-endpoint without otp results in a Template
     *
     * @return void
     */
    public function testOtpNoOtp(): void
    {
        $request = Request::create(
            '/login',
            'GET',
            ['AuthState' => 'abc123']
        );

        $c = new Controller\Yubikey($this->config, $this->session);
/**
        $c->setAuthState(new class () extends Auth\State {
            public static function loadState(string $id, string $stage, bool $allowMissing = false): ?array
            {
                return [];
            }
        });
*/
        $response = $c->main($request);

        $this->assertTrue($response->isSuccessful());
        $this->assertInstanceOf(Template::class, $response);
    }


    /**
     * Test that accessing the otp-endpoint with invalid otp returns Template
     *
     * @return void
     */
    public function testWrongOtp(): void
    {
        $request = Request::create(
            '/login',
            'GET',
            ['AuthState' => 'abc123', 'otp' => 'aabbccddeeffgghhiijjkkllmmnnooppqq']
        );

        $c = new Controller\Yubikey($this->config, $this->session);
        $c->setYubikey(new class (['AuthId' => 'authYubiKey'], []) extends YubiKey {
            public function __construct(array $info, array $config) {
            }

            public static function handleLogin(string $stateId, string $otp): ?string
            {
                return 'WRONGUSERPASS';
            }
        });
        $response = $c->main($request);

        $this->assertTrue($response->isSuccessful());
        $this->assertInstanceOf(Template::class, $response);
    }
}
