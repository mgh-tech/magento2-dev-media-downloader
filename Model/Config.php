<?php
/**
 * Copyright © MGH-tech. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MGH\DevMediaDownloader\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\State as AppState;

class Config
{
    private const XML_PATH_BASE_URL = 'dev_media_downloader/general/remote_base_url';
    private const XML_PATH_ENABLED  = 'dev_media_downloader/general/enabled';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly AppState $appState
    ) {
    }

    public function isEnabled(): bool
    {
        $enabled = false;
        if (PHP_SAPI !== 'cli') {
            try {
                $inDevMode = ($this->appState->getMode() === AppState::MODE_DEVELOPER);
            } catch (\Exception $e) {
                $inDevMode = false; // fail-safe
            }
            if ($inDevMode
                && $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE)
                && $this->getRemoteBaseUrl() !== null
            ) {
                $enabled = true;
            }
        }
        return $enabled;
    }

    public function getRemoteBaseUrl(): ?string
    {
        $value = (string)$this->scopeConfig->getValue(self::XML_PATH_BASE_URL, ScopeInterface::SCOPE_STORE);
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        // Normalize: remove trailing slash
        return rtrim($value, '/');
    }
}
