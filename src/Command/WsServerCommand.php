<?php

namespace Swoft\WebSocket\Server\Command;

use Swoft\Console\Annotation\Mapping\Command;
use Swoft\Console\Annotation\Mapping\CommandMapping;
use Swoft\Console\Annotation\Mapping\CommandOption;
use Swoft\Console\Helper\Show;
use Swoft\Helper\EnvHelper;
use Swoft\Server\Server;
use Swoft\WebSocket\Server\WebSocketServer;

/**
 * Class WsServerCommand
 * @Command("ws",
 *     coroutine=false,
 *     alias="ws-server,wsserver",
 *     desc="provide some commands to operate WebSocket Server"
 * )
 */
class WsServerCommand
{
    /**
     * Start the webSocket server
     *
     * @CommandMapping(
     *     usage="{fullCommand} [-d|--daemon]",
     *     example="{fullCommand}\n{fullCommand} -d"
     * )
     * @CommandOption("daemon", short="d", desc="Run server on the background")
     *
     * @throws \ReflectionException
     * @throws \Swoft\Bean\Exception\ContainerException
     * @throws \Swoft\Server\Exception\ServerException
     */
    public function start(): void
    {
        $server = $this->createServer();

        // Check if it has started
        if ($server->isRunning()) {
            $masterPid = $server->getPid();
            \output()->writeln("<error>The server have been running!(PID: {$masterPid})</error>");
            return;
        }

        // Startup settings
        $this->configStartOption($server);

        $settings = $server->getSetting();
        // Setting
        $workerNum = $settings['worker_num'];

        // Server startup parameters
        $mainHost = $server->getHost();
        $mainPort = $server->getPort();
        $modeName = $server->getModeName();
        $typeName = $server->getTypeName();

        // TCP 启动参数
        // $tcpStatus = $server->getTcpSetting();

        Show::panel([
            'WebSocket' => [
                'listen' => $mainHost . ':' . $mainPort,
                'type'   => $typeName,
                'mode'   => $modeName,
                'worker' => $workerNum,
            ],
        ]);

        \output()->writef('<success>Server start success !</success>');

        // Start the server
        $server->start();
    }

    /**
     * Reload worker processes
     *
     * @CommandMapping(usage="{fullCommand} [-t]")
     * @CommandOption("t", desc="Only to reload task processes, default to reload worker and task")
     *
     * @throws \ReflectionException
     * @throws \Swoft\Bean\Exception\ContainerException
     */
    public function reload(): void
    {
        $server = $this->createServer();
        $script = \input()->getScript();

        // Check if it has started
        if (!$server->isRunning()) {
            \output()->writeln('<error>The server is not running! cannot reload</error>');
            return;
        }

        \output()->writef('<info>Server %s is reloading</info>', $script);

        if ($reloadTask = input()->hasOpt('t')) {
            Show::notice('Will only reload task worker');
        }

        if (!$server->reload($reloadTask)) {
            Show::error('The swoole server worker process reload fail!');
            return;
        }

        \output()->writef('<success>Server %s reload success</success>', $script);
    }

    /**
     * Stop the currently running server
     *
     * @CommandMapping()
     *
     * @throws \ReflectionException
     * @throws \Swoft\Bean\Exception\ContainerException
     */
    public function stop(): void
    {
        $server = $this->createServer();

        // Check if it has started
        if (!$server->isRunning()) {
            \output()->writeln('<error>The server is not running! cannot stop.</error>');
            return;
        }

        // Do stopping.
        $server->stop();
    }

    /**
     * Restart the http server
     *
     * @CommandMapping(
     *     usage="{fullCommand} [-d|--daemon]",
     *     example="
     * {fullCommand}
     * {fullCommand} -d"
     * )
     * @CommandOption("daemon", short="d", desc="Run server on the background")
     *
     * @throws \ReflectionException
     * @throws \Swoft\Bean\Exception\ContainerException
     */
    public function restart(): void
    {
        $server = $this->createServer();

        // Restart server
        $server->restart();
    }

    /**
     * @return WebSocketServer
     * @throws \ReflectionException
     * @throws \Swoft\Bean\Exception\ContainerException
     */
    private function createServer(): WebSocketServer
    {
        // check env
        // EnvHelper::check();

        // http server初始化
        $script = input()->getScript();

        $server = \bean('wsServer');
        $server->setScriptFile($script);

        return $server;
    }

    /**
     * 设置启动选项，覆盖配置选项
     *
     * @param Server $server
     */
    protected function configStartOption(Server $server): void
    {
        $asDaemon = \input()->getSameOpt(['d', 'daemon'], false);

        if ($asDaemon) {
            $server->setDaemonize();
        }
    }
}
