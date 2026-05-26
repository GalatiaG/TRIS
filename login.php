<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$errore = "";
$successo = "";
$schermata_attiva = "login";

$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "tictactoe";

$conn = @new mysqli($host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    $errore = "Connessione al database fallita!";
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && empty($errore)) {
    if (isset($_POST['azione_login'])) {
        $schermata_attiva = "login";
        $user = trim($_POST['user'] ?? '');
        $pass = $_POST['pwd'] ?? '';

        if ($user !== '' && $pass !== '') {
            $stmt = $conn->prepare("SELECT password FROM giocatori WHERE username = ?");
            if ($stmt) {
                $stmt->bind_param("s", $user);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    $stmt->bind_result($password_salvata);
                    $stmt->fetch();

                    if ($pass === $password_salvata || password_verify($pass, $password_salvata)) {
                        session_regenerate_id(true);
                        $_SESSION['username'] = $user;
                        $_SESSION['board'] = array_fill(0, 9, '');
                        $_SESSION['wins'] = 0; $_SESSION['losses'] = 0; $_SESSION['draws'] = 0;
                        $_SESSION['game_active'] = false;

                        header("Location: index.php");
                        exit();
                    } else {
                        $errore = "Password errata!";
                    }
                } else {
                    $errore = "Utente non trovato!";
                }
                $stmt->close();
            }
        } else {
            $errore = "Compila tutti i campi!";
        }
    }

    if (isset($_POST['azione_registrati'])) {
        $schermata_attiva = "register";
        $new_user = trim($_POST['new_user'] ?? '');
        $new_pass = $_POST['new_pwd'] ?? '';
        $captcha_utente = strtoupper(trim($_POST['captcha_code'] ?? ''));
        $captcha_corretto = $_SESSION['captcha_result'] ?? '';

        if ($new_user === '' || $new_pass === '' || $captcha_utente === '') {
            $errore = "Tutti i campi sono obbligatori!";
        } elseif ($captcha_utente !== $captcha_corretto) {
            $errore = "ERRORE: VERIFICA UMANA FALLITA! IL CODICE È ERRATO.";
        } else {
            $check = $conn->prepare("SELECT id FROM giocatori WHERE username = ?");
            $check->bind_param("s", $new_user);
            $check->execute();
            $check->store_result();
            
            if ($check->num_rows > 0) {
                $errore = "Questo nome utente è già occupato!";
                $check->close();
            } else {
                $check->close();
                $pass_hash = password_hash($new_pass, PASSWORD_BCRYPT);
                $ins = $conn->prepare("INSERT INTO giocatori (username, password, vittorie, sconfitte, pareggi) VALUES (?, ?, 0, 0, 0)");
                
                if ($ins) {
                    $ins->bind_param("ss", $new_user, $pass_hash);
                    if ($ins->execute()) {
                        $successo = "REGISTRAZIONE COMPLETATA! ADESSO PUOI ACCEDERE.";
                        $schermata_attiva = "login"; 
                    } else {
                        $errore = "Errore durante la registrazione.";
                    }
                    $ins->close();
                }
            }
        }
    }
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tris Lavagna - Login</title>
    <style>
        /* CSS INTEGRATO STRUTTURALE - Rimpiazza e forza lo stile della lavagna */
        body {
            background-color: #2c3e50;
            font-family: 'Courier New', Courier, monospace;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
        .chalkboard-container {
            background: #1e4d2b;
            border: 15px solid #8b5a2b;
            border-radius: 10px;
            box-shadow: 0px 10px 30px rgba(0,0,0,0.5), inset 0 0 100px rgba(0,0,0,0.8);
            width: 100%;
            max-width: 480px;
            padding: 30px;
            color: white;
            text-shadow: 1px 1px 2px black;
            box-sizing: border-box;
            text-align: center;
        }
        .hidden-screen {
            display: none !important;
        }
        h1.main-title-chalk {
            font-size: 2.8rem;
            margin-bottom: 20px;
            letter-spacing: 5px;
            border-bottom: 2px dashed white;
            padding-bottom: 10px;
        }
        .form-vertical-stack {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .form-group-chalk {
            text-align: left;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .label-chalk {
            font-weight: bold;
            font-size: 0.9rem;
            letter-spacing: 1px;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            background: rgba(255,255,255,0.1);
            border: 2px solid white;
            border-radius: 4px;
            color: white;
            font-size: 1rem;
            box-sizing: border-box;
        }
        input::placeholder {
            color: rgba(255,255,255,0.4);
        }
        .btn-block {
            width: 100%;
            padding: 12px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            border-radius: 5px;
            border: 2px solid white;
            text-transform: uppercase;
            transition: background 0.2s;
        }
        .btn-green {
            background: #27ae60;
            color: white;
        }
        .btn-green:hover { background: #219653; }
        .btn-grey {
            background: #7f8c8d;
            color: white;
        }
        .btn-grey:hover { background: #6c7a7d; }
        .req-list-compact {
            display: block;
            text-align: left;
            font-size: 0.75rem;
            color: #ff8d8d;
            font-weight: bold;
            line-height: 1.3;
        }
        .captcha-section {
            border: 2px dashed rgba(255,255,255,0.5);
            padding: 10px;
            border-radius: 5px;
            background: rgba(0,0,0,0.2);
            margin: 10px 0;
        }
        .captcha-legend {
            font-size: 0.8rem;
            padding: 0 5px;
        }
        .captcha-control-row {
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: center;
        }
        .captcha-render-box {
            width: 120px;
            height: 40px;
            background: white;
            border-radius: 4px;
            overflow: hidden;
        }
        .captcha-input-wrapper {
            flex: 1;
        }
        .error-msg {
            background: rgba(192, 57, 43, 0.9); border: 2px solid #c0392b;
            color: white; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-weight: bold;
        }
        .success-msg {
            background: rgba(39, 174, 96, 0.9); border: 2px solid #27ae60;
            color: white; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-weight: bold;
        }
        .btn-group {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>

    <div class="chalkboard-container">
        
        <?php if (!empty($errore)): ?>
            <div class="error-msg"><?php echo htmlspecialchars($errore); ?></div>
        <?php endif; ?>
        <?php if (!empty($successo)): ?>
            <div class="success-msg"><?php echo htmlspecialchars($successo); ?></div>
        <?php endif; ?>

        <div id="wrapper-login">
            <h1 class="main-title-chalk">TRIS</h1>
            
            <form method="POST" action="login.php" class="form-vertical-stack">
                <input type="hidden" name="azione_login" value="1">

                <div class="form-group-chalk">
                    <label class="label-chalk">NOME UTENTE:</label>
                    <input type="text" name="user" placeholder="INSERISCI USERNAME" autocomplete="off" required>
                </div>

                <div class="form-group-chalk">
                    <label class="label-chalk">PASSWORD:</label>
                    <input type="password" name="pwd" placeholder="INSERISCI PASSWORD" required>
                </div>

                <button type="submit" class="btn-block btn-green">ENTRA</button>
            </form>
            
            <div class="registration-prompt" style="margin-top: 20px;">
                <span class="prompt-text-chalk" style="font-size:0.85rem; display:block; margin-bottom:5px;">NON HAI UN ACCOUNT?</span>
                <button type="button" class="btn-block btn-grey" onclick="changeScreen('register')">REGISTRATI</button>
            </div>
        </div>

        <div id="wrapper-register" class="hidden-screen">
            <h1 class="main-title-chalk" style="font-size: 2rem;">REGISTRAZIONE</h1>
            
            <form method="POST" action="login.php" class="form-vertical-stack">
                <input type="hidden" name="azione_registrati" value="1">

                <div class="form-group-chalk">
                    <label class="label-chalk">SCEGLI NOME UTENTE:</label>
                    <input type="text" name="new_user" placeholder="NUOVO USERNAME" autocomplete="off" required>
                </div>

                <div class="form-group-chalk">
                    <label class="label-chalk">SCEGLI PASSWORD:</label>
                    <input type="password" name="new_pwd" placeholder="PASSWORD SICURA" required>
                </div>

                <div class="req-list-compact">
                    ✘ 8-12 caratteri (Maiuscola, Minuscola, Numero, Simbolo)
                </div>

                <fieldset class="captcha-section">
                    <legend class="captcha-legend">Verifica Umana</legend>
                    <div class="captcha-control-row">
                        <div class="captcha-render-box">
                            <img src="captcha_gen.php" alt="Captcha" style="height: 100%; width: 100%; object-fit: cover;">
                        </div>
                        <div class="captcha-input-wrapper">
                            <input type="text" id="captcha_code" name="captcha_code" placeholder="CODICE" autocomplete="off" required>
                        </div>
                    </div>
                </fieldset>

                <div class="btn-group">
                    <button type="submit" class="btn-block btn-green">Salva</button>
                    <button type="button" class="btn-block btn-grey" onclick="changeScreen('login')">Annulla</button>
                </div>
            </form>
        </div>

    </div>

    <script>
        function changeScreen(screenName) {
            document.getElementById('wrapper-login').classList.add('hidden-screen');
            document.getElementById('wrapper-register').classList.add('hidden-screen');

            if (screenName === 'login') {
                document.getElementById('wrapper-login').classList.remove('hidden-screen');
            } else if (screenName === 'register') {
                document.getElementById('wrapper-register').classList.remove('hidden-screen');
            }
        }

        // Imposta lo stato iniziale generato dal server
        changeScreen('<?php echo $schermata_attiva; ?>');
    </script>
</body>
</html>