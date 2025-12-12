<?php
/**
 * Copyright © MGH-tech. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MGH\DevMediaDownloader\Test\Unit\Model;

use MGH\DevMediaDownloader\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\State as AppState;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    #[DataProvider('isEnabledDataProvider')]
    public function testIsEnabled(
        string $phpSapi,
        string $appMode,
        bool $flag,
        ?string $remoteBaseUrl,
        bool $expected
    ) {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('isSetFlag')->willReturn($flag);
        $scopeConfig->method('getValue')->willReturn($remoteBaseUrl);

        $appState = $this->createMock(AppState::class);
        $appState->method('getMode')->willReturn($appMode);

        $config = new Config($scopeConfig, $appState, $phpSapi);

        // Debug: Assert the mock returns the expected flag value
        $this->assertSame($flag, $scopeConfig->isSetFlag('any', 'any'));

        $result = $config->isEnabled();
        $this->assertSame($expected, $result);
    }

    public static function isEnabledDataProvider(): array
    {
        return [
            'dev mode, flag, remote set' => ['apache2handler', AppState::MODE_DEVELOPER, true, 'http://remote', true],
            'dev mode, flag, remote empty' => ['apache2handler', AppState::MODE_DEVELOPER, true, '', false],
            'dev mode, flag false' => ['apache2handler', AppState::MODE_DEVELOPER, false, 'http://remote', false],
            'dev mode, flag, remote null' => ['apache2handler', AppState::MODE_DEVELOPER, true, null, false],
            'not dev mode' => ['apache2handler', AppState::MODE_DEFAULT, true, 'http://remote', false],
            'cli sapi' => ['cli', AppState::MODE_DEVELOPER, true, 'http://remote', false],
        ];
    }

    #[DataProvider('getRemoteBaseUrlDataProvider')]
    public function testGetRemoteBaseUrl($value, $expected)
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn($value);
        $appState = $this->createMock(AppState::class);
        $config = new Config($scopeConfig, $appState);
        $this->assertSame($expected, $config->getRemoteBaseUrl());
    }

    public static function getRemoteBaseUrlDataProvider(): array
    {
        return [
            ['http://remote', 'http://remote'],
            ['', null],
            [null, null],
            ['   ', null],
        ];
    }
}
