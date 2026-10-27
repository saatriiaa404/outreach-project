<?php

namespace App\Support;

use GdImage;
use Illuminate\Validation\ValidationException;

/**
 * Photo compression with PHP's bundled GD (Workflow v2.1 section 15.5):
 * longest side about 2,000 px without upscaling, JPEG quality about 80, EXIF
 * orientation applied. Every image is written as JPEG, which drops metadata
 * such as GPS location and is what Dompdf renders most reliably.
 */
class ImageOptimizer
{
    public const MAX_SIDE = 2000;

    public const JPEG_QUALITY = 80;

    /**
     * Images larger than this would need too much memory to decode; a real
     * phone photo is well below it (about 12 to 50 megapixels).
     */
    public const MAX_PIXELS = 50_000_000;

    /**
     * Writes an optimized JPEG next to the system temp files.
     *
     * @return array{path: string, width: int, height: int}
     */
    public function optimize(string $path, string $mime): array
    {
        $size = @getimagesize($path);

        if ($size === false || $size[0] * $size[1] > self::MAX_PIXELS) {
            throw ValidationException::withMessages(['file' => 'Resolusi gambar terlalu besar untuk diproses. Unggah foto dengan resolusi lebih kecil.']);
        }

        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => false,
        };

        if (! $image instanceof GdImage) {
            throw ValidationException::withMessages(['file' => 'Gambar tidak dapat diproses. Unggah ulang berkas yang utuh.']);
        }

        if ($mime === 'image/jpeg') {
            $image = $this->applyOrientation($image, $path);
        }

        $image = $this->resize($image);
        $output = tempnam(sys_get_temp_dir(), 'att');

        if ($output === false || ! imagejpeg($image, $output, self::JPEG_QUALITY)) {
            throw ValidationException::withMessages(['file' => 'Gambar gagal dikompresi. Coba unggah ulang.']);
        }

        return ['path' => $output, 'width' => imagesx($image), 'height' => imagesy($image)];
    }

    /**
     * Phone cameras store rotation as an EXIF tag that GD ignores.
     */
    private function applyOrientation(GdImage $image, string $path): GdImage
    {
        $exif = function_exists('exif_read_data') ? @exif_read_data($path) : false;
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;

        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }

        // imagerotate() turns counterclockwise; EXIF 6 and 7 need 90 degrees clockwise.
        $angle = match ($orientation) {
            3, 4 => 180,
            6, 7 => -90,
            5, 8 => 90,
            default => 0,
        };

        return $angle === 0 ? $image : (imagerotate($image, $angle, 0) ?: $image);
    }

    /**
     * Scales down to MAX_SIDE on a white background (JPEG has no transparency).
     */
    private function resize(GdImage $image): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, self::MAX_SIDE / max($width, $height));
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        imagefill($canvas, 0, 0, (int) imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        return $canvas;
    }
}
