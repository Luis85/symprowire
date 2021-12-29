<?php

namespace Symprowire;

use Symprowire\Exception\SymprowireExecutionException;
use Symprowire\Exception\SymprowireNotExecutedException;
use Symprowire\Exception\SymprowireNotReadyException;
use Symprowire\Interfaces\SymprowireInterface;
use Exception;
use ProcessWire\ProcessWire;
use Symprowire\Engine\SymprowireRuntime;

/**
 * Symprowire - a PHP MVC Framework for ProcessWire
 * ------------------------------------------------
 *
 * This Class is mainly responsible to execute the Kernel with a given ProcessWire instance.
 * Execution will return the processed Kernel with attached Request and Response
 * To get the processed Response Data as a string, just call the ::render()
 *
 */
class Symprowire implements SymprowireInterface
{

    public const ENGINE = 'Symprowire';
    public const VERSION = '0.1.0';

    protected Kernel $kernel;
    protected array $params;
    protected string $projectDir;
    protected bool $ready = false;
    protected bool $executed = false;

    /**
     * TODO: add the native ProcessWire File Renderer as option
     *
     * @param array $params
     */
    public function __construct(array $params = []) {
        $this->params = [
            'renderer' => 'twig',
            'test' => false,
            'disable_dotenv' => true,
        ];
        $this->params = array_merge($this->params, $params);
        $this->projectDir = dirname(__DIR__, 1);
        $this->ready = true;
    }

    /**
     * TODO: add the native ProcessWire File Renderer as option
     *
     * Create a Symprowire callable from the Symprowire/Kernel, injecting ProcessWire and create a new Runtime
     * Resolve the SymprowireKernel, set env arguments, execute and get the created Response
     * we send our Kernel as callable to the runtime and execute the Kernel
     * the called Symprowire/Runner will handle the callable Kernel and attach the result to the Runner
     *
     *
     * @throws SymprowireExecutionException
     */
    public function execute(ProcessWire $processWire = null): Kernel {

        $params = $this->params;
        $params['project_dir'] = $this->projectDir;

        if($processWire instanceof ProcessWire) {
            $params['project_dir'] = $processWire->config->paths->root . 'site';
        }

        try {

            $app = function () use($processWire, $params) {
                return new Kernel($processWire , $params);
            };
            $runtime = new SymprowireRuntime($params);
            [$app, $args] = $runtime->getResolver($app)->resolve();
            $app = $app(...$args);
            $runtime->getRunner($app)->run();
            $this->kernel = $runtime->getExecutedRunner()->getKernel();
            $this->executed = true;

            /**
             * We return the executed Kernel to let the Developer handle the Response
             */
            return $this->kernel;

        } catch(Exception $exception) {
            throw new SymprowireExecutionException('Symprowire Execution Failed', 200, $exception);
        }
    }

    /**
     *
     * @return string
     * @throws SymprowireNotReadyException
     * @throws SymprowireNotExecutedException
     */
    public function render(): string {
        if(!$this->ready) throw new SymprowireNotReadyException('Symprowire is not ready yet. Maybe construction failed silently', 201);
        if(!$this->executed) throw new SymprowireNotExecutedException('Symprowire is not executed yet. Try to call $symprowire->execute() first', 202);
        return $this->kernel->getResponse()->getContent();
    }

    /**
     * @return bool
     */
    public function isReady(): bool {
        return $this->ready;
    }

    /**
     * @return bool
     */
    public function isExecuted(): bool {
        return $this->executed;
    }
}