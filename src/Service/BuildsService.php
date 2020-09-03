<?php declare(strict_types=1);

namespace App\Service;

use App\Application\ApplicationInterface;
use App\Structs\Build;
use App\Structs\BuildInterface;
use App\Structs\LatestBuild;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\Routing\RouterInterface;
use const DIRECTORY_SEPARATOR;

class BuildsService
{
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var DownloadCounterService
     */
    private $downloadCounter;
    /**
     * @var FilesystemInterface
     */
    private $buildsFilesystem;

    /**
     * BuildsService constructor.
     *
     * @param RouterInterface $router
     * @param DownloadCounterService $downloadCounter
     * @param FilesystemInterface $buildsFilesystem
     */
    public function __construct(RouterInterface $router, DownloadCounterService $downloadCounter, FilesystemInterface $buildsFilesystem)
    {
        $this->router = $router;
        $this->downloadCounter = $downloadCounter;
        $this->buildsFilesystem = $buildsFilesystem;
    }

    /**
     * @param ApplicationInterface $application
     *
     * @return BuildInterface[]
     */
    public function getBuildsForApplication(ApplicationInterface $application): array
    {
        $builds = $this->getBuildsFromFilesystemForApplication($application);

        $latestBuild = $this->findLatestBuild($application, $builds);

        if ($latestBuild !== null) {
            $builds[] = $latestBuild;
        }

        return $builds;
    }

    public function getLatestBuildForApplication(ApplicationInterface $application): ?BuildInterface
    {
        $builds = $this->getBuildsFromFilesystemForApplication($application);

        return $this->findLatestBuild($application, $builds);
    }

    public function getBuildForApplication(ApplicationInterface $application, string $fileName): BuildInterface
    {
        $latestBuild = $this->getLatestBuildForApplication($application);
        if ($latestBuild !== null && $fileName === $latestBuild->getFileName()) {
            return $latestBuild;
        }

        return $this->getBuildForFile($application, $fileName);
    }

    public function getPathForBuild(ApplicationInterface $application, string $fileName): string
    {
        return $this->getPathForApplication($application) . DIRECTORY_SEPARATOR . $fileName;
    }

    public function getPathForApplication(ApplicationInterface $application): string
    {
        return $application->getName();
    }

    public function doesBuildExist(ApplicationInterface $application, string $fileName): bool
    {
        return $this->buildsFilesystem->has($this->getPathForBuild($application, $fileName));
    }

    private function findLatestBuild(ApplicationInterface $application, array $builds): ?LatestBuild
    {
        /** @var Build $highestVersion */
        $highestVersion = null;
        /** @var BuildInterface $build */
        foreach ($builds as $build) {
            if ($highestVersion !== null) {
                if ($this->isNewerThan($build->getMinecraftVersion(), $highestVersion->getMinecraftVersion())) {
                    $highestVersion = $build;
                    continue;
                }

                if ($build->getEpochDate() > $highestVersion->getEpochDate()) {
                    $highestVersion = $build;
                    continue;
                }
            } else {
                $highestVersion = $build;
            }
        }

        if ($highestVersion === null) {
            return null;
        }

        return new LatestBuild(
            $application,
            $highestVersion->getFileName(),
            $highestVersion->getByteSize(),
            $highestVersion->getDirectLink(),
            $highestVersion->getGrabLink(),
            $highestVersion->getDownloadCounter()
        );
    }

    /**
     * @param ApplicationInterface $application
     *
     * @return BuildInterface[]
     */
    private function getBuildsFromFilesystemForApplication(ApplicationInterface $application): array
    {
        $applicationPath = $this->getPathForApplication($application);

        $files = $this->buildsFilesystem->listContents($applicationPath);

        $builds = [];
        foreach ($files as $file) {
            $builds[] = $this->getBuildForFile($application, $file['basename']);
        }

        return $builds;
    }

    /**
     * Returns true if the first Version is higher than the second Version
     *
     * @param string $versionA
     * @param string $versionB
     *
     * @return bool
     */
    private function isNewerThan(string $versionA, string $versionB): bool
    {
        return version_compare($versionA, $versionB) === 1;
    }

    private function getBuildForFile(ApplicationInterface $application, string $fileName): BuildInterface
    {
        $directLink = $this->getDirectLinkForFile($application, $fileName);

        $grabLink = $this->getGrabLinkForFile($application, $fileName);

        $build = new Build($application, $fileName, $this->buildsFilesystem->getSize($this->getPathForBuild($application, $fileName)), $directLink, $grabLink);

        $build->setDownloadCounter($this->downloadCounter->getCounter($application, $build));

        return $build;
    }

    private function getDirectLinkForFile(ApplicationInterface $application, string $fileName): string
    {
        return $this->router->generate('files', [
            'applicationName' => $application->getName(),
            'fileName'        => $fileName,
        ], RouterInterface::ABSOLUTE_URL);
    }

    private function getGrabLinkForFile(ApplicationInterface $application, string $fileName): string
    {
        return $this->router->generate('grab', [
            'applicationName' => $application->getName(),
            'fileName'        => $fileName,
        ], RouterInterface::ABSOLUTE_URL);
    }
}
