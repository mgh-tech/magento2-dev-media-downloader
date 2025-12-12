<?php
/**
 * Copyright © MGH-tech. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MGH\DevMediaDownloader\Test\Unit\Plugin;

use Magento\MediaStorage\App\Media;
use MGH\DevMediaDownloader\Plugin\MediaPlugin;
use MGH\DevMediaDownloader\Model\Config;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\HTTP\Client\Curl;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class MediaPluginTest extends TestCase
{
    #[DataProvider('aroundLaunchDataProvider')]
    public function testAroundLaunch(bool $enabled, bool $fileExists, ?string $remoteBaseUrl, bool $shouldDownload)
    {
        $config = $this->createMock(Config::class);
        $config->method('isEnabled')->willReturn($enabled);
        $config->method('getRemoteBaseUrl')->willReturn($remoteBaseUrl);

        $request = $this->createMock(Http::class);
        $request->method('getPathInfo')->willReturn('/media/test.jpg');

        $ioFile = $this->createMock(File::class);
        $curl = $this->createMock(Curl::class);
        $logger = $this->createMock(LoggerInterface::class);

        // Use a subclass to spy on attemptDownload and override fileExists
        $plugin = new class($config, $request, $ioFile, $curl, $logger, $fileExists) extends MediaPlugin {
            public array $calls = [];
            private bool $fileExists;
            public function __construct($config, $request, $ioFile, $curl, $logger, $fileExists) {
                parent::__construct($config, $request, $ioFile, $curl, $logger);
                $this->fileExists = $fileExists;
            }
            protected function fileExists(string $path): bool {
                return $this->fileExists;
            }
            public function attemptDownload($remoteBase, $pathInfo, $mediaPath): void {
                $this->calls[] = [$remoteBase, $pathInfo, $mediaPath];
            }
        };

        $mediaPath = BP . '/pub/media/test.jpg';

        $proceed = function () {
            return 'proceeded';
        };

        // Simulate file_exists by temporarily redefining it if possible, or just trust the logic for this test
        $result = $plugin->aroundLaunch($this->createStub(Media::class), $proceed);
        $this->assertEquals('proceeded', $result);
        if ($shouldDownload) {
            $this->assertNotEmpty($plugin->calls);
        } else {
            $this->assertEmpty($plugin->calls);
        }
    }

    public static function aroundLaunchDataProvider(): array
    {
        return [
            'enabled, file missing, remote set' => [true, false, 'http://remote', true],
            'enabled, file exists' => [true, true, 'http://remote', false],
            'enabled, no remote' => [true, false, null, false],
            'disabled' => [false, false, 'http://remote', false],
        ];
    }
}
