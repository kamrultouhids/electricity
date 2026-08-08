<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Compresses and converts uploaded images to WebP to save storage.
 */
class ImageService
{
    /**
     * Store an uploaded image as a resized WebP on the public disk.
     * Falls back to a plain store if the image can't be processed.
     *
     * @return string The stored path relative to the public disk.
     */
    public function storeAsWebp(
        UploadedFile $file,
        string $folder,
        int $maxWidth = 1200,
        int $maxHeight = 1200,
        int $quality = 80
    ): string {
        $webp = $this->toWebp($file->getRealPath(), $maxWidth, $maxHeight, $quality);

        if ($webp === null) {
            // Unsupported / unreadable — keep the original as-is.
            return $file->store($folder, 'public');
        }

        $path = trim($folder, '/').'/'.Str::uuid()->toString().'.webp';
        Storage::disk('public')->put($path, $webp);

        return $path;
    }

    /**
     * Read an image file, scale it down within the given bounds and return
     * the WebP-encoded binary. Returns null if the source can't be decoded.
     */
    protected function toWebp(string $sourcePath, int $maxWidth, int $maxHeight, int $quality): ?string
    {
        if (! function_exists('imagewebp')) {
            return null;
        }

        $info = @getimagesize($sourcePath);
        if ($info === false) {
            return null;
        }

        $src = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG  => @imagecreatefrompng($sourcePath),
            IMAGETYPE_GIF  => @imagecreatefromgif($sourcePath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($sourcePath),
            default        => null,
        };

        if (! $src) {
            return null;
        }

        $width = imagesx($src);
        $height = imagesy($src);

        // Scale down proportionally only if larger than the bounds.
        $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
        $newWidth = max(1, (int) round($width * $ratio));
        $newHeight = max(1, (int) round($height * $ratio));

        $dst = imagecreatetruecolor($newWidth, $newHeight);
        // Preserve transparency (PNG/GIF/WebP) with a white background.
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        ob_start();
        imagewebp($dst, null, $quality);
        $binary = ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        return $binary ?: null;
    }
}
