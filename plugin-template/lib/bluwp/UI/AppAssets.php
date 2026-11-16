<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\UI;

final class AppAssets
{
    /** @var array<string, array{type: string, handle: string, deps: list<string>}> */
    private array $files = [];

    public function __construct(
        private string $handle,
        private string $path,
        private string $url,
        private string $version,
        array $files = [],
        private bool $suppressFooter = false
    ) {
        $this->path = rtrim($path, '/\\') . '/';
        $this->url = rtrim($url, '/') . '/';
        $files = $files ?: ['css/style.css' => 'style', 'js/script.js' => 'script'];
        $handles = [];
        foreach ($files as $file => $definition) {
            if (!is_string($file) || $file === '' || (!is_string($definition) && !is_array($definition))) {
                throw new \InvalidArgumentException('Assets require a file path and a style or script definition.');
            }
            $definition = is_string($definition) ? ['type' => $definition] : $definition;
            $type = $definition['type'] ?? null;
            if (!in_array($type, ['style', 'script'], true)) {
                throw new \InvalidArgumentException('Invalid asset type: ' . $file);
            }
            $standard = ($type === 'style' && $file === 'css/style.css') || ($type === 'script' && $file === 'js/script.js');
            $assetHandle = $definition['handle'] ?? ($standard ? $handle : $handle . '-' . $type . '-' . substr(sha1($file), 0, 12));
            $deps = $definition['deps'] ?? [];
            if (!is_string($assetHandle) || $assetHandle === '' || !is_array($deps) || !array_is_list($deps)) {
                throw new \InvalidArgumentException('Invalid asset handle or dependencies: ' . $file);
            }
            foreach ($deps as $dependency) {
                if (!is_string($dependency) || $dependency === '' || $dependency === $assetHandle) {
                    throw new \InvalidArgumentException('Invalid asset dependency: ' . $file);
                }
            }
            if (isset($handles[$type][$assetHandle])) {
                throw new \InvalidArgumentException('Duplicate asset handle: ' . $assetHandle);
            }
            $handles[$type][$assetHandle] = true;
            $this->files[$file] = ['type' => $type, 'handle' => $assetHandle, 'deps' => $deps];
        }
    }

    public function enqueue(): void
    {
        $versions = [];
        foreach ($this->files as $file => $definition) {
            $path = $this->path . 'assets/' . $file;
            if (!is_file($path) || !is_readable($path)) {
                throw new \RuntimeException('Build the assets before loading the application. Missing or unreadable file: ' . $file);
            }
            $modified = filemtime($path);
            if ($modified === false) {
                throw new \RuntimeException('Cannot determine asset version: ' . $file);
            }
            $versions[$file] = $this->version . '.' . $modified;
        }
        if ($this->suppressFooter) {
            add_filter('admin_footer_text', '__return_empty_string', PHP_INT_MAX);
            add_filter('update_footer', '__return_empty_string', PHP_INT_MAX);
        }
        foreach ($this->files as $file => $definition) {
            if ($definition['type'] === 'style') {
                wp_enqueue_style($definition['handle'], $this->url . 'assets/' . $file, $definition['deps'], $versions[$file]);
            } else {
                wp_enqueue_script($definition['handle'], $this->url . 'assets/' . $file, $definition['deps'], $versions[$file], true);
            }
        }
    }
}
