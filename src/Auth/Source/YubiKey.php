<?php

declare(strict_types=1);

namespace SimpleSAML\Module\authYubiKey\Auth\Source;

use GuzzleHttp\Client as GuzzleClient;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth;
use SimpleSAML\Error;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Utils;
use Surfnet\YubikeyApiClient\Crypto\RandomNonceGenerator;
use Surfnet\YubikeyApiClient\Crypto\Signer;
use Surfnet\YubikeyApiClient\Http\ServerPoolClient;
use Surfnet\YubikeyApiClient\Otp;
use Surfnet\YubikeyApiClient\Service\VerificationService;

/**
 * YubiKey authentication module, see http://www.yubico.com/developers/intro/
 *
 * Configure it by adding an entry to config/authsources.php such as this:
 *
 *    'yubikey' => [
 *        'authYubiKey:YubiKey',
 *        'id' => 997,
 *        'key' => 'b64hmackey',
 *    ],
 *
 * To generate your own client id/key you will need one YubiKey, and then
 * go to http://yubico.com/developers/api/
 *
 * @package simplesamlphp/simplesamlphp-module-authyubikey
 */

class YubiKey extends Auth\Source
{
    /**
     * The string used to identify our states.
     */
    public const STAGEID = '\SimpleSAML\Module\authYubiKey\Auth\Source\YubiKey.state';

    /**
     * The number of characters of the OTP that is the secure token.
     * The rest is the user id.
     */
    public const TOKENSIZE = 32;

    /**
     * The key of the AuthId field in the state.
     */
    public const AUTHID = '\SimpleSAML\Module\authYubiKey\Auth\Source\YubiKey.AuthId';

    /**
     * The client id/key for use with the Auth_Yubico PHP module.
     * @var string
     */
    private $yubi_id;

    /** @var string */
    private $yubi_key;


    /**
     * Constructor for this authentication source.
     *
     * @param array $info  Information about this authentication source.
     * @param array $config  Configuration.
     */
    public function __construct(array $info, array $config)
    {
        // Call the parent constructor first, as required by the interface
        parent::__construct($info, $config);

        Assert::keyExists($config, 'id', Error\ConfigurationError::class);
        Assert::keyExists($config, 'key', Error\ConfigurationError::class);

        $this->yubi_id = $config['id'];
        $this->yubi_key = $config['key'];
    }


    /**
     * Initialize login.
     *
     * This function saves the information about the login, and redirects to a
     * login page.
     *
     * @param array &$state  Information about the current authentication.
     */
    public function authenticate(array &$state): void
    {
        // We are going to need the authId in order to retrieve this authentication source later
        $state[self::AUTHID] = $this->authId;

        $id = Auth\State::saveState($state, self::STAGEID);
        $url = Module::getModuleURL('authYubiKey/login');
        $httpUtils = new Utils\HTTP();
        $httpUtils->redirectTrustedURL($url, ['AuthState' => $id]);
    }


    /**
     * Handle login request.
     *
     * This function is used by the login form (core/www/loginuserpass.php) when the user
     * enters a username and password. On success, it will not return. On wrong
     * username/password failure, it will return the error code. Other failures will throw an
     * exception.
     *
     * @param string $authStateId  The identifier of the authentication state.
     * @param string $otp  The one time password entered-
     * @return string|void Error code in the case of an error.
     */
    public static function handleLogin(
        string $authStateId,
        #[\SensitiveParameter]
        string $otp,
    ) {
        /* Retrieve the authentication state. */
        $state = Auth\State::loadState($authStateId, self::STAGEID);
        if (is_null($state)) {
            throw new Error\NoState();
        }

        /* Find authentication source. */
        Assert::keyExists($state, self::AUTHID);

        $source = Auth\Source::getById($state[self::AUTHID]);
        Assert::isInstanceOf(
            $source,
            YubiKey::class,
            'Could not find authentication source with id ' . $state[self::AUTHID]
        );

        try {
            /**
             * Attempt to log in.
             *
             * @var \SimpleSAML\Module\authYubiKey\Auth\Source\YubiKey $source
             */
            $attributes = $source->login($otp);
        } catch (Error\Error $e) {
            /* An error occurred during login. Check if it is because of the wrong
             * username/password - if it is, we pass that error up to the login form,
             * if not, we let the generic error handler deal with it.
             */
            if ($e->getErrorCode() === 'WRONGUSERPASS') {
                return 'WRONGUSERPASS';
            }

            /* Some other error occurred. Rethrow exception and let the generic error
             * handler deal with it.
             */
            throw $e;
        }

        $state['Attributes'] = $attributes;
        Auth\Source::completeAuth($state);

        assert(false);
    }


    /**
     * Return the user id part of a one time passord
     *
     * @param string $otp
     * @return string
     */
    public static function getYubiKeyPrefix(#[\SensitiveParameter] string $otp): string
    {
        $uid = substr($otp, 0, strlen($otp) - self::TOKENSIZE);
        return $uid;
    }


    /**
     * Attempt to log in using the given username and password.
     *
     * On a successful login, this function should return the users attributes. On failure,
     * it should throw an exception. If the error was caused by the user entering the wrong
     * username or password, a \SimpleSAML\Error\Error('WRONGUSERPASS') should be thrown.
     *
     * Note that both the username and the password are UTF-8 encoded.
     *
     * @param string $otp
     * @return array Associative array with the users attributes.
     */
    protected function login(#[\SensitiveParameter] string $userInputOtp): array
    {
        $service = new VerificationService(
            new ServerPoolClient(new GuzzleClient()),
            new RandomNonceGenerator(),
            new Signer($this->yubi_key),
            $this->yubi_id
        );

        if (!Otp::isValid($userInputOtp)) {
            throw new Error\Exception('User-entered OTP string is not valid.');
        }

        $otp = Otp::fromString($userInputOtp);
        $result = $service->verify($otp);

        if ($result->isSuccessful()) {
            // Yubico verified OTP.

            Logger::info(sprintf(
                'YubiKey:%s: YubiKey otp %s validated successfully: %s',
                $this->authId,
                $userInputOtp,
                $result::STATUS_OK
            ));

            $uid = self::getYubiKeyPrefix($userInputOtp);
            return ['uid' => [$uid]];
        }

        throw new Error\Error($result->getError());
    }
}
