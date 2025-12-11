<?php
/**
 * Copyright © MGH-tech. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'MGH_DevMediaDownloader',
    __DIR__
);
