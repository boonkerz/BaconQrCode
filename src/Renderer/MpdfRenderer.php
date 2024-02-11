<?php

namespace BaconQrCode\Renderer;

use BaconQrCode\Encoder\MatrixUtil;
use BaconQrCode\Encoder\QrCode;
use BaconQrCode\Exception\InvalidArgumentException;
use BaconQrCode\Renderer\Color\ColorInterface;
use BaconQrCode\Renderer\Image\MpdfBackEnd;
use BaconQrCode\Renderer\Path\Path;
use BaconQrCode\Renderer\RendererStyle\EyeFill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use Mpdf\Mpdf as MpdfObject;
use RuntimeException;

class MpdfRenderer implements RendererInterface
{

    private int|float $moduleSize;
    private int|float $offsetX;
    private int|float $offsetY;

    public function __construct(private RendererStyle $rendererStyle, private MpdfObject $mpdf)
    {

    }

    public function render(QrCode $qrCode): string
    {
        $size = $this->rendererStyle->getSize();
        $margin = $this->rendererStyle->getMargin();
        $matrix = $qrCode->getMatrix();
        $matrixSize = $matrix->getWidth();

        if ($matrixSize !== $matrix->getHeight()) {
            throw new InvalidArgumentException('Matrix must have the same width and height');
        }

        $totalSize = $matrixSize + ($margin * 2);
        $this->moduleSize = $size / $totalSize;
        $fill = $this->rendererStyle->getFill();

        $backgroundColor = $fill->getBackgroundColor();
        $this->mpdf->SetFillColor(r: $backgroundColor->toRgb()->getRed(), g: $backgroundColor->toRgb()->getGreen(), b: $backgroundColor->toRgb()->getBlue());



        $this->mpdf->RoundedRect($this->rendererStyle->getX(), $this->rendererStyle->getY(), $size, $size, 0, 'FD');

        $module = $this->rendererStyle->getModule();
        $moduleMatrix = clone $matrix;
        MatrixUtil::removePositionDetectionPatterns($moduleMatrix);
        $modulePath = $this->drawEyes($matrixSize, $module->createPath($moduleMatrix));

        return "";
    }

    private function drawEyes(int $matrixSize, Path $modulePath) : Path
    {
        $fill = $this->rendererStyle->getFill();

        $eye = $this->rendererStyle->getEye();
        $externalPath = $eye->getExternalPath();
        $internalPath = $eye->getInternalPath();

        $modulePath = $this->drawEye(
            $externalPath,
            $internalPath,
            $fill->getTopLeftEyeFill(),
            3.5,
            3.5,
            0,
            $modulePath
        );

        /*$modulePath = $this->drawEye(
            $externalPath,
            $internalPath,
            $fill->getTopRightEyeFill(),
            $matrixSize - 3.5,
            3.5,
            90,
            $modulePath
        );
        $modulePath = $this->drawEye(
            $externalPath,
            $internalPath,
            $fill->getBottomLeftEyeFill(),
            3.5,
            $matrixSize - 3.5,
            -90,
            $modulePath
        );*/

        return $modulePath;
    }

    private function drawEye(
        Path $externalPath,
        Path $internalPath,
        EyeFill $fill,
        float $xTranslation,
        float $yTranslation,
        int $rotation,
        Path $modulePath
    ) : Path {
        if ($fill->inheritsBothColors()) {
            return $modulePath
                ->append($externalPath->translate($xTranslation, $yTranslation))
                ->append($internalPath->translate($xTranslation, $yTranslation));
        }

        if (0 !== $rotation) {
            $this->mpdf->Rotate($rotation);
        }

        $this->offsetX = $xTranslation * $this->moduleSize;
        $this->offsetY = $yTranslation * $this->moduleSize;

        if ($fill->inheritsExternalColor()) {
            $modulePath = $modulePath->append($externalPath->translate($xTranslation, $yTranslation));
        } else {
            $this->drawPathWithColor($externalPath, $fill->getExternalColor());
        }

        if ($fill->inheritsInternalColor()) {
            $modulePath = $modulePath->append($internalPath->translate($xTranslation, $yTranslation));
        } else {
            $this->drawPathWithColor($internalPath, $fill->getInternalColor());
        }

        return $modulePath;
    }

    private function drawPathWithColor(Path $path, ColorInterface $color) : void
    {
        $this->mpdf->setFillColor(r: $color->toRgb()->getRed(), g: $color->toRgb()->getGreen(), b: $color->toRgb()->getBlue());
        $this->drawPath($path);
    }

    private function drawPath(Path $path) : void
    {
        $x = 0;
        $y = 0;
        foreach ($path as $op) {
            switch (true) {
                case $op instanceof \BaconQrCode\Renderer\Path\Move:
                    $x = $this->rendererStyle->getX() + $this->offsetX + ($op->getX() * $this->moduleSize);
                    $y = $this->rendererStyle->getY() + $this->offsetY + ($op->getY() * $this->moduleSize);
                    break;

                case $op instanceof \BaconQrCode\Renderer\Path\Line:
                    $this->mpdf->Line($x, $y, $this->rendererStyle->getX() + $this->offsetX + ($op->getX() * $this->moduleSize), $this->rendererStyle->getY() + $this->offsetY + ($op->getY() * $this->moduleSize));
                    $x = $this->rendererStyle->getX() + $this->offsetX + ($op->getX() * $this->moduleSize);
                    $y = $this->rendererStyle->getY() + $this->offsetY + ($op->getY() * $this->moduleSize);
                    break;

                case $op instanceof \BaconQrCode\Renderer\Path\EllipticArc:
                    $this->draw->pathEllipticArcAbsolute(
                        $op->getXRadius(),
                        $op->getYRadius(),
                        $op->getXAxisAngle(),
                        $op->isLargeArc(),
                        $op->isSweep(),
                        $op->getX(),
                        $op->getY()
                    );
                    break;

                case $op instanceof \BaconQrCode\Renderer\Path\Curve:
                    $this->draw->pathCurveToAbsolute(
                        $op->getX1(),
                        $op->getY1(),
                        $op->getX2(),
                        $op->getY2(),
                        $op->getX3(),
                        $op->getY3()
                    );
                    break;

                case $op instanceof \BaconQrCode\Renderer\Path\Close:
                    $x = 0;
                    $y = 0;
                    break;

                default:
                    throw new RuntimeException('Unexpected draw operation: ' . get_class($op));
            }
        }
    }
}