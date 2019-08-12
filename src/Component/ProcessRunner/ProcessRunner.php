<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Component\ProcessRunner;

use Deployer\Deployer;
use Deployer\Component\ProcessRunner\Printer;
use Deployer\Exception\RunException;
use Deployer\Host\Host;
use Deployer\Logger\Logger;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ProcessRunner
{
    private $pop;
    private $logger;

    public function __construct(Printer $pop, Logger $logger)
    {
        $this->pop = $pop;
        $this->logger = $logger;
    }

    /**
     * Runs a command, consider deployer global configs (timeout,...)
     *
     * @param Host $host
     * @param string $command
     * @param array $config
     *
     * @return string
     *
     */
    public function run(Host $host, string $command, array $config = []): string
    {
        $defaults = [
            'timeout' => $host->get('default_timeout', 300),
            'tty' => false,
        ];
        $config = array_merge($defaults, $config);

        $this->pop->command($host, $command);

        $terminalOutput = $this->pop->callback($host);
        $callback = function ($type, $buffer) use ($host, $terminalOutput) {
            $this->logger->printBuffer($host, $type, $buffer);
            $terminalOutput($type, $buffer);
        };

        try {
            $process = Process::fromShellCommandline($command);
            $process
                ->setTimeout($config['timeout'])
                ->setTty($config['tty'])
                ->mustRun($callback);

            return $process->getOutput();
        } catch (ProcessFailedException $exception) {
            $trace = debug_backtrace();
            throw new RunException(
                basename($trace[1]['file']),
                $trace[1]['line'],
                $host->alias(),
                $command,
                $process->getExitCode(),
                $process->getOutput(),
                $process->getErrorOutput()
            );
        }
    }
}
