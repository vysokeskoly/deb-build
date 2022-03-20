<?php declare(strict_types=1);

namespace VysokeSkoly\Build;

/**
 * Task to assemble package version based on build environment variables.
 *
 * PackageVersionerTrait is creating package version for example: 2017.05.09.15.57.32-30.g9ea62a3bc
 * This branch versioner will produce: 2017.05.09-30.PRA-666rc1 or 2017.05.09-30.master or 2017.05.09-30.feature-VS-1337
 * e.g. it is appending branch or tag (depends on GIT_BRANCH env variable) to human description
 */
trait BranchPackageVersionerTrait
{
    protected function assemblePackageVersion(bool $isDevBuild): string
    {
        return sprintf(
            '%s%s',
            ($isDevBuild ? '0~' : ''),
            date('Y.m.d')
        );
    }

    protected function assembleVersionIteration(int $revisionLength = 9): string
    {
        $jenkinsBuildNumber = getenv('BUILD_NUMBER');
        if (!$jenkinsBuildNumber) {
            throw new \RuntimeException('BUILD_NUMBER environment variable is empty, are you building on Jenkins?');
        }

        $jenkinsGitBranchOrTag = getenv('GIT_BRANCH');
        if (!$jenkinsGitBranchOrTag) {
            throw new \RuntimeException('GIT_BRANCH environment variable is empty, are you building on Jenkins?');
        }

        // replace all "/" in git branch name with -
        $jenkinsGitBranchOrTag = str_replace('/', '-', $jenkinsGitBranchOrTag);

        return sprintf(
            '%s.%s',
            $jenkinsBuildNumber,
            $jenkinsGitBranchOrTag
        );
    }
}
