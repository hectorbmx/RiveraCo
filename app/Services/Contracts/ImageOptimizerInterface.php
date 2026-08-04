<?php

namespace App\Services\Contracts;

use Illuminate\Http\UploadedFile;

interface ImageOptimizerInterface
{
    /**
     * Optimizates an uploaded or local image file.
     *
     * @param UploadedFile|string $file Source image file or path
     * @param array $options Configuration options (maxWidth, maxHeight, quality, format)
     * @return string Path or raw content of the optimized image
     */
    public function optimize(UploadedFile|string $file, array $options = []): string;

    /**
     * Compress and store an uploaded image to a given disk path.
     *
     * @param UploadedFile $file
     * @param string $directory Storage directory (e.g. 'reposiciones_evidencias')
     * @param string $disk Storage disk (default 'public')
     * @param array $options Optimization options
     * @return string Relative path stored on disk
     */
    public function optimizeAndStore(UploadedFile $file, string $directory, string $disk = 'public', array $options = []): string;
}
