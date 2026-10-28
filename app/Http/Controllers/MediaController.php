<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    public function __invoke(string $path): BinaryFileResponse
    {
        return $this->fileFromDisk('legacy', $path);
    }

    public function upload(string $path): BinaryFileResponse
    {
        return $this->fileFromDisk('legacy_uploads', $path);
    }

    private function fileFromDisk(string $disk, string $path): BinaryFileResponse
    {
        abort_if(str_contains($path, '..') || str_contains($path, '\\') || str_contains($path, "\0"), 404);
        abort_unless(in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf'], true), 404);
        $root = realpath(Storage::disk($disk)->path(''));
        $file = realpath(Storage::disk($disk)->path($path));
        abort_unless($root && $file && str_starts_with($file, $root.DIRECTORY_SEPARATOR) && is_file($file), 404);

        return response()->file($file, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, max-age=3600']);
    }
}
