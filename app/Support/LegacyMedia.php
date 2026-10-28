<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LegacyMedia
{
    public static function path(array $image, Model $record): ?string
    {
        if ($image['url'] ?? false) {
            return null;
        }
        $value = (string) $record->getAttribute($image['namingField'] ?? $image['field']);
        if (! static::safeName($value)) {
            return null;
        }
        $suffix = ($image['stem'] ?? false) || isset($image['namingField']) ? '.'.$image['extension'] : '';
        $path = $image['directory'].'/'.$value.$suffix;

        return Storage::disk('legacy')->exists($path) ? $path : null;
    }

    public static function url(array $image, Model $record): ?string
    {
        $path = static::path($image, $record);

        return $path === null ? null : Storage::disk('legacy')->url($path);
    }

    /**
     * File names of the extra gallery photos the website shows for a product name.
     *
     * @param  array{directory: string, namingField: string, suffixes: array<int, string>, extension: string}  $gallery
     * @return array<int, string>
     */
    public static function galleryFiles(array $gallery, string $name): array
    {
        return array_map(fn (string $suffix): string => $name.$suffix.'.'.$gallery['extension'], $gallery['suffixes']);
    }

    /**
     * Gallery photos that are not in the gallery directory yet.
     *
     * @param  array{directory: string, namingField: string, suffixes: array<int, string>, extension: string}  $gallery
     * @return array<int, string>
     */
    public static function missingGalleryFiles(array $gallery, string $name): array
    {
        $files = static::galleryFiles($gallery, $name);

        if (! static::safeName($name)) {
            return $files;
        }

        return array_values(array_filter(
            $files,
            fn (string $file): bool => ! Storage::disk('legacy')->exists($gallery['directory'].'/'.$file),
        ));
    }

    public static function safeName(string $name): bool
    {
        return $name !== '' && ! str_contains($name, '..') && ! preg_match('/[\\\\\/<>:"|?*\x00-\x1F]/u', $name)
            && ! preg_match('/^(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(?:\.|$)/i', $name)
            && ! preg_match('/[ .]$/', $name);
    }

    public static function prepare(array $data, array $definition, ?Model $record = null): array
    {
        $disk = Storage::disk('legacy');
        $uploads = Storage::disk('legacy_uploads');
        foreach ($definition['images'] ?? [] as $image) {
            $uploadField = '_upload_'.ltrim($image['field'], '_');
            $uploaded = $data[$uploadField] ?? null;
            unset($data[$uploadField]);
            if (is_array($uploaded)) {
                $uploaded = reset($uploaded) ?: null;
            }
            if (! $uploaded) {
                // Named images follow the machine/customer name. Preserve the old file on rename.
                if ($record && isset($image['namingField'])) {
                    $name = (string) ($data[$image['namingField']] ?? $record->getAttribute($image['namingField']));
                    $oldPath = static::path($image, $record);
                    if ($oldPath && $name !== $record->getAttribute($image['namingField'])) {
                        static::validateName($name, $image['namingField']);
                        $newPath = $image['directory'].'/'.$name.'.'.$image['extension'];
                        if ($disk->exists($newPath)) {
                            throw ValidationException::withMessages([$image['namingField'] => 'Nama ini sudah mempunyai gambar. Gunakan nama lain.']);
                        }
                        $disk->copy($oldPath, $newPath);
                    }
                }

                continue;
            }
            if (! is_string($uploaded) || ! preg_match('/^_uploads\/[a-f0-9-]+\.(jpg|jpeg|png|webp|pdf)$/i', $uploaded) || ! $uploads->exists($uploaded)) {
                throw ValidationException::withMessages([$uploadField => 'Upload tidak valid. Silakan upload ulang.']);
            }
            $extension = $image['extension'] ?? strtolower(pathinfo($uploaded, PATHINFO_EXTENSION));
            $name = isset($image['namingField']) ? (string) ($data[$image['namingField']] ?? '') : (string) Str::uuid();
            static::validateName($name, $image['namingField'] ?? $uploadField);
            $relative = $image['directory'].'/'.$name.'.'.$extension;
            if ($extension === 'pdf') {
                if (! str_starts_with($uploads->get($uploaded), '%PDF-')) {
                    throw ValidationException::withMessages([$uploadField => 'File harus berupa PDF.']);
                }
                $disk->put($relative, $uploads->get($uploaded));
            } else {
                $bytes = $uploads->get($uploaded);
                $dimensions = @getimagesizefromstring($bytes);
                if (! $dimensions || $dimensions[0] > 6000 || $dimensions[1] > 6000) {
                    throw ValidationException::withMessages([$uploadField => 'Gambar maksimal 6000 × 6000 piksel.']);
                }
                if (in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
                    $source = @imagecreatefromstring($bytes);
                    if (! $source) {
                        throw ValidationException::withMessages([$uploadField => 'File gambar tidak dapat dibaca.']);
                    }
                    if ($extension !== 'png') {
                        $canvas = imagecreatetruecolor(imagesx($source), imagesy($source));
                        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
                        imagecopy($canvas, $source, 0, 0, 0, 0, imagesx($source), imagesy($source));
                        $source = $canvas;
                    } else {
                        imagealphablending($source, false);
                        imagesavealpha($source, true);
                    }
                    ob_start();
                    $extension === 'png' ? imagepng($source) : imagejpeg($source, null, 90);
                    $disk->put($relative, ob_get_clean());
                } else {
                    $disk->put($relative, $bytes);
                }
            }
            if (! isset($image['namingField'])) {
                $data[$image['field']] = ($image['url'] ?? false) ? $disk->url($relative)
                    : (($image['stem'] ?? false) ? $name : $name.'.'.$extension);
            }
            $uploads->delete($uploaded);
        }

        return $data;
    }

    private static function validateName(string $name, string $field): void
    {
        if (! static::safeName($name)) {
            throw ValidationException::withMessages([$field => 'Nama tidak boleh berisi karakter yang tidak valid untuk nama file.']);
        }
    }
}
