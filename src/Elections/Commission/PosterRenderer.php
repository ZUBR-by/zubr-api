<?php

namespace App\Elections\Commission;

use App\Elections\Entity\Commission;
use App\Elections\Entity\Member;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Treinetic\ImageArtist\lib\Image;
use Treinetic\ImageArtist\lib\Text\Color;
use Treinetic\ImageArtist\lib\Text\Font;
use Treinetic\ImageArtist\lib\Text\TextBox;

class PosterRenderer
{
    const FILE_TEMPLATE    = 'template_commission.png';
    const FILE_PLACEHOLDER = 'default.png';
    const FILE_BLANK       = 'blank_1.png';
    const POS_TOP          = 590;
    const POS_LEFT         = 150;
    const SIZE_H_12        = 450;
    const SIZE_W_12        = 410;
    const SIZE_M_12        = 15;
    const TEXT_COLOR       = 0;
    const GRAYSCALE        = false;
    const POS_TITLE_TOP    = 420;
    const RECT_H           = 150;

    const URL_COMMISSION = 'https://zubr.in/elections/commission/%d';
    const FILE_QRCODE    = 'qrcode.png';
    const SIZE           = 410;
    const MARGIN         = 400;

    /* @var string $projectDir */
    private $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    public function render(Commission $commission, $withoutPhoto = false) : string
    {
        $members  = $commission->getMembers()->toArray();
        $members  = array_values($members);
        $template = new Image($this->projectDir . '/templates/' . self::FILE_TEMPLATE);
        $this->addTitle($template, $commission->getName());

        $qrCode = new QrCode(sprintf(self::URL_COMMISSION, $commission->getId()));
        $qrCode->setWriterByName('png');
        $qrCode->setEncoding('UTF-8');
        $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::MEDIUM());
        $qrCode->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0]);
        $qrCode->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0]);
        $qrCode->setLogoSize(self::SIZE, self::SIZE);
        $qrCode->writeFile($this->projectDir . self::FILE_QRCODE);

        $qrCodeImage = new Image($this->projectDir . self::FILE_QRCODE);
        $qrCodeImage->scaleToHeight(self::SIZE);

        $template->merge(
            $qrCodeImage,
            $template->getWidth() - self::SIZE - self::MARGIN + 240,
            $template->getHeight() - self::SIZE - self::MARGIN + 135
        );

        $this->placeMembers($template, $members, $withoutPhoto);
        $posterNewPath = sprintf('%s%d.png', $this->projectDir, $commission->getId());
        $template->save($posterNewPath, IMAGETYPE_PNG);

        return $posterNewPath;
    }

    private function addLabel(Image $image, Member $member)
    {
        $textBox = new TextBox($image->getWidth(), 200);
        $textBox->setColor(
            new Color(
                self::TEXT_COLOR,
                self::TEXT_COLOR,
                self::TEXT_COLOR
            )
        );
        $textBox->setFont(Font::getFont(Font::$NOTOSERIF_BOLD));
        $textBox->setSize(30);
        $textBox->setMargin(1);
        $textBox->setText($member->getFullName());
        $image->setTextBox($textBox, ($image->getWidth() - $textBox->getWidth()) / 2, $image->getHeight() - 120, false);
    }

    private function addSquare(Image $image)
    {
        $square = new Image($this->projectDir . '/templates/' . self::FILE_BLANK);
        $square->crop(0, 0, $image->getWidth(), self::RECT_H);
        $image->merge($square, 0, $image->getHeight());
    }

    /**
     * @param Image    $template
     * @param Member[] $members
     * */
    private function placeMembers(Image $template, array $members, bool $withoutPhoto = false)
    {
        $members = array_splice($members, 0, 15);

        $shiftX = self::POS_LEFT;
        $shiftY = self::POS_TOP;
        foreach ($members as $index => $member) {
            /** @var Member $member */
            $imageUrl = $withoutPhoto === false && $member->hasValidPhotoURL()
                ? $member->getPhotoUrl()
                : $this->projectDir . '/templates/' . self::FILE_PLACEHOLDER;

            $image = new Image($imageUrl);
            $image->scaleToHeight(self::SIZE_H_12);
            $image->crop(0, 0, self::SIZE_W_12, self::SIZE_H_12 - self::RECT_H);
            if (self::GRAYSCALE) {
                imagefilter($image->getResource(), IMG_FILTER_GRAYSCALE);
            }
            $this->addSquare($image);
            $this->addLabel($image, $member);
            $template->merge($image, $shiftX, $shiftY);

            if (($index + 1) % 5 === 0) {
                $shiftY += self::SIZE_H_12 + self::SIZE_M_12 * 3;
                $shiftX = self::POS_LEFT;
            } else {
                $shiftX += self::SIZE_W_12 + self::SIZE_M_12;
            }
        }
    }

    private function addTitle(Image $image, string $title)
    {
        $textBox = new TextBox(2300, 400);
        $color   = new Color(0, 0, 0);
        $textBox->setColor($color);
        $textBox->setFont(Font::getFont(Font::$NOTOSERIF_BOLD));
        $textBox->setMargin(1);
        $textBox->setText($title);
        if (strlen($title) < 60) {
            $textBox->setSize(45);
            $image->setTextBox($textBox, 150, self::POS_TITLE_TOP + 55);
        } else {
            $textBox->setSize(45);
            $image->setTextBox($textBox, 150, self::POS_TITLE_TOP + 45);
        }
    }
}
