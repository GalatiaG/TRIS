<?php
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

header('Content-Type: image/png');

$caratteri = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$codice = '';

for ($i = 0; $i < 4; $i++) {
    $posizione = rand(0, strlen($caratteri) - 1);
    $codice .= $caratteri[$posizione];
}

$_SESSION['captcha_result'] = $codice;

$immagine = imagecreatetruecolor(90, 35);
$verdeLavagna = imagecolorallocate($immagine, 30, 63, 32);
$gessoBianco  = imagecolorallocate($immagine, 255, 255, 255);

imagefill($immagine, 0, 0, $verdeLavagna);
imagestring($immagine, 5, 20, 10, $codice, $gessoBianco);

for ($i = 0; $i < 3; $i++) {
    imageline($immagine, rand(0, 90), rand(0, 35), rand(0, 90), rand(0, 35), $gessoBianco);
}

imagepng($immagine);
imagedestroy($immagine);
?>