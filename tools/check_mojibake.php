<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$defaultPaths = ['app', 'config', 'database', 'docs', 'resources', 'routes', 'tests'];
$paths = array_slice($argv, 1) ?: $defaultPaths;
$extensions = ['blade.php', 'css', 'js', 'json', 'md', 'php', 'txt', 'vue', 'yml', 'yaml'];
$patterns = [
    '/Ã[\x80-\xBF]?/u',
    '/Â[\x80-\xBF]?/u',
    '/â(?:€|€™|€œ|€|€¦|€¢|€“|€”)/u',
    '/\x{FFFD}/u',
];
$skipDirs = ['.git', 'bootstrap/cache', 'node_modules', 'storage', 'vendor'];

function relative_path(string $root, string $path): string
{
    return str_replace('\\', '/', str_starts_with($path, $root) ? substr($path, strlen($root) + 1) : $path);
}

function should_scan_file(string $filename, array $extensions): bool
{
    foreach ($extensions as $extension) {
        if (str_ends_with($filename, '.' . $extension)) {
            return true;
        }
    }

    return false;
}

function scan_file(string $root, SplFileInfo $file, array $patterns, array &$matches): void
{
    $contents = file_get_contents($file->getPathname());

    if ($contents === false) {
        return;
    }

    $lines = preg_split('/\R/u', $contents) ?: [];

    foreach ($lines as $lineNumber => $line) {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $line) === 1) {
                $matches[] = sprintf('%s:%d: %s', relative_path($root, $file->getPathname()), $lineNumber + 1, trim($line));
                break;
            }
        }
    }
}

$matches = [];

foreach ($paths as $inputPath) {
    $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $inputPath);
    $absolutePath = str_starts_with($path, $root) ? $path : $root . DIRECTORY_SEPARATOR . $path;

    if (is_file($absolutePath)) {
        $file = new SplFileInfo($absolutePath);

        if (should_scan_file($file->getFilename(), $extensions)) {
            scan_file($root, $file, $patterns, $matches);
        }

        continue;
    }

    if (!is_dir($absolutePath)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($absolutePath, FilesystemIterator::SKIP_DOTS),
            function (SplFileInfo $file) use ($root, $skipDirs): bool {
                $relative = relative_path($root, $file->getPathname());

                foreach ($skipDirs as $skipDir) {
                    if ($relative === $skipDir || str_starts_with($relative, $skipDir . '/')) {
                        return false;
                    }
                }

                return true;
            }
        )
    );

    foreach ($iterator as $file) {
        if ($file instanceof SplFileInfo && $file->isFile() && should_scan_file($file->getFilename(), $extensions)) {
            scan_file($root, $file, $patterns, $matches);
        }
    }
}

if ($matches !== []) {
    fwrite(STDERR, "Mojibake probable detectado. Guarda archivos como UTF-8 y corrige estos textos:\n");
    fwrite(STDERR, implode(PHP_EOL, $matches) . PHP_EOL);
    exit(1);
}

echo "OK: no se detecto mojibake probable." . PHP_EOL;