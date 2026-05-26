<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost"; 
$user = "root";
$pass = ""; 
$db   = "tictactoe";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Errore di connessione al database: " . $conn->connect_error);
}

function verificaLimiteRichieste($connessioneDb, $indirizzoIp) {
    $sogliaMassima = 5; 
    $finestraMinuti = 2; 
    $connessioneDb->query("DELETE FROM login_attempts WHERE last_attempt < (NOW() - INTERVAL $finestraMinuti MINUTE)");
    $comandoSql = $connessioneDb->prepare("SELECT attempts FROM login_attempts WHERE ip_address = ?");
    $comandoSql->bind_param("s", $indirizzoIp);
    $comandoSql->execute();
    $datiRaccolti = $comandoSql->get_result();
    if ($rigaAttuale = $datiRaccolti->fetch_assoc()) {
        if ($rigaAttuale['attempts'] >= $sogliaMassima) {
            return false; 
        }
    }
    return true; 
}

function mappaTentativoFallito($connessioneDb, $indirizzoIp) {
    $querySalvataggio = "INSERT INTO login_attempts (ip_address, attempts) VALUES (?, 1) 
                         ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = CURRENT_TIMESTAMP";
    $comandoSql = $connessioneDb->prepare($querySalvataggio);
    $comandoSql->bind_param("s", $indirizzoIp);
    $comandoSql->execute();
}

function resettaCronologiaIp($connessioneDb, $indirizzoIp) {
    $comandoSql = $connessioneDb->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
    $comandoSql->bind_param("s", $indirizzoIp);
    $comandoSql->execute();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['azione_autenticazione'])) {
    header('Content-Type: application/json');
    $azione = $_POST['azione_autenticazione'];
    $indirizzoIp = $_SERVER['REMOTE_ADDR'];

    if ($azione === 'login') {
        $utenteLogin = trim($_POST['user'] ?? '');
        $passwordLogin = $_POST['pass'] ?? '';

        if (empty($utenteLogin) || empty($passwordLogin)) {
            echo json_encode(['success' => false, 'message' => 'Compila tutti i campi richiesti.']);
            exit;
        }

        $controlloAccesso = $conn->prepare("SELECT username FROM giocatori WHERE username = ? AND password = ?");
        $controlloAccesso->bind_param("ss", $utenteLogin, $passwordLogin);
        $controlloAccesso->execute();
        $risultatoQuery = $controlloAccesso->get_result();

        if ($risultatoQuery->num_rows === 1) {
            $datiProfilo = $risultatoQuery->fetch_assoc();
            $_SESSION['username'] = $datiProfilo['username'];
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Nome utente o password non validi.']);
        }
        exit;
    }

    if ($azione === 'registrazione') {
        if (!verificaLimiteRichieste($conn, $indirizzoIp)) {
            echo json_encode(["success" => false, "message" => "Troppi tentativi! Riprova tra 2 minuti."]);
            exit;
        }

        $captchaUtente = isset($_POST['captcha_input']) ? strtoupper(trim($_POST['captcha_input'])) : '';
        $captchaCorretto = isset($_SESSION['captcha_result']) ? $_SESSION['captcha_result'] : '';

        if ($captchaUtente !== $captchaCorretto) {
            mappaTentativoFallito($conn, $indirizzoIp); 
            echo json_encode(["success" => false, "message" => "CAPTCHA errato! Riprova."]);
            exit;
        }
        unset($_SESSION['captcha_result']);

        $utenteScelto = $conn->real_escape_string($_POST['user']);
        $passwordScelta = $_POST['pass']; 
        $controlloPassword = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@#!$])[A-Za-z\d@#!$]{8,12}$/';

        if (!preg_match($controlloPassword, $passwordScelta)) {
            mappaTentativoFallito($conn, $indirizzoIp); 
            echo json_encode(["success" => false, "message" => "La password non rispetta i criteri!"]);
            exit;
        }

        $controlloDuplicati = $conn->query("SELECT * FROM giocatori WHERE username = '$utenteScelto'");
        if ($controlloDuplicati->num_rows > 0) {
            mappaTentativoFallito($conn, $indirizzoIp); 
            echo json_encode(["success" => false, "message" => "Username già esistente!"]);
        } else {
            $queryInserimento = "INSERT INTO giocatori (username, password, vittorie, sconfitte, pareggi) VALUES ('$utenteScelto', '$passwordScelta', 0, 0, 0)";
            if ($conn->query($queryInserimento)) {
                resettaCronologiaIp($conn, $indirizzoIp);
                echo json_encode(["success" => true, "message" => "Registrazione completata con successo!"]);
            } else {
                echo json_encode(["success" => false, "message" => "Errore di registrazione nel database."]);
            }
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['result'])) {
    header('Content-Type: application/json');
    if (isset($_SESSION['username'])) {
        $utenteConnesso = $_SESSION['username'];
        $esitoPartita = $_POST['result']; 

        if ($esitoPartita === 'vittoria') {
            $conn->query("UPDATE giocatori SET vittorie = vittorie + 1 WHERE username = '$utenteConnesso'");
        } 
        if ($esitoPartita === 'sconfitta') {
            $conn->query("UPDATE giocatori SET sconfitte = sconfitte + 1 WHERE username = '$utenteConnesso'");
        } 
        if ($esitoPartita === 'pareggio') {
            $conn->query("UPDATE giocatori SET pareggi = pareggi + 1 WHERE username = '$utenteConnesso'");
        }
        if ($esitoPartita === 'reset') {
            $conn->query("UPDATE giocatori SET vittorie = 0, sconfitte = 0, pareggi = 0 WHERE username = '$utenteConnesso'");
        }

        $selezionaDati = $conn->query("SELECT vittorie, sconfitte, pareggi FROM giocatori WHERE username = '$utenteConnesso'");
        $recordStatistiche = $selezionaDati->fetch_assoc();

        echo json_encode([
            'vittorie' => $recordStatistiche['vittorie'] ?? 0,
            'sconfitte' => $recordStatistiche['sconfitte'] ?? 0,
            'pareggi' => $recordStatistiche['pareggi'] ?? 0
        ]);
    } else {
        echo json_encode(['error' => 'Utente non autenticato']);
    }
    exit;
}
?>