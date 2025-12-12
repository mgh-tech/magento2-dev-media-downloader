<?php
/**
 * Copyright © MGH-tech. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MGH\DevMediaDownloader\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\HTTP\Client\Curl;
use MGH\DevMediaDownloader\Model\Config;
use Psr\Log\LoggerInterface;
use Throwable;

class MediaPlugin
{
    public function __construct(
        private readonly Config $config,
        private readonly RequestInterface $request,
        private readonly File $ioFile,
        private readonly Curl $curl,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Around plugin for Media::launch - attempts to fetch missing media files from a remote base.
     */
    public function aroundLaunch(\Magento\MediaStorage\App\Media $subject, callable $proceed)
    {
        // Intentionally not using $subject; required by plugin signature
        if ($this->config->isEnabled()) {
            $pathInfo = $this->request->getPathInfo();
            if (str_starts_with($pathInfo, '/media/')) {
                $mediaPath = BP . '/pub' . $pathInfo;
                if (!$this->fileExists($mediaPath)) {
                    $remoteBase = $this->config->getRemoteBaseUrl();
                    if ($remoteBase) {
                        $this->attemptDownload($remoteBase, $pathInfo, $mediaPath);
                    }
                }
            }
        }

        return $proceed();
    }

    protected function fileExists(string $path): bool
    {
        return file_exists($path);
    }

    protected function attemptDownload(string $remoteBase, string $pathInfo, string $mediaPath): void
    {
        $candidates = $this->buildRemoteCandidates($remoteBase, $pathInfo);
        $downloaded = false;
        foreach ($candidates as $remoteUrl) {
            try {
                $data = $this->downloadIfImage($remoteUrl);
                if ($data !== null) {
                    $this->ioFile->checkAndCreateFolder(dirname($mediaPath));
                    $this->ioFile->write($mediaPath, $data);
                    $downloaded = true;
                    break;
                }
            } catch (FileSystemException|LocalizedException $e) {
                $this->logger->debug('DevMediaDownloader file write error', [
                    'exception' => $e->getMessage(),
                    'url' => $remoteUrl,
                    'target' => $mediaPath
                ]);
                break; // Writing failed; abort further attempts
            } catch (Throwable $t) {
                $this->logger->debug('DevMediaDownloader fetch failed', [
                    'exception' => $t->getMessage(),
                    'url' => $remoteUrl
                ]);
            }
        }
        if (!$downloaded) {
            $this->logger->debug('DevMediaDownloader: no remote candidate succeeded', [
                'path' => $pathInfo,
                'candidates' => $candidates
            ]);
        }
    }

    /**
     * Build a list of possible remote URLs for a requested media path.
     * Handles Magento product image cache paths by stripping /cache/<hash>/ and optional size/type segments.
     */
    private function buildRemoteCandidates(string $remoteBase, string $pathInfo): array
    {
        $candidates = [];
        $remoteBase = rtrim($remoteBase, '/');
        if (preg_match('#^/media/catalog/product/cache/[^/]+/(.+)$#', $pathInfo, $m)) {
            $remainder = $m[1];
            $candidates[] = $remoteBase . '/media/catalog/product/' . $remainder;
            if (preg_match('#^([^/]+)/(.+)$#', $remainder, $m2)) {
                $first = $m2[1];
                $rest = $m2[2];
                if (preg_match('#^(?:\d+x\d+|[a-z_]+)$#i', $first)) {
                    $candidates[] = $remoteBase . '/media/catalog/product/' . $rest;
                }
            }
        }
        $candidates[] = $remoteBase . $pathInfo;
        return array_values(array_unique($candidates));
    }

    /**
     * Attempt to download a remote resource returning raw bytes only if it looks like an image.
     * Single return at end to satisfy coding standard.
     */
    private function downloadIfImage(string $remoteUrl): ?string
    {
        $result = null;
        $allowedExtensions = ['jpg','jpeg','png','gif','webp','avif','svg'];
        $ext = strtolower(pathinfo(parse_url($remoteUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        if ($ext && !in_array($ext, $allowedExtensions, true)) {
            return null; // early exit 1
        }

        $this->curl->setTimeout(3);
        $this->curl->setOption(CURLOPT_CONNECTTIMEOUT, 2);
        $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);
        $this->curl->get($remoteUrl);

        $statusOk = ($this->curl->getStatus() === 200);
        $contentType = $statusOk ? $this->extractContentType() : null;

        if (!$statusOk || $contentType === null) {
            return null; // early exit 2
        }

        if ($ext === 'svg') {
            $isValid = str_contains($contentType, 'svg');
        } else {
            $isValid = str_starts_with($contentType, 'image/');
        }

        if ($isValid) {
            $body = $this->curl->getBody();
            if ($body !== '') {
                $result = $body;
            }
        }

        return $result; // final return
    }

    /**
     * Extract Content-Type header from Curl client (case-insensitive scan of headers array).
     */
    private function extractContentType(): ?string
    {
        $headers = $this->curl->getHeaders();
        if (!is_array($headers)) {
            return null;
        }
        $found = null;
        foreach ($headers as $key => $value) {
            if (is_string($key) && strtolower($key) === 'content-type') {
                $found = is_array($value) ? ($value[0] ?? null) : $value;
                break;
            }
            if (is_int($key) && is_string($value)) {
                $parts = explode(':', $value, 2);
                if (count($parts) === 2 && strtolower(trim($parts[0])) === 'content-type') {
                    $found = trim($parts[1]);
                    break;
                }
            }
        }
        return $found;
    }
}
