<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Writes the resized WebP copies that Media::url serves.
 *
 * Shared by the `images:optimize` command (bulk warm-up) and by Media::url
 * itself, which generates a missing derivative on first use. That self-healing
 * matters: images uploaded through the dashboard would otherwise be served at
 * full resolution forever unless someone remembered to run the command.
 */
class ImageDerivatives
{
    public const QUALITY = 82;

    /** Source types we can resize. */
    private const SUPPORTED = ['jpg', 'jpeg', 'png'];

    public static function supports(string $path): bool
    {
        return in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::SUPPORTED, true);
    }

    /**
     * Make sure the derivative for $path at $width exists.
     *
     * Returns true when the file is present afterwards. Never throws: a
     * failure just means the caller serves the original.
     */
    public static function ensure(string $path, int $width, bool $force = false): bool
    {
        if (! function_exists('imagewebp') || ! self::supports($path)) {
            return false;
        }

        $target = Media::derivativePath($path, $width);

        if ($target === null) {
            return false;
        }

        $disk = Storage::disk('public');

        if (! $force && $disk->exists($target)) {
            return true;
        }

        if (! $disk->exists($path)) {
            return false;
        }

        try {
            return self::write($disk->path($path), $disk->path($target), $width);
        } catch (Throwable) {
            return false;
        }
    }

    private static function write(string $source, string $target, int $width): bool
    {
        $contents = @file_get_contents($source);

        if ($contents === false) {
            return false;
        }

        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            return false;
        }

        $sourceWidth = imagesx($image);
        $sourceHeight = imagesy($image);

        // Never upscale: a 400px-wide source stays 400px wide.
        $targetWidth = min($width, $sourceWidth);
        $targetHeight = max(1, (int) round($sourceHeight * ($targetWidth / $sourceWidth)));

        $resized = imagecreatetruecolor($targetWidth, $targetHeight);

        // Preserve transparency for PNG sources (logos, badges).
        imagealphablending($resized, false);
        imagesavealpha($resized, true);

        imagecopyresampled(
            $resized, $image,
            0, 0, 0, 0,
            $targetWidth, $targetHeight,
            $sourceWidth, $sourceHeight
        );

        $directory = dirname($target);

        if (! is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }

        $ok = imagewebp($resized, $target, self::QUALITY);

        imagedestroy($image);
        imagedestroy($resized);

        return $ok && is_file($target);
    }
}
