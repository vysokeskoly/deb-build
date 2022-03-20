<?php declare(strict_types=1);

namespace VysokeSkoly\Build\Task;

use Robo\Result;
use Robo\Task\BaseTask;

/**
 * Task to generate buildinfo.xml file
 */
class Buildinfo extends BaseTask
{
    protected string $filename;
    protected string $appName = '';
    protected string $version = '';

    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    public function appName(string $appName): self
    {
        $this->appName = $appName;

        return $this;
    }

    public function version(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function run(): \Robo\Result
    {
        $doc = new \DOMDocument('1.0', 'utf-8');
        $root = $doc->createElement('appStatus');
        $doc->appendChild($root);

        $root->appendChild($doc->createElement('name', $this->appName));
        $root->appendChild($doc->createElement('version', $this->version));
        $root->appendChild($doc->createElement('sourceRevision', $this->getEnv('GIT_COMMIT')));
        $root->appendChild($doc->createElement('repository', $this->getEnv('GIT_URL')));
        $root->appendChild($doc->createElement('buildNumber', $this->getEnv('BUILD_NUMBER')));
        $root->appendChild($doc->createElement('buildBranch', $this->getEnv('GIT_BRANCH')));
        $root->appendChild($doc->createElement('buildUrl', $this->getEnv('BUILD_URL')));
        $root->appendChild($doc->createElement('project', $this->getEnv('JOB_NAME')));
        $root->appendChild($doc->createElement('hostName', '__HOSTNAME__'));

        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $xmlOutput = $doc->saveXML();

        $bytesWritten = file_put_contents($this->filename, $xmlOutput);
        if ($bytesWritten === false) {
            return Result::error($this, 'Error writing buildinfo to {filename}.', ['filename' => $this->filename]);
        }

        $this->printTaskSuccess('Buildinfo written to file {filename}.', ['filename' => $this->filename]);

        return Result::success($this);
    }

    private function getEnv(string $name): string
    {
        return (string) getenv($name);
    }
}
