<?php


namespace Deployer\Executor;

use Deployer\Deployer;
use Deployer\Exception\Exception;
use Deployer\Task\Context;
use Psr\Http\Message\ServerRequestInterface;
use React;
use React\Http\Message\Response;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use function Deployer\getHost;

class Server
{
    private $input;
    private $output;
    private $questionHelper;
    /**
     * @var React\EventLoop\LoopInterface
     */
    private $loop;
    private $port;

    public function __construct(
        InputInterface $input,
        OutputInterface $output,
        QuestionHelper $questionHelper
    )
    {
        $this->input = $input;
        $this->output = $output;
        $this->questionHelper = $questionHelper;
    }

    public function start()
    {
        $this->loop = React\EventLoop\Factory::create();
        $server = new React\Http\Server($this->loop, function (ServerRequestInterface $request) {
            try {
                return $this->router($request);
            } catch (Throwable $exception) {
                Deployer::printException($this->output, $exception);
                return new React\Http\Message\Response(500, ['Content-Type' => 'text/plain'], 'Master error: ' . $exception->getMessage());
            }
        });
        $socket = new React\Socket\Server(0, $this->loop);
        $server->listen($socket);
        $address = $socket->getAddress();
        $this->port = parse_url($address, PHP_URL_PORT);
    }

    private function router(ServerRequestInterface $request): Response
    {
        $path = $request->getUri()->getPath();
        switch ($path) {
            case '/load':
                ['host' => $host] = json_decode($request->getBody(), true);

                $host = getHost($host);
                $config = json_encode($host->config()->persist());

                return new Response(200, ['Content-Type' => 'application/json'], $config);

            case '/save':
                ['host' => $host, 'config' => $config] = json_decode($request->getBody(), true);

                $host = getHost($host);
                $host->config()->update($config);

                return new Response(200, ['Content-Type' => 'application/json'], 'true');

            case '/proxy':
                ['host' => $host, 'func' => $func, 'arguments' => $arguments] = json_decode($request->getBody(), true);

                Context::push(new Context(getHost($host), $this->input, $this->output));
                $answer = call_user_func($func, ...$arguments);
                Context::pop();

                return new Response(200, ['Content-Type' => 'application/json'], json_encode($answer));

            default:
                throw new Exception('Server path not found: ' . $request->getUri()->getPath());
        }
    }

    public function addPeriodicTimer($interval, $callback)
    {
        $this->loop->addPeriodicTimer($interval, $callback);
    }

    public function addTimer($interval, $callback)
    {
        $this->loop->addTimer($interval, $callback);
    }

    public function cancelTimer($timer)
    {
        $this->loop->cancelTimer($timer);
    }

    public function run()
    {
        $this->loop->run();
    }

    public function stop()
    {
        $this->loop->stop();
    }

    public function getPort(): int
    {
        return $this->port;
    }
}
