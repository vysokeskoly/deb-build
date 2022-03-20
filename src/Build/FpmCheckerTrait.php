<?php declare(strict_types=1);

namespace VysokeSkoly\Build;

/**
 * @see \Robo\Task\Base\loadTasks::taskExec()
 * @method \Robo\Task\Base\Exec taskExec(string|\Robo\Contract\CommandInterface $command)
 *
 * @see \Robo\Common\IO::io()
 * @method \Symfony\Component\Console\Style\SymfonyStyle io()
 *
 * @see \Robo\Common\IO::say()
 * @method void say(string $text)
 */
trait FpmCheckerTrait
{
    protected function checkFpmIsInstalled(): bool
    {
        if (!$this->taskExec('command -v fpm')->run()->wasSuccessful()) {
            $this->io()->error('fpm not installed, cannot build the deb package');

            return false;
        }

        $this->say('fpm installed, starting the deb build');

        return true;
    }
}
