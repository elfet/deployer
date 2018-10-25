<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Support;

class Unix
{
    /**
     * Parse "~" symbol from path.
     *
     * @throws \RuntimeException
     */
    public static function parseHomeDir(string $path): string
    {
        if (isset($_SERVER['HOME'])) {
            return str_replace('~', $_SERVER['HOME'], $path);
        } elseif (isset($_SERVER['HOMEDRIVE'], $_SERVER['HOMEPATH'])) {
            return str_replace('~', $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'], $path);
        }

        throw new \RuntimeException('Missing server parameters "HOME" or "HOMEDRIVE && HOMEPATH"');
    }
}
