<?php
declare(strict_types = 1);

namespace BaconQrCode\Renderer\Image;

use BaconQrCode\Renderer\Color\ColorInterface;
use BaconQrCode\Renderer\Path\Path;
use BaconQrCode\Renderer\RendererStyle\Gradient;
use Mpdf\Mpdf as MpdfObject;

final class MpdfBackEnd implements ImageBackEndInterface
{

    public function __construct(private MpdfObject $mpdf)
    {

    }

    public function new(int $size, ColorInterface $backgroundColor): void
    {
        // TODO: Implement new() method.
    }

    public function scale(float $size): void
    {
        // TODO: Implement scale() method.
    }

    public function translate(float $x, float $y): void
    {
        // TODO: Implement translate() method.
    }

    public function rotate(int $degrees): void
    {
        // TODO: Implement rotate() method.
    }

    public function push(): void
    {
        // TODO: Implement push() method.
    }

    public function pop(): void
    {
        // TODO: Implement pop() method.
    }

    public function drawPathWithColor(Path $path, ColorInterface $color): void
    {
        // TODO: Implement drawPathWithColor() method.
    }

    public function drawPathWithGradient(Path $path, Gradient $gradient, float $x, float $y, float $width, float $height): void
    {
        // TODO: Implement drawPathWithGradient() method.
    }

    public function done(): string
    {
        return "";
    }
}
