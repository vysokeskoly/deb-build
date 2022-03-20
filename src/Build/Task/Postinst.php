<?php declare(strict_types=1);

namespace VysokeSkoly\Build\Task;

use Robo\Result;
use Robo\Task\BaseTask;

/**
 * Task to generate postinst shell script, which invokes `install:deb-postinst` Robo task
 */
class Postinst extends BaseTask
{
    private string $method = 'install:deb-postinst';
    /** @var string[] */
    private array $args = [];

    /**
     * @param string $targetDir Where the postinst script will be written
     * @param string $packageInstallDir From which directory will the postinst task be run
     */
    public function __construct(
        private string $packageName,
        private string $targetDir,
        private string $packageInstallDir
    ) {
        if ($targetDir[0] !== '/') {
            throw new \RuntimeException(
                'Target directory where to place the postinst file must be absolute.'
                . ' You may use path to the temporary build directory.'
            );
        }
    }

    public function method(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @param string[] $args
     */
    public function args(array $args): self
    {
        $this->args = $args;

        return $this;
    }

    public function run(): Result
    {
        $postinstTargetPath = $this->targetDir . '/' . $this->packageName . '_postinst.sh';
        $packageInstallDir = '/' . trim($this->packageInstallDir, '/') . '/';

        $roboArguments = [$this->method];
        $roboArguments = array_merge($roboArguments, $this->args);

        $escapedRoboArguments = implode(' ', array_map('escapeshellarg', $roboArguments));

        $robo = 'bin/robo';

        $postinstContent =
            '#!/usr/bin/env bash' . "\n" .
            'cd ' . $packageInstallDir . "\n" .
            sprintf('%s %s', $robo, $escapedRoboArguments);

        $bytesWritten = file_put_contents($postinstTargetPath, $postinstContent);
        if ($bytesWritten === false) {
            return Result::error(
                $this,
                'Error writing postinst to {fileFullPath}.',
                ['fileFullPath' => $postinstTargetPath]
            );
        }

        $this->printTaskSuccess('Postinst written to file {fileFullPath}.', ['fileFullPath' => $postinstTargetPath]);

        return Result::success($this, '', ['path' => $postinstTargetPath]);
    }
}
