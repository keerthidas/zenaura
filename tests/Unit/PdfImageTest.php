<?php

namespace Tests\Unit;

use App\Support\PdfImage;
use PHPUnit\Framework\TestCase;

class PdfImageTest extends TestCase
{
    public function test_it_downscales_large_images_to_jpeg_thumbs(): void
    {
        $source = imagecreatetruecolor(800, 600);
        $red = imagecolorallocate($source, 200, 40, 40);
        imagefilledrectangle($source, 0, 0, 799, 599, $red);
        ob_start();
        imagepng($source);
        $png = ob_get_clean();
        imagedestroy($source);

        $path = tempnam(sys_get_temp_dir(), 'pdf-image-');
        try {
            file_put_contents($path, $png);

            $uri = PdfImage::dataUriFromFile($path, 64, 70);

            $this->assertNotNull($uri);
            $this->assertStringStartsWith('data:image/jpeg;base64,', $uri);

            $jpeg = base64_decode(substr($uri, strlen('data:image/jpeg;base64,')), true);
            $this->assertNotFalse($jpeg);
            $this->assertLessThan(strlen($png), strlen($jpeg));

            $thumb = imagecreatefromstring($jpeg);
            $this->assertNotFalse($thumb);
            $this->assertLessThanOrEqual(64, max(imagesx($thumb), imagesy($thumb)));
            imagedestroy($thumb);
        } finally {
            unlink($path);
        }
    }

    public function test_it_falls_back_to_original_bytes_when_content_is_not_an_image(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'pdf-image-');
        try {
            file_put_contents($path, 'not-an-image');

            $uri = PdfImage::dataUriFromFile($path, 64);

            $this->assertNotNull($uri);
            $this->assertStringContainsString(base64_encode('not-an-image'), $uri);
        } finally {
            unlink($path);
        }
    }
}
