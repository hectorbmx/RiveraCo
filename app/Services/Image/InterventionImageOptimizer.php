<?php

namespace App\Services\Image;

use App\Services\Contracts\ImageOptimizerInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

class InterventionImageOptimizer implements ImageOptimizerInterface
{
    protected ImageManager $manager;

    public function __construct(?ImageManager $manager = null)
    {
        $this->manager = $manager ?? ImageManager::gd();
    }

    /**
     * Optimizates an uploaded or local image file.
     *
     * @param UploadedFile|string $file
     * @param array $options
     * @return string Raw binary content of the optimized image
     */
    public function optimize(UploadedFile|string $file, array $options = []): string
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        $maxWidth = $options['max_width'] ?? 1920;
        $maxHeight = $options['max_height'] ?? 1920;
        $quality = $options['quality'] ?? 80;
        $format = strtolower($options['format'] ?? 'jpeg');

        $image = $this->manager->read($path);

        // Resize down if exceeds max dimensions keeping aspect ratio
        if ($image->width() > $maxWidth || $image->height() > $maxHeight) {
            $image->scaleDown(width: $maxWidth, height: $maxHeight);
        }

        // Encode image according to format
        return match ($format) {
            'webp' => (string) $image->toWebp($quality),
            'png'  => (string) $image->toPng(),
            'gif'  => (string) $image->toGif(),
            default => (string) $image->toJpeg($quality),
        };
    }

    /**
     * Compress and store an uploaded image or document to a given disk path.
     * If the file is a PDF, it is saved directly without image manipulation.
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param string $disk
     * @param array $options
     * @return string Relative path stored on disk
     */
    public function optimizeAndStore(UploadedFile $file, string $directory, string $disk = 'public', array $options = []): string
    {
        $mime = strtolower($file->getMimeType());
        $originalExtension = strtolower($file->getClientOriginalExtension());

        // Non-image files (like PDF) are stored directly
        if ($originalExtension === 'pdf' || str_contains($mime, 'pdf')) {
            $filename = Str::uuid() . '.pdf';
            return $file->storeAs($directory, $filename, $disk);
        }

        // Process images
        $format = $options['format'] ?? ($originalExtension === 'png' ? 'jpeg' : ($originalExtension === 'webp' ? 'webp' : 'jpeg'));
        $extension = $format === 'jpeg' ? 'jpg' : $format;
        
        $binaryContent = $this->optimize($file, array_merge(['format' => $format], $options));

        $filename = Str::uuid() . '.' . $extension;
        $path = trim($directory, '/') . '/' . $filename;

        Storage::disk($disk)->put($path, $binaryContent);

        return $path;
    }
}
