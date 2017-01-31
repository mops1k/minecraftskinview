<?php
/**
 * Created by PhpStorm.
 * User: mops1k
 * Date: 01.02.2017
 * Time: 1:11
 */

namespace Minecraft;


class SkinView
{
    const SIDE_FRONT = 'front';
    const SIDE_BACK = 'back';

    private $slimDetectPixel = array(42, 51);

    /* Допустимые пропорции образа */
    private $skinProps = array(
        0 => array('base' => 64, 'ratio' => 2),
        1 => array('base' => 64, 'ratio' => 1),
    );

    private $cloakProps = array(
        0 => array('base' => 64, 'ratio' => 2),
        1 => array('base' => 22, 'ratio' => 1.29),
    );

    /** @var string */
    private $waySkin;

    /** @var string */
    private $wayCloak;

    /** @var string */
    private $saveSkin;

    /** @var string */
    private $saveHead;

    /** @var string */
    private $side;

    /** @var integer */
    private $size;

    /**
     * @return bool|resource
     */
    public function createHead()
    {
        $size = $this->size;
        if ($size === null) {
            $size = 151;
        }

        if (!$info = $this->isValidSkin()) {
            return false;
        }

        $img = @imagecreatefrompng($this->waySkin);
        if (!$img) {
            return false;
        }

        $p = array('face' => array(8, 8), 'hat' => array(40, 8));


        $av = imagecreatetruecolor($size, $size);
        $mp = $info['scale'];

        imagecopyresized($av, $img, 0, 0, $p['face'][0] * $mp, $p['face'][1] * $mp, $size, $size, 8 * $mp, 8 * $mp);
        imagecopyresized($av, $img, 0, 0, $p['hat'][0] * $mp, $p['hat'][1] * $mp, $size, $size, 8 * $mp, 8 * $mp);
        imagedestroy($img);

        return $av;
    }

