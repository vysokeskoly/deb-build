<?php declare(strict_types=1);

namespace VysokeSkoly\Build\Task;

use Robo\Contract\TaskInterface;

/**
 * @see \Robo\TaskAccessor::task()
 * @method task(string $className, ...$args)
 */
trait LoadTasksTrait
{
    protected function taskBuildinfo(string $filename = 'buildinfo.xml'): TaskInterface
    {
        return $this->task(Buildinfo::class, $filename);
    }

    protected function taskFilesystemHelper(): TaskInterface
    {
        return $this->task(FilesystemHelper::class);
    }

    protected function taskPostinst(string $packageName, string $targetDir, string $packageInstallDir): TaskInterface
    {
        return $this->task(Postinst::class, $packageName, $targetDir, $packageInstallDir);
    }
}
