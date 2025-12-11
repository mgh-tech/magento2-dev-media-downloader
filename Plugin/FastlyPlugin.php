<?php
/**
 * Copyright © MGH-tech. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MGH\DevMediaDownloader\Plugin;

use Fastly\Cdn\Plugin\MediaStorage\App\AroundMedia;
use Magento\MediaStorage\App\Media;
use Magento\MediaStorage\Model\File\Storage\Response;
use MGH\DevMediaDownloader\Model\Config;

class FastlyPlugin
{

    public function __construct(
        private readonly Config $config,
    ) {}

    public function aroundAroundLaunch(
        AroundMedia $subject,
        callable $proceed,
        Media $origSubject,
        callable $origProceed): Response
    {
        if ($this->config->isEnabled()) {
            return $origProceed($origSubject);
        }
        return $proceed($origSubject, $origProceed);
    }
}