    /**
     * @return resource|bool
     */
    public function createPreview()
    {
        $size = $this->size;
        if ($size === null) {
            $size = 224;
        }

        if (!$info = $this->isValidSkin()) {
            return false;
        }

        $skin = @imagecreatefrompng($this->waySkin);
        if (!$skin) {
            return false;
        }

        $mp = $info['scale'];
        $size_x = (($this->side) ? 16 : 32);
        $preview = imagecreatetruecolor($size_x * $mp, 32 * $mp);

        $transparent = imagecolorallocatealpha($preview, 255, 255, 255, 127);
        imagefill($preview, 0, 0, $transparent);

        $armWidth = 4; // for slim \ fat arms on version 1.8 or higher
        $slim = false;

        if ($info['ratio'] == 1) {
            // is slim verion
            $color = imagecolorat($skin, $this->slimDetectPixel[0], $this->slimDetectPixel[1]);
            $colors = imagecolorsforindex($skin, $color); // returns rgba array

            if ((int)$colors['alpha'] == 127) {
                $slim = true;
                $armWidth = 3;
            }
        }

        if ($this->side === null or $this->side === self::SIDE_FRONT) {
            // head
            imagecopy($preview, $skin, 4 * $mp, 0 * $mp, 8 * $mp, 8 * $mp, 8 * $mp, 8 * $mp);
            imagecopy($preview, $skin, 4 * $mp, 0 * $mp, 40 * $mp, 8 * $mp, 8 * $mp, 8 * $mp);

            // front arms
            imagecopy($preview, $skin, (4 - $armWidth) * $mp, 8 * $mp, 44 * $mp, 20 * $mp, $armWidth * $mp, 12 * $mp);

            // right side
            if ($info['ratio'] == 2) {
                $this->imageflip($preview, $skin, 12 * $mp, 8 * $mp, 44 * $mp, 20 * $mp, 4 * $mp, 12 * $mp);
            } else {
                // body (8) + arm(4)
                imagecopy($preview, $skin, 12 * $mp, 8 * $mp, 36 * $mp, 52 * $mp, $armWidth * $mp, 12 * $mp);
            }

            // body
            imagecopy($preview, $skin, 4 * $mp, 8 * $mp, 20 * $mp, 20 * $mp, 8 * $mp, 12 * $mp);

            // front legs
            imagecopy($preview, $skin, 4 * $mp, 20 * $mp, 4 * $mp, 20 * $mp, 4 * $mp, 12 * $mp);

            if ($info['ratio'] == 2) {
                $this->imageflip($preview, $skin, 8 * $mp, 20 * $mp, 4 * $mp, 20 * $mp, 4 * $mp, 12 * $mp);
            } else {
                imagecopy($preview, $skin, 8 * $mp, 20 * $mp, 20 * $mp, 52 * $mp, 4 * $mp, 12 * $mp);
            }

            if ($info['ratio'] == 1) {
                // front arms layer 2 right
                imagecopy($preview, $skin, (4 - $armWidth) * $mp, 8 * $mp, 44 * $mp, 36 * $mp, $armWidth * $mp, 12 * $mp);
                // left
                imagecopy($preview, $skin, 12 * $mp, 8 * $mp, 52 * $mp, 52 * $mp, $armWidth * $mp, 12 * $mp);

                // jacket
                imagecopy($preview, $skin, 4 * $mp, 8 * $mp, 20 * $mp, 36 * $mp, 8 * $mp, 12 * $mp);

                // front legs right leg layer 2
                imagecopy($preview, $skin, 4 * $mp, 20 * $mp, 4 * $mp, 36 * $mp, 4 * $mp, 12 * $mp);
                // front legs  left leg layer 2
                imagecopy($preview, $skin, 8 * $mp, 20 * $mp, 4 * $mp, 52 * $mp, 4 * $mp, 12 * $mp);
            }

        }
        if ($this->side === null or $this->side === self::SIDE_BACK) {
            $mp_x_h = ($this->side) ? 0 : imagesx($preview) / 2; // base padding left on output canvas, if render both sides on the same image
            $backArmPos = ($armWidth * 2);

            // front side of arm have width 3, but back still able have width 4, so skip pixels at begining
            if ($armWidth < 4) {
                $backArmPos += 4 - $armWidth;
            }

            // head
            imagecopy($preview, $skin, $mp_x_h + 4 * $mp, 0 * $mp, 24 * $mp, 8 * $mp, 8 * $mp, 8 * $mp);
            imagecopy($preview, $skin, $mp_x_h + 4 * $mp, 0 * $mp, 56 * $mp, 8 * $mp, 8 * $mp, 8 * $mp);

            // body back
            imagecopy($preview, $skin, $mp_x_h + 4 * $mp, 8 * $mp, 32 * $mp, 20 * $mp, 8 * $mp, 12 * $mp);

            // back arm, calc from start right arm zone base on arm width

            imagecopy($preview, $skin, $mp_x_h + 12 * $mp, 8 * $mp, (44 + $backArmPos) * $mp, 20 * $mp, $armWidth * $mp, 12 * $mp);

            // flip left arm for old skins

            if ($info['ratio'] == 2) {
                $this->imageflip($preview, $skin, $mp_x_h + 0 * $mp, 8 * $mp, 52 * $mp, 20 * $mp, 4 * $mp, 12 * $mp);
            } else {
                imagecopy($preview, $skin, $mp_x_h + (4 - $armWidth) * $mp, 8 * $mp, (36 + $backArmPos) * $mp, 52 * $mp, $armWidth * $mp, 12 * $mp);
            }

            // back leg

            // left
            if ($info['ratio'] == 2) {
                $this->imageflip($preview, $skin, $mp_x_h + 4 * $mp, 20 * $mp, 12 * $mp, 20 * $mp, 4 * $mp, 12 * $mp);
            } else {
                imagecopy($preview, $skin, $mp_x_h + 4 * $mp, 20 * $mp, 28 * $mp, 52 * $mp, 4 * $mp, 12 * $mp);
            }

            // right
            imagecopy($preview, $skin, $mp_x_h + 8 * $mp, 20 * $mp, 12 * $mp, 20 * $mp, 4 * $mp, 12 * $mp);

            // addition attributes for new skins (v 1.8 >)
            if ($info['ratio'] == 1) {
                // jaket
                imagecopy($preview, $skin, $mp_x_h + 4 * $mp, 8 * $mp, 32 * $mp, 36 * $mp, 8 * $mp, 12 * $mp);
                // back arm decals right arm
                imagecopy($preview, $skin, $mp_x_h + 12 * $mp, 8 * $mp, (44 + $backArmPos) * $mp, 20 * $mp, $armWidth * $mp, 12 * $mp);
                // back arm decals left arm
                imagecopy($preview, $skin, $mp_x_h + (4 - $armWidth) * $mp, 8 * $mp, (52 + $backArmPos) * $mp, 52 * $mp, $armWidth * $mp, 12 * $mp);
                // back leg decals 2 right leg
                imagecopy($preview, $skin, $mp_x_h + 8 * $mp, 20 * $mp, 12 * $mp, 36 * $mp, 4 * $mp, 12 * $mp);
                // back leg decals 2 left leg
                imagecopy($preview, $skin, $mp_x_h + 4 * $mp, 20 * $mp, 12 * $mp, 52 * $mp, 4 * $mp, 12 * $mp);
            }
        }

        $mp_cloak = null;
        if ($this->wayCloak and !$info = $this->isValidCloak()) {
            $this->wayCloak = null;
        } else {
            $mp_cloak = $info['scale'];
        }

        $cloak = @imagecreatefrompng($this->wayCloak);
        if (!$cloak) {
            $way_cloak = null;
        }

        if ($this->wayCloak) {
            if ($mp_cloak > $mp) { // cloak bigger
                $mp_x_h = ($this->side) ? 0 : ($size_x * $mp_cloak) / 2;
                $mp_result = $mp_cloak;
            } else {
                $mp_x_h = ($this->side) ? 0 : ($size_x * $mp) / 2;
                $mp_result = $mp;
            }

            $preview_cloak = imagecreatetruecolor($size_x * $mp_result, 32 * $mp_result);
            $transparent = imagecolorallocatealpha($preview_cloak, 255, 255, 255, 127);
            imagefill($preview_cloak, 0, 0, $transparent);

            // ex. copy front side of cloak to new image
            if (!$this->side or $this->side === 'front') {
                imagecopyresized(
                    $preview_cloak, // result image
                    $cloak, // source image
                    round(3 * $mp_result), // start x point of result
                    round(8 * $mp_result), // start y point of result
                    round(12 * $mp_cloak), // start x point of source img
                    round(1 * $mp_cloak), // start y point of source img
                    round(10 * $mp_result), // result <- width ->
                    round(16 * $mp_result), // result /|\ height \|/
                    round(10 * $mp_cloak), // width of cloak img (from start x \ y)
                    round(16 * $mp_cloak) // height of cloak img (from start x \ y)
                );
            }

            imagecopyresized($preview_cloak, $preview, 0, 0, 0, 0, imagesx($preview_cloak), imagesy($preview_cloak), imagesx($preview), imagesy($preview));

            if (!$this->side or $this->side === 'back') {
                imagecopyresized(
                    $preview_cloak,
                    $cloak,
                    $mp_x_h + 3 * $mp_result,
                    round(8 * $mp_result),
                    round(1 * $mp_cloak),
                    round(1 * $mp_cloak),
                    round(10 * $mp_result),
                    round(16 * $mp_result),
                    round(10 * $mp_cloak),
                    round(16 * $mp_cloak)
                );
            }

            $preview = $preview_cloak;
        }

        $size_x = ($this->side) ? $size / 2 : $size;
        $fullsize = imagecreatetruecolor($size_x, $size);

        imagesavealpha($fullsize, true);
        $transparent = imagecolorallocatealpha($fullsize, 255, 255, 255, 127);
        imagefill($fullsize, 0, 0, $transparent);

        imagecopyresized($fullsize, $preview, 0, 0, 0, 0, imagesx($fullsize), imagesy($fullsize), imagesx($preview), imagesy($preview));

        imagedestroy($preview);
        imagedestroy($skin);
        if ($this->wayCloak) {
            imagedestroy($cloak);
        }

        return $fullsize;
    }

