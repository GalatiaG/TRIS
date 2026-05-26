<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$errore = "";
$successo = "";

// 1. Generatore di stringa di sicurezza randomica se vuota
if (!isset($_SESSION['captcha_segreto'])) {
    $caratteri = 'ABCDEFGHKMNPQRSTUVWXYZ23456789'; 
    $codice = '';
    for ($i = 0; $i < 5; $i++) {
        $codice .= $caratteri[rand(0, strlen($caratteri) - 1)];
    }
    $_SESSION['captcha_segreto'] = $codice;
}

// 2. Controllo della verifica al click del pulsante
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $captcha_utente = isset($_POST['captcha_code']) ? strtoupper(trim($_POST['captcha_code'])) : '';
    $captcha_corretto = isset($_SESSION['captcha_segreto']) ? $_SESSION['captcha_segreto'] : '';

    if (empty($captcha_utente) || $captcha_utente !== $captcha_corretto) {
        $errore = "ERRORE: VERIFICA UMANA FALLITA! IL CODICE È ERRATO.";
    } else {
        // Qui andrà la tua logica sql INSERT INTO utenti... per salvare l'account creato
        $successo = "REGISTRAZIONE COMPLETATA CON SUCCESSO!";
    }

    // Rigenera il captcha per sicurezza anti-bot ad ogni tentativo
    $caratteri = 'ABCDEFGHKMNPQRSTUVWXYZ23456789';
    $codice = '';
    for ($i = 0; $i < 5; $i++) {
        $codice .= $caratteri[rand(0, strlen($caratteri) - 1)];
    }
    $_SESSION['captcha_segreto'] = $codice;
}

// 3. Generatore grafico vettoriale del codice CAPTCHA
function genera_captcha_svg($testo) {
    $width = 150; $height = 55;
    $svg = "<svg width='$width' height='$height' xmlns='http://www.w3.org/2000/svg' style='background:#ffffff; border-radius:6px; display:block;'>";
    for ($i = 0; $i < 6; $i++) {
        $x1 = rand(0, $width); $y1 = rand(0, $height);
        $x2 = rand(0, $width); $y2 = rand(0, $height);
        $svg .= "<line x1='$x1' y1='$y1' x2='$x2' y2='$y2' stroke='rgba(0,0,0,0.25)' stroke-width='2' />";
    }
    $lunghezza = strlen($testo);
    for ($i = 0; $i < $lunghezza; $i++) {
        $x = 15 + ($i * 26) + rand(-3, 3); $y = 38 + rand(-5, 5);
        $rotazione = rand(-18, 18); $font_size = rand(24, 29);
        $colore = "rgb(" . rand(0, 90) . "," . rand(0, 90) . "," . rand(0, 140) . ")";
        $svg .= "<text x='$x' y='$y' font-family='Arial, sans-serif' font-weight='bold' font-size='$font_size' fill='$colore' transform='rotate($rotazione, $x, $y)'>".$testo[$i]."</text>";
    }
    for ($i = 0; $i < 35; $i++) {
        $cx = rand(0, $width); $cy = rand(0, $height);
        $svg .= "<circle cx='$cx' cy='$cy' r='1.5' fill='rgba(0,0,0,0.25)' />";
    }
    $svg .= "</svg>";
    return $svg;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione - Tris</title>
    <style>
        *, ::before, ::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: #141719;
            background-image: url('sfondo.png'); 
            background-size: cover; background-position: center; background-repeat: no-repeat; background-attachment: fixed;
            color: #f5f5f5; font-family: 'Chalkboard SE', 'Comic Sans MS', sans-serif;
            display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px;
        }
        .signup-container { width: 100%; max-width: 580px; text-align: center; background: transparent; }
        h1.signup-title {
            color: #b026ff !important; text-shadow: 0 0 12px rgba(176, 38, 255, 0.8), 2px 2px 4px #000000 !important;
            font-size: 3.5rem !important; letter-spacing: 3px !important; margin-bottom: 25px;
            border-bottom: 2px dashed rgba(255, 255, 255, 0.15); padding-bottom: 12px; text-transform: uppercase !important; font-weight: bold;
        }
        .form-vertical-stack { display: flex !important; flex-direction: column !important; gap: 18px !important; width: 100% !important; margin-bottom: 20px !important; }
        .form-group-chalk { text-align: left !important; width: 100% !important; }
        .form-group-chalk label {
            display: block !important; margin-bottom: 8px !important; font-size: 1.3rem !important;  
            text-transform: uppercase !important; letter-spacing: 1.5px !important; color: rgba(255, 255, 255, 0.98) !important; text-shadow: 2px 2px 4px #000000 !important;
        }
        .form-group-chalk input {
            width: 100% !important; padding: 15px 18px !important; background: rgba(0, 0, 0, 0.6) !important; color: #fff !important;
            border: 2px dashed rgba(255, 255, 255, 0.4) !important; border-radius: 6px !important; font-size: 1.2rem !important; font-family: inherit !important; box-sizing: border-box !important;
        }
        .form-group-chalk input:focus { outline: none !important; border-color: #fffb00 !important; }
        .password-requirements { list-style: none; text-align: left; margin: 20px 0; padding: 15px 20px; background: rgba(0, 0, 0, 0.4); border-radius: 6px; border: 1px solid rgba(255, 255, 255, 0.1); }
        .req-item { font-size: 1.1rem; margin-bottom: 8px; display: flex; align-items: center; gap: 12px; text-transform: uppercase; text-shadow: 2px 2px 3px rgba(0, 0, 0, 1); }
        .req-item:last-child { margin-bottom: 0; }
        .req-item.invalid { color: #ff4d4d; }
        .req-item .icon { font-weight: bold; font-size: 1.2rem; }
        .captcha-section { border: 2px dashed rgba(0, 204, 204, 0.5) !important; border-radius: 8px !important; padding: 20px !important; margin-bottom: 25px !important; background: rgba(