<?php

declare(strict_types=1);

namespace SimpleSAML\Module\authYubiKey\Auth\Process;

use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth;
use SimpleSAML\Logger;

use function array_keys;
use function implode;
use function sprintf;
use function strlen;
use function substr;

/*
 * Copyright (C) 2009  Simon Josefsson <simon@yubico.com>.
 *
 * This file is part of SimpleSAMLphp
 *
 * SimpleSAMLphp is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public License
 * as published by the Free Software Foundation; either version 3 of
 * the License, or (at your option) any later version.
 *
 * SimpleSAMLphp is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License License along with GNU SASL Library; if not, write to the
 * Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA 02110-1301, USA.
 *
 */

/**
 * A processing filter to replace the 'otp' attribute with an attribute
 * 'yubiPrefix' that contains the static YubiKey prefix.
 *
 * Before:
 *   otp=ekhgjhbctrgnubeeklijcibbgjnbtjlffdnjbhjluvur
 *
 * After:
 *   otp undefined
 *   yubiPrefix=ekhgjhbctrgn
 *
 * You use it by adding it as an authentication filter in config.php:
 *
 *      'authproc.idp' => array(
 *    ...
 *          90 => 'authYubiKey:OTP2YubiPrefix',
 *    ...
 *      );
 *
 */

class OTP2YubiPrefix extends Auth\ProcessingFilter
{
    /**
     * Filter out YubiKey 'otp' attribute and replace it with
     * a 'yubiPrefix' attribute that leaves out the dynamic part.
     *
     * @param array &$state  The state we should update.
     */
    public function process(array &$state): void
    {
        Assert::keyExists($state, 'Attributes');
        $attributes = $state['Attributes'];

        Logger::debug('OTP2YubiPrefix: enter with attributes: ' . implode(',', array_keys($attributes)));

        $otps = $attributes['otp'];
        $otp = $otps['0'];

        $token_size = 32;
        $identity = substr($otp, 0, strlen($otp) - $token_size);

        $attributes['yubiPrefix'] = [$identity];

        Logger::info(sprintf(
            'OTP2YubiPrefix: otp: %s identity: %s (otp keys: %s)',
            $otp,
            $identity,
            implode(',', array_keys($otps)),
        ));

        unset($attributes['otp']);

        Logger::debug(sprintf(
            'OTP2YubiPrefix: leaving with attributes: %s',
            implode(',', array_keys($attributes)),
        ));
    }
}
