<?php

namespace App\Console\Commands;

use App\Support\ImageDerivatives;
use App\Support\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Bulk-generates the resized WebP copies that Media::url serves.
 *
 * Media::url also creates a missing derivative on demand, so this command is a
 * warm-up rather than a requirement - handy after a bulk import or a deploy.
 * Safe to re-run: existing derivatives are skipped unless --force is given.
 */
class OptimizeImages extends Command
{
    protected $signature = 'images:optimize
        {--force : Regenerate derivatives that already exist}';

    protected $description = 'Generate resized WebP versions of storefront images';

    public function handle(): int
    {
        if (! function_exists('imagewebp')) {
            $this->error('GD is missing WebP support, cannot generate derivatives.');

            return self::FAILURE;
        }

        $disk = Storage::disk('public');
        $force = (bool) $this->option('force');

        $sources = collect($disk->allFiles())
            ->reject(fn (string $path) => str_contains($path, '/' . Media::THUMB_DIR . '/'))
            ->filter(fn (string $path) => ImageDerivatives::supports($path))
            ->values();

        if ($sources->isEmpty()) {
            $this->info('No source images found.');

            return self::SUCCESS;
        }

        $created = 0;
        $skipped = 0;
        $failed = 0;
        $bytesIn = 0;
        $bytesOut = 0;

        $bar = $this->output->createProgressBar($sources->count());
        $bar->start();

        foreach ($sources as $path) {
            foreach (Media::WIDTHS as $width) {
                $target = Media::derivativePath($path, $width);

                if ($target === null) {
                    continue;
                }

                if (! $force && $disk->exists($target)) {
                    $skipped++;

                    continue;
                }

                if (! ImageDerivatives::ensure($path, $width, $force)) {
                    $failed++;

                    continue;
                }

                $created++;
                $bytesIn += (int) $disk->size($path);
                $bytesOut += (int) $disk->size($target);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info(sprintf('Created %d, skipped %d, failed %d.', $created, $skipped, $failed));

        if ($created > 0) {
            $this->info(sprintf(
                'Source bytes for those derivatives: %s -> %s (%d%% smaller).',
                $this->human($bytesIn),
                $this->human($bytesOut),
                $bytesIn > 0 ? (int) round(100 - ($bytesOut / $bytesIn * 100)) : 0
            ));
        }

        Media::forgetExistsCache();

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function human(int $bytes): string
    {
        return $bytes >= 1048576
            ? round($bytes / 1048576, 1) . ' MB'
            : round($bytes / 1024) . ' KB';
    }
}