    /**
     * @return bool|resource
     */
    public function savePreview()
    {
        if (file_exists($this->saveSkin)) {
            unlink($this->saveSkin);
        }

        $new_skin = $this->createPreview();
        if (!$new_skin) {
            return false;
        }

        imagepng($new_skin, $this->saveSkin);
        return $new_skin;
    }

    /**
     * @return bool|resource
     */
    public function saveHead()
    {
        if (file_exists($this->saveHead)) {
            unlink($this->saveHead);
        }

        $new_head = $this->createHead();
        if (!$new_head) {
            return false;
        }

        imagepng($new_head, $this->saveHead);
        return $new_head;
    }

    /**
     * @return array|bool
     */
    public function isValidSkin()
    {
        if (!file_exists($this->waySkin)) {
            return false;
        }

        if (!$imageSize = $this->getImageSize($this->waySkin)) {
            return false;
        }


        for ($i = 0; $i < sizeof($this->skinProps); $i++) {
            if (round($this->skinProps[$i]['ratio'], 2) != $this->getRatio($imageSize)) {
                continue;
            }

            return [
                'ratio' => $this->getRatio($imageSize),
                'scale' => $this->getScale($imageSize, $this->skinProps[$i]['base']),
            ];
        }

        return false;
    }

    /**
     * @return array|bool
     */
    public function isValidCloak()
    {
        if (!file_exists($this->wayCloak)) {
            return false;
        }

        if (!$imageSize = $this->getImageSize($this->wayCloak)) {
            return false;
        }

        for ($i = 0; $i < sizeof($this->cloakProps); $i++) {
            if (round($this->cloakProps[$i]['ratio'], 2) != $this->getRatio($imageSize)) {
                continue;
            }

            return [
                'ratio' => $this->cloakProps[$i]['ratio'],
                'scale' => $this->getScale($imageSize, $this->cloakProps[$i]['base']),
            ];
        }

        return false;
    }

