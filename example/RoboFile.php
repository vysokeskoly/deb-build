<?php

// Autoload deb-build classes
// (you can use the returned instance of Composer\Autoload\ClassLoader to register additional paths if needed)
/* $classLoader = */ require __DIR__ . '/vendor/vysokeskoly/deb-build/src/autoload.php';

use Robo\Common\ResourceExistenceChecker;
use Robo\Tasks;
use VysokeSkoly\Build\ComposerParserTrait;
use VysokeSkoly\Build\FpmCheckerTrait;
use VysokeSkoly\Build\PackageVersionerTrait;
use VysokeSkoly\Build\Task\LoadTasksTrait;

class RoboFile extends Tasks
{
    use ComposerParserTrait;
    use FpmCheckerTrait;
    use PackageVersionerTrait;
    use ResourceExistenceChecker;
    use LoadTasksTrait;
    // TODO use all needed traits

    const INSTALL_DIR = 'srv/www/{PACKAGE}'; // TODO specify path to your package
    const VHOST_DIR = 'etc/apache2/sites-enabled';
    const LOGROTATE_DIR = 'etc/logrotate.d';
    const POSTINST_DIR = 'etc';

    /**
     * Build deb package. It is expected the Composer packages were installed using `--no-dev`.
     *
     * @param array $options
     * @return int
     */
    public function buildDeb($options = ['dev-build' => false])
    {
        $this->stopOnFail();

        $isDevBuild = (bool) $options['dev-build'];

        if (!$this->checkFpmIsInstalled()) {
            return 1;
        }

        $packageName = 'vysokeskoly-{PACKAGE_NAME}'; // TODO specify you dash-separated package name (vysokeskoly-foo-bar)
        $packageVersion = $this->assemblePackageVersion($isDevBuild);
        $versionIteration = $this->assembleVersionIteration();
        $composer = $this->parseComposer();

        $temporaryBuildDir = $this->_tmpDir();
        $buildRootDir = $temporaryBuildDir . '/root';
        $appBuildDir = $buildRootDir . '/' . self::INSTALL_DIR;

        // Create basic filesystem structure
        $this->taskFilesystemStack()
            ->mkdir($appBuildDir)
            ->mkdir($appBuildDir . '/var')
            ->mkdir($appBuildDir . '/' . self::POSTINST_DIR)
            ->run();

        // Generate postinst script
        $postinstResult = $this->taskPostinst($packageName, $appBuildDir . '/' . self::POSTINST_DIR, self::INSTALL_DIR)
            ->args([
                'www-data', // runtime files owner
                'www-data', // runtime files group
            ])
            ->run();

        $postinstPath = $postinstResult['path'];

        // Copy required directories
        foreach (['app', 'bin', 'etc', 'src', 'vendor', 'www'] as $directoryToCopy) {
            $this->_copyDir(__DIR__ . '/' . $directoryToCopy, $appBuildDir . '/' . $directoryToCopy);
        }

        // Copy required files
        foreach (['bin/robo', 'composer.json', 'composer.lock', 'RoboFile.php'] as $fileToCopy) {
            $this->_copy(__DIR__ . '/' . $fileToCopy, $appBuildDir . '/' . $fileToCopy);
        }

        // Generate buildinfo.xml
        $this->taskBuildinfo($appBuildDir . '/var/buildinfo.xml')
            ->appName($packageName)
            ->version($packageVersion . '-' . $versionIteration)
            ->run();

        // Clean unwanted files
        foreach ([] as $fileToDelete) {
            $this->_remove($appBuildDir . '/' . $fileToDelete);
        }

        // Even when packages are installed using `composer install --no-dev`, they often contains unneeded files.
        $vendorDirectoriesToDelete = [
            // TODO specify vendor directories you want to delete from the package:
            'ocramius/proxy-manager/html-docs',
            'ocramius/proxy-manager/tests',
            'twig/twig/test',
            'solarium/solarium/tests',
            'guzzlehttp/guzzle/docs',
            'guzzlehttp/guzzle/tests',
            'monolog/monolog/tests',
            'mobiledetect/mobiledetectlib/tests',
        ];

        // Clean unwanted vendor directories
        foreach ($vendorDirectoriesToDelete as $vendorDirectoryToDelete) {
            $this->_deleteDir($appBuildDir . '/vendor/' . $vendorDirectoryToDelete);
        }

        $this->taskFilesystemHelper()
            ->dir($appBuildDir)
            ->removeDirsRecursively('Tests', 'vendor/symfony')// Remove Tests files from Symfony itself
            ->removeDirsRecursively('Tests', 'src/')// Remove Tests files from our bundles
            ->run();

        // Copy vhosts settings
        $vhostDir = $buildRootDir . '/' . self::VHOST_DIR;
        $this->taskFilesystemStack()
            ->mkdir($vhostDir)
            ->copy(__DIR__ . '/etc/vhosts.{PACKAGE}.conf', $vhostDir . '/vhosts.{PACKAGE}.conf')// TODO change to your vhost(s)
            ->run();

        // Copy logrotate settings
        $logrotateDir = $buildRootDir . '/' . self::LOGROTATE_DIR;
        $this->taskFilesystemStack()
            ->mkdir($logrotateDir)
            // TODO change to you LOG_ROTATE filename:
            ->copy(
                __DIR__ . '/etc/{LOG_ROTATE}.logrotate',
                $logrotateDir . '/{LOG_ROTATE}.logrotate'
            )
            ->run();

        $this->taskExec('fpm')
            ->args(['--description', $composer['description']])// description for `apt search`
            ->args(['-s', 'dir'])// source type
            ->args(['-t', 'deb'])// output type
            ->args(['--name', $packageName])// package name
            ->args(['--vendor', 'VysokeSkoly'])
            ->args(['--architecture', 'all'])
            ->args(['--version', $packageVersion])
            ->args(['--iteration', $versionIteration])
            ->args(['-C', $buildRootDir])// change directory to here before searching for files
            // TODO specify your package dependencies (PHP extensions, other packages etc.):
            ->args(['--depends', 'php-common'])
            ->args(['--depends', 'php-cli'])
            // TODO if you need apache reload, uncomment following 2 lines
            //->args(['--depends', 'vysokeskoly-apache-common'])
            //->args(['--deb-activate', 'apache-common-reload'])
            ->args(['--after-install', $postinstPath])
            // Files placed in /etc wouldn't be overridden on package update without following flag:
            ->arg('--deb-no-default-config-files')
            ->arg('.')
            ->run();

        $this->io()->success('Done');

        return 0;
    }

