<?php

namespace App\Support;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class Qr
{
    /**
     * Return a base64 PNG data URI for the given text (e.g. a verify URL).
     */
    public static function dataUri(string $text, int $size = 200): string
    {
        $qrCode = new QrCode(data: $text, size: $size, margin: 8);

        return (new PngWriter())->write($qrCode)->getDataUri();
    }
}
