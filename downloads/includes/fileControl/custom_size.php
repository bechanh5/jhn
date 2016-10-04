<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);
$id = isset($_REQUEST['id']) ? abs(intval($_REQUEST['id'])) : 0;

// Скачка изображения в особом размере
$req_down = $db->query("SELECT * FROM `download__files` WHERE `id` = '" . $id . "' AND (`type` = 2 OR `type` = 3)  LIMIT 1");
$res_down = $req_down->fetch();
$format_file = functions::format($res_down['name']);
$pic_ext = ['gif', 'jpg', 'jpeg', 'png'];
$array = ['101x80', '128x128', '128x160', '176x176', '176x208', '176x220', '208x208', '208x320', '240x266', '240x320', '240x432', '352x416', '480x800'];
$size_img = isset($_GET['img_size']) ? abs(intval($_GET['img_size'])) : 0;
$proportion = isset($_GET['proportion']) ? abs(intval($_GET['proportion'])) : 0;
$val = isset($_GET['val']) ? abs(intval($_GET['val'])) : 100;

if ($val < 50 || $val > 100) {
    $val = 100;
}

if (!$req_down->rowCount() || !is_file($res_down['dir'] . '/' . $res_down['name']) || !in_array($format_file, $pic_ext) || ($res_down['type'] == 3 && $rights < 6 && $rights != 4) || empty($array[$size_img])) {
    echo _t('File not found') . '<br><a href="?">' . _t('Downloads') . '</a>';
    exit;
}

$sizs = getimagesize($res_down['dir'] . '/' . $res_down['name']);
$explode = explode('x', $array[$size_img]);
$width = $sizs[0];
$height = $sizs[1];

if ($proportion) {
    $x_ratio = $explode[0] / $width;
    $y_ratio = $explode[0] / $height;
    if (($width <= $explode[0]) && ($height <= $explode[0])) {
        $tn_width = $width;
        $tn_height = $height;
    } else {
        if (($x_ratio * $height) < $explode[0]) {
            $tn_height = ceil($x_ratio * $height);
            $tn_width = $explode[0];
        } else {
            $tn_width = ceil($y_ratio * $width);
            $tn_height = $explode[0];
        }
    }
} else {
    $tn_height = $explode[1];
    $tn_width = $explode[0];
}

switch ($format_file) {
    case "gif":
        $image_create = imagecreatefromgif($res_down['dir'] . '/' . $res_down['name']);
        break;

    case "jpg":
        $image_create = imagecreatefromjpeg($res_down['dir'] . '/' . $res_down['name']);
        break;

    case "jpeg":
        $image_create = imagecreatefromjpeg($res_down['dir'] . '/' . $res_down['name']);
        break;

    case "png":
        $image_create = imagecreatefrompng($res_down['dir'] . '/' . $res_down['name']);
        break;
}


if (!isset($_SESSION['down_' . $id])) {
    $db->exec("UPDATE `download__files` SET `field`=`field`+1 WHERE `id`='" . $id . "'");
    $_SESSION['down_' . $id] = 1;
}

$image = imagecreatetruecolor($tn_width, $tn_height);
imagecopyresized($image, $image_create, 0, 0, 0, 0, $tn_width, $tn_height, $width, $height);

ob_end_clean();
ob_start();
imagejpeg($image, null, $val);
imagedestroy($image);
imagedestroy($image_create);
header('Content-Type: image/jpeg');
header('Content-Disposition: inline; filename=image.jpg');
header('Content-Length: ' . ob_get_length());
flush();