    /**
     * Run post-installation tasks for deb package
     *
     * @param string $runtimeFilesOwner name of the user to whom should the files created on runtime belong to
     * @param string $runtimeFilesGroup name of the group to whom should the files created on runtime belong to
     */
    public function installDebPostinst($runtimeFilesOwner, $runtimeFilesGroup)
    {
        $this->stopOnFail();

        // Setup rights recursively
        $directoriesToChmod = [
            '/' . self::INSTALL_DIR,
            '/' . self::VHOST_DIR,
            '/' . self::LOGROTATE_DIR,
        ];
        foreach ($directoriesToChmod as $directoryToChmod) {
            $this->taskFilesystemHelper()
                ->dir($directoryToChmod)
                ->chmodRecursivelyWritableByUserReadableByOthers()
                ->run();
        }

        // Do hard cache clean
        $cacheDir = '/' . self::INSTALL_DIR . '/var/cache';
        if ($this->isFile($cacheDir)) {
            $this->_cleanDir($cacheDir);
        }

        // Build Symfony bootstrap
        $this->taskExec('php ./vendor/sensio/distribution-bundle/Resources/bin/build_bootstrap.php')
            ->arg('./var')
            ->arg('./app')
            ->arg('--use-new-directory-structure')
            ->dir('/' . self::INSTALL_DIR)
            ->run();

        // Clean and warm-up app cache
        foreach (['dev', 'prod'] as $symfonyEnvironment) {
            $this->taskExec('php -d memory_limit=256M ./bin/console')
                ->arg('cache:clear')
                ->arg('--env=' . $symfonyEnvironment)
                ->dir('/' . self::INSTALL_DIR)
                ->run();
        }

        // Make var/ directory (containing cache and logs) recursively owned and writable for given user
        $varDirectory = '/' . self::INSTALL_DIR . '/var';
        $this->taskFilesystemStack()
            ->chown($varDirectory, $runtimeFilesOwner, true)
            ->chgrp($varDirectory, $runtimeFilesGroup, true)
            ->run();

        $this->taskFilesystemHelper()
            ->dir($varDirectory)
            ->chmodRecursivelyWritableByUserReadableByOthers()
            ->run();

        // Copy assets
        $this->taskExec('php ./bin/console assets:install ./www')
            ->dir('/' . self::INSTALL_DIR)
            ->run();
    }
}
