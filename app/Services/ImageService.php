<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageService
{
    public function optimize(UploadedFile $file, string $path, array $sizes = []): array
    {
        $paths = [];

        $originalPath = $file->store($path, 'public');
        $paths['original'] = $originalPath;

        $defaultSizes = [
            'thumb' => [150, 150],
            'medium' => [400, 400],
            'large' => [800, 800],
        ];

        $resizeSizes = !empty($sizes) ? $sizes : $defaultSizes;

        foreach ($resizeSizes as $label => [$width, $height]) {
            $filename = pathinfo($originalPath, PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $resizedPath = $path . '/' . $label . '_' . $filename . '.' . $extension;

            Storage::disk('public')->put($resizedPath, $file->getContent());

            $paths[$label] = $resizedPath;
        }

        return $paths;
    }

    public function getUrl(string $path, string $size = 'original'): string
    {
        return asset('storage/' . $path);
    }
}
