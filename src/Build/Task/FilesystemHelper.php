<?php declare(strict_types=1);

namespace VysokeSkoly\Build\Task;

use Robo\Task\CommandStack;

/**
 * Task to encapsulate common file operations run directly using shell commands.
 */
class FilesystemHelper extends CommandStack
{
    /**
     * Set recursively rights for files to 644 and directories to 755
     *
     * @param string $relativeDir Relative path from defined working dir (see `dir()` method)
     */
    public function chmodRecursivelyWritableByUserReadableByOthers(string $relativeDir = '.'): self
    {
        return $this->exec(['chmod', '-R', 'u+rwX,go+rX', $relativeDir]);
    }

    /**
     * Set recursively rights for files in directory.
     * If you need to set rights 644 to files and 755 to dirs, use chmodRecursivelyWritableByUserReadableByOthers(),
     * which is much faster.
     */
    public function chmodFilesRecursively(string $fileRights): self
    {
        return $this->exec(['find', '.', '-type f', '-exec chmod ' . $fileRights . ' {} \;']);
    }

    /**
     * Set recursively rights for directories in directory.
     * If you need to set rights 644 to files and 755 to dirs, use chmodRecursivelyWritableByUserReadableByOthers(),
     * which is much faster.
     */
    public function chmodDirsRecursively(string $directoryRights): self
    {
        return $this->exec(['find', '.', '-type d', '-exec chmod ' . $directoryRights . ' {} \;']);
    }

    /**
     * Recursively remove directories named by given pattern
     *
     * @param string $relativeDir Relative base path from defined working dir (see `dir()` method)
     */
    public function removeDirsRecursively(string $directoriesNamePattern, string $relativeDir = '.'): self
    {
        return $this->exec(
            [
                'find',
                $relativeDir,
                '-name "' . $directoriesNamePattern . '"',
                '-type d',
                '-prune',
                '-exec rm -rf {} \;',
            ]
        );
    }
}
