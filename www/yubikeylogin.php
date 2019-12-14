<?php

/**
 * This page shows a username/password login form, and passes information from it
 * to the \SimpleSAML\Module\core\Auth\UserPassBase class, which is a generic class for
 * username/password authentication.
 *
 * @author Olav Morken, UNINETT AS.
 * @package SimpleSAMLphp
 */

use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Module;
use SimpleSAML\Module\authyubikey\Auth\Source\YubiKey;
use SimpleSAML\XHTML\Template;

if (!array_key_exists('AuthState', $_REQUEST)) {
    throw new Error\BadRequest('Missing AuthState parameter.');
}
$authStateId = $_REQUEST['AuthState'];

$globalConfig = Configuration::getInstance();
$t = new Template($globalConfig, 'authYubiKey:yubikeylogin.twig');
$translator = $t->getTranslator();

$errorCode = null;
if (array_key_exists('otp', $_REQUEST)) {
    // attempt to log in
    /** @psalm-var string $errorCode */
    $errorCode = YubiKey::handleLogin($authStateId, $_REQUEST['otp']);
    $errorCodes = Error\ErrorCodes::getAllErrorCodeMessages();
    if (array_key_exists($errorCode, $errorCodes['title'])) {
        $t->data['errorTitle'] = $errorCodes['title'][$errorCode];
    }
    if (array_key_exists($errorCode, $errorCodes['desc'])) {
        $t->data['errorDesc'] = $errorCodes['desc'][$errorCode];
    }
}

$t->data['autofocus'] = 'otp';
$t->data['errorCode'] = $errorCode;
$t->data['stateParams'] = ['AuthState' => $authStateId];
$t->send();
