<?php

declare(strict_types=1);

namespace SimpleSAML\Module\authYubikey\Controller;

use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Module\authYubikey\Auth\Source;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller class for the authyubikey module.
 *
 * This class serves the different views available in the module.
 *
 * @package simplesamlphp/simplesamlphp-module-authyubikey
 */
class Yubikey
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;

    /** @var \SimpleSAML\Session */
    protected Session $session;

    /**
     * @var \SimpleSAML\Module\authYubikey\Auth\Source\YubiKey|string
     * @psalm-var \SimpleSAML\Module\authYubikey\Auth\Source\YubiKey|class-string
     */
    protected $yubikey = Source\YubiKey::class;


    /**
     * Controller constructor.
     *
     * It initializes the global configuration and session for the controllers implemented here.
     *
     * @param \SimpleSAML\Configuration $config The configuration to use by the controllers.
     * @param \SimpleSAML\Session $session The session to use by the controllers.
     *
     * @throws \Exception
     */
    public function __construct(
        Configuration $config,
        Session $session
    ) {
        $this->config = $config;
        $this->session = $session;
    }


    /**
     * Inject the \SimpleSAML\Module\authYubikey\Auth\Source\YubiKey dependency.
     *
     * @param \SimpleSAML\Module\authYubikey\Auth\Source\YubiKey $yubikey
     */
    public function setYubikey(Source\Yubikey $yubikey): void
    {
        $this->yubikey = $yubikey;
    }


    /**
     * @param \Symfony\Component\HttpFoundation\Request $request The current request.
     * @return \SimpleSAML\XHTML\Template
     */
    public function main(Request $request): Template
    {
        $stateId = $request->get('AuthState');
        if ($stateId === null) {
            throw new Error\BadRequest('Missing AuthState parameter.');
        }

        $t = new Template($this->config, 'authYubikey:yubikeylogin.twig');

        $errorCode = null;
        $otp = $request->get('otp');
        if ($otp !== null) {
            // attempt to log in

            /** @psalm-var string $errorCode */
            $errorCode = $this->yubikey::handleLogin($stateId, $otp);
            $errorCodes = Error\ErrorCodes::getAllErrorCodeMessages();

            if (array_key_exists($errorCode, $errorCodes['title'])) {
                $t->data['errorTitle'] = $errorCodes['title'][$errorCode];
            }

            if (array_key_exists($errorCode, $errorCodes['descr'])) {
                $t->data['errorDesc'] = $errorCodes['descr'][$errorCode];
            }
        }

        $t->data['errorCode'] = $errorCode;
        $t->data['stateParams'] = ['AuthState' => $stateId];

        return $t;
    }
}
