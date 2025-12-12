# MGH\DevMediaDownloader

A lightweight Magento 2 module for on-demand downloading of missing media files from a remote instance during development and testing.

## Overview

**MGH\DevMediaDownloader** streamlines development and testing workflows by automatically downloading missing media files (such as product images) from a remote Magento instance. This module is designed for development, staging, or CI environments where the full media library is not available locally, but is accessible from a production or reference server.

## Problem & Solution

Working with a local development environment often means missing large media files. Rather than synchronizing entire media folders, this module intelligently fetches media assets on-demand from your remote instance, improving both developer experience and CI/CD efficiency.

### How It Works

When a media file (e.g., an image in `/pub/media/`) is requested and not found locally:

1. The module intercepts the request
2. Attempts to fetch the file from a configured remote base URL
3. If successful, saves it locally for future requests
4. Serves the file as if it were present from the start

### Workflow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                    Request for Media File                       │
│              (e.g., /pub/media/catalog/product/...)             │
└─────────────────────┬───────────────────────────────────────────┘
                      │
                      ▼
        ┌─────────────────────────────┐
        │  File exists locally?       │
        └─────────────────────────────┘
               │           │
            YES│           │NO
               │           │
               │      ┌────▼────────────────────────────┐
               │      │ Module enabled & in dev mode?   │
               │      └────────┬────────────────────────┘
               │              │
               │            NO│ → Serve 404 or default
               │              │
               │            YES│
               │      ┌───────▼──────────────────────┐
               │      │ Build remote URL candidates  │
               │      │ (handle cache paths, etc.)   │
               │      └───────┬──────────────────────┘
               │              │
               │      ┌───────▼──────────────────────┐
               │      │ Try to download from remote  │
               │      │ (validate image type, etc.)  │
               │      └───────┬──────────────────────┘
               │              │
               │        ┌─────┴──────┐
               │      SUCCESS       FAIL
               │        │            │
               │    ┌───▼──────┐     └──→ Log error, serve 404
               │    │ Save file│
               │    │ locally  │
               │    └───┬──────┘
               │        │
               └────┬───┘
                    │
                    ▼
          ┌──────────────────────┐
          │  Serve file to client│
          └──────────────────────┘
```

## Key Features

- **Seamless on-demand downloading** of missing media files from a remote Magento instance
- **Intelligent caching** - once downloaded, files are stored locally for fast retrieval
- **Supports Magento's product image cache** structure and fallback logic
- **Safe downloads** - only downloads valid image files (jpg, jpeg, png, gif, webp, avif, svg)
- **Developer-mode only** - automatically disabled in production environments
- **Robust error handling** with comprehensive debug logging
- **Configurable remote base URL** - point to any Magento instance (support for multi-website setups)
- **Compatible with Fastly CDN** - includes dedicated plugin for Adobe Commerce with Fastly

## Installation

### Option 1: Via Composer (Recommended)

```bash
composer require mgh-tech/magento2-dev-media-downloader
bin/magento setup:upgrade
```

### Option 2: Manual Installation

1. Place the module under `app/code/MGH/DevMediaDownloader` in your Magento 2 project:
   ```bash
   mkdir -p app/code/MGH/DevMediaDownloader
   # Copy module files into this directory
   ```

2. Register the module by running setup upgrade:
   ```bash
   bin/magento setup:upgrade
   ```

3. Configure the module (see Configuration section below)

## Configuration

The module provides the following configuration options under **Stores > Configuration > Dev Tools > Dev Media Downloader**:

| Setting | Type | Description |
|---------|------|-------------|
| **Enable On-The-Fly Download** | Boolean | Toggle the module on/off. Only active in developer mode. |
| **Remote Base URL** | Text | The base URL of the remote Magento instance (e.g., `https://production.example.com`). Do not include a trailing slash. |

### Configuration via Admin Panel

1. Navigate to **Stores > Configuration > Dev Tools > Dev Media Downloader**
2. Enable the module
3. Set the remote base URL

### Configuration via Command Line

Alternatively, configure the module using the command line:

```bash
# Enable the module
bin/magento config:set dev_media_downloader/general/enabled 1

# Set the remote base URL
bin/magento config:set dev_media_downloader/general/remote_base_url https://production.example.com

# For specific store view (optional)
bin/magento config:set --scope=stores --scope-id=1 dev_media_downloader/general/remote_base_url https://production.example.com
```

### Example Configuration

```
Remote Base URL: https://production.example.com
Enable: Yes
```

## Requirements

- **PHP** >= 7.4
- **Magento** 2.4.x (or compatible OpenMage/Magento Open Source)
- **ext-curl** - PHP cURL extension (required for remote downloads)

## Compatibility

- ✅ Magento Open Source 2.4.x
- ✅ Adobe Commerce 2.4.x
- ✅ Adobe Commerce Cloud with Fastly CDN

## License

This module is licensed under the **MIT License**. See the [LICENSE.txt](LICENSE.txt) file for details.

## Author

**mgh-tech** - Magento 2 Development & Solutions  
GitHub: [github.com/mgh-tech](https://github.com/mgh-tech)

## Support & Contributions

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/mgh-tech/magento2-dev-media-downloader).

