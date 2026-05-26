<?php
// Inizia la sessione se non è già attiva
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Svuota tutte le variabili di sessione
$_SESSION = array();

// Distrugge completamente la sessione lato server
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Reindirizza l'utente alla pagina di login
header("Location: login.php");
exit();
?>