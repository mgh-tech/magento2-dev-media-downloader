<?php
/**
 * Copyright © MGH-tech. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MGH\DevMediaDownloader\Test\Unit\Plugin;

use MGH\DevMediaDownloader\Plugin\FastlyPlugin;
use MGH\DevMediaDownloader\Model\Config;
use Fastly\Cdn\Plugin\MediaStorage\App\AroundMedia;
use Magento\MediaStorage\App\Media;
use Magento\MediaStorage\Model\File\Storage\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FastlyPluginTest extends TestCase
{
    #[DataProvider('aroundAroundLaunchDataProvider')]
    public function testAroundAroundLaunch(bool $enabled, $expectedCall)
    {
        $config = $this->createMock(Config::class);
        $config->method('isEnabled')->willReturn($enabled);
        $plugin = new FastlyPlugin($config);

        $subject = $this->createMock(AroundMedia::class);
        $media = $this->createMock(Media::class);
        $response = $this->createMock(Response::class);

        $proceed = function ($mediaArg, $origProceedArg) use ($response) {
            $this->fail('proceed should not be called when enabled');
        };
        $origProceed = function ($mediaArg) use ($response) {
            return $response;
        };

        if (!$enabled) {
            $proceed = function ($mediaArg, $origProceedArg) use ($response) {
                return $response;
            };
            $origProceed = function ($mediaArg) {
                $this->fail('origProceed should not be called when disabled');
            };
        }

        $result = $plugin->aroundAroundLaunch($subject, $proceed, $media, $origProceed);
        $this->assertSame($response, $result);
    }

    public static function aroundAroundLaunchDataProvider(): array
    {
        return [
            'enabled' => [true, 'origProceed'],
            'disabled' => [false, 'proceed'],
        ];
    }
}
