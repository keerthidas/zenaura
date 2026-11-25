<?php

namespace App\Support;

class PdfImage
{
    /**
     * Build a data URI for PDF embedding. When $maxEdge is set, downscale and
     * re-encode as JPEG so DomPDF does not embed full-resolution catalogue photos.
     */
    public static function dataUriFromFile(string $fullPath, ?int $maxEdge = null, int $jpegQuality = 75): ?string
    {
        if (! is_readable($fullPath)) {
            return null;
        }

        $bytes = (string) file_get_contents($fullPath);
        if ($bytes === '') {
            return null;
        }

        if ($maxEdge !== null && $maxEdge > 0 && extension_loaded('gd')) {
            $optimized = self::optimize($bytes, $maxEdge, $jpegQuality);
            if ($optimized !== null) {
                return 'data:image/jpeg;base64,'.base64_encode($optimized);
            }
        }

        $mime = mime_content_type($fullPath) ?: 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode($bytes);
    }

    public static function optimize(string $bytes, int $maxEdge, int $jpegQuality = 75): ?string
    {
        if (! extension_loaded('gd') || $bytes === '') {
            return null;
        }

        set_error_handler(static fn () => true);
        try {
            $source = imagecreatefromstring($bytes);
        } finally {
            restore_error_handler();
        }

        if ($source === false) {
            return null;
        }

        try {
            $width = imagesx($source);
            $height = imagesy($source);
            if ($width < 1 || $height < 1) {
                return null;
            }

            $scale = min(1, $maxEdge / max($width, $height));
            $targetWidth = max(1, (int) round($width * $scale));
            $targetHeight = max(1, (int) round($height * $scale));

            $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
            if ($canvas === false) {
                return null;
            }

            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $white);
            imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

            ob_start();
            imagejpeg($canvas, null, max(40, min(90, $jpegQuality)));
            $jpeg = ob_get_clean();
            imagedestroy($canvas);

            return $jpeg === false || $jpeg === '' ? null : $jpeg;
        } finally {
            imagedestroy($source);
        }
    }
}
