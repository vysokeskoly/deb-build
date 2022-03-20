<?php declare(strict_types=1);

namespace VysokeSkoly\Build;

/**
 * Task to assemble package version based on build environment variables.
 */
trait PackageVersionerTrait
{
    protected function assemblePackageVersion(bool $isDevBuild): string
    {
        return sprintf(
            '%s%s',
            ($isDevBuild ? '0~' : ''),
            date('Y.m.d.H.i.s')
        );
    }

    protected function assembleVersionIteration(int $revisionLength = 9): string
    {
        $jenkinsBuildNumber = getenv('BUILD_NUMBER');
        if (!$jenkinsBuildNumber) {
            throw new \RuntimeException('BUILD_NUMBER environment variable is empty, are you building on Jenkins?');
        }

        $gitShortCommit = $this->retrieveGitShortCommit($revisionLength);

        return sprintf(
            '%s.g%s',
            $jenkinsBuildNumber,
            $gitShortCommit
        );
    }

    protected function retrieveGitShortCommit(int $length = 9): string
    {
        return mb_substr(getenv('GIT_COMMIT'), 0, $length);
    }
}