    /**
     * @param $inputImg
     * @param integer $size
     * @return bool|float|int
     */
    private function getScale($inputImg, $size)
    {
        if (!is_array($inputImg) and !$inputImg = $this->getImageSize($inputImg)) {
            return false;
        }

        return $inputImg[0] / $size;
    }

    /**
     * @param $inputImg
     * @return bool|float
     */
    private function getRatio($inputImg)
    {
        if (!is_array($inputImg) and !$inputImg = $this->getImageSize($inputImg)) {
            return false;
        }

        return round($inputImg[0] / $inputImg[1], 2);
    }

    /**
     * @param $file
     * @return array|bool
     */
    private function getImageSize($file)
    {
        $imageSize = @getimagesize($file);
        if (empty($imageSize)) {
            return false;
        }

        return $imageSize;
    }

    /**
     * @param $result
     * @param $img
     * @param int $rx
     * @param int $ry
     * @param int $x
     * @param int $y
     * @param null $size_x
     * @param null $size_y
     */
    private function imageflip(&$result, &$img, $rx = 0, $ry = 0, $x = 0, $y = 0, $size_x = null, $size_y = null)
    {
        if ($size_x < 1) {
            $size_x = imagesx($img);
        }
        if ($size_y < 1) {
            $size_y = imagesy($img);
        }

        imagecopyresampled($result, $img, $rx, $ry, ($x + $size_x - 1), $y, $size_x, $size_y, 0 - $size_x, $size_y);
    }

    /**
     * @param string $waySkin
     * @return SkinView
     */
    public function setWaySkin($waySkin)
    {
        $this->waySkin = $waySkin;
        return $this;
    }

    /**
     * @param string $wayCloak
     * @return SkinView
     */
    public function setWayCloak($wayCloak)
    {
        $this->wayCloak = $wayCloak;
        return $this;
    }

    /**
     * @param string $saveSkin
     * @return SkinView
     */
    public function setSaveSkin($saveSkin)
    {
        $this->saveSkin = $saveSkin;
        return $this;
    }

    /**
     * @param string $saveHead
     * @return SkinView
     */
    public function setSaveHead($saveHead)
    {
        $this->saveHead = $saveHead;
        return $this;
    }

    /**
     * @param string $side
     * @return SkinView
     */
    public function setSide($side)
    {
        $this->side = $side;
        return $this;
    }

    /**
     * @param int $size
     * @return SkinView
     */
    public function setSize($size)
    {
        $this->size = $size;
        return $this;
    }
}
