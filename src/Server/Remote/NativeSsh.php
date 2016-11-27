<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server\Remote;

use Deployer\Server\Configuration;
use Deployer\Server\ServerInterface;
use Deployer\Server\RecursiveUploadEnabledInterface;
use Symfony\Component\Process\Process;

class NativeSsh implements ServerInterface, RecursiveUploadEnabledInterface
{
    /**
     * @var array
     */
    private $mkdirs = [];

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        /* No persistent connection is used */
    }

    /**
     * {@inheritdoc}
     */
    public function run($command)
    {
        $serverConfig = $this->getConfiguration();
        $sshOptions = [
            '-A',
            '-q',
            '-o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no'
        ];

        $username = $serverConfig->getUser() ? $serverConfig->getUser() : null;
        if (!empty($username)) {
            $username = $username . '@';
        }
        $hostname = $serverConfig->getHost();

        if ($serverConfig->getPort()) {
            $sshOptions[] = '-p ' . escapeshellarg($serverConfig->getPort());
        }

        if ($serverConfig->getPrivateKey()) {
            $sshOptions[] = '-i ' . escapeshellarg($serverConfig->getPrivateKey());
        }

        $sshCommand = 'ssh ' . implode(' ', $sshOptions) . ' ' . escapeshellarg($username . $hostname) . ' ' . escapeshellarg($command);

        $process = new Process($sshCommand);
        $process
            ->setTimeout(null)
            ->setIdleTimeout(null)
            ->mustRun();

        return $process->getOutput();
    }

    /**
     * {@inheritdoc}
     */
    public function upload($local, $remote)
    {
        $serverConfig = $this->getConfiguration();

        $username = $serverConfig->getUser() ? $serverConfig->getUser() : null;
        $hostname = $serverConfig->getHost();

        $dir = dirname($remote);

        if (!in_array($dir, $this->mkdirs)) {
            $this->run('mkdir -p ' . escapeshellarg($dir));
            $this->mkdirs[] = $dir;
        }

        return $this->scpCopy($local, (!empty($username) ? $username . '@' : '') . $hostname . ':' . $remote);
    }

    /**
     * {@inheritdoc}
     */
    public function download($local, $remote)
    {
        $serverConfig = $this->getConfiguration();

        $username = $serverConfig->getUser() ? $serverConfig->getUser() : null;
        $hostname = $serverConfig->getHost();

        return $this->scpCopy((!empty($username) ? $username . '@' : '') . $hostname . ':' . $remote, $local, ['-r']);
    }

    /**
     * {@inheritdoc}
     */
    public function uploadDirectory($local, $remote)
    {
        $serverConfig = $this->getConfiguration();

        $username = $serverConfig->getUser() ? $serverConfig->getUser() : null;
        $hostname = $serverConfig->getHost();

        return $this->scpCopy($local, (!empty($username) ? $username . '@' : '') . $hostname . ':' . $remote, ['-r']);
    }

    /**
     * Copy file from target1 to target 2 via scp
     * @param string $target
     * @param string $target2
     * @param array $scpOptions
     * @return string
     */
    public function scpCopy($target, $target2, array $scpOptions = [])
    {
        $serverConfig = $this->getConfiguration();

        if ($serverConfig->getPort()) {
            $scpOptions[] = '-P ' . escapeshellarg($serverConfig->getPort());
        }

        if ($serverConfig->getPrivateKey()) {
            $scpOptions[] = '-i ' . escapeshellarg($serverConfig->getPrivateKey());
        }

        $scpCommand = 'scp ' . implode(' ', $scpOptions) . ' ' . escapeshellarg($target) . ' ' . escapeshellarg($target2);

        $process = new Process($scpCommand);
        $process
            ->setTimeout(null)
            ->setIdleTimeout(null)
            ->mustRun();

        return $process->getOutput();
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }
}
