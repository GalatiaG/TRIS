<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// connessione al database
$conn = @new mysqli("localhost", "root", "", "tictactoe");

if (!isset($_SESSION['board'])) {
    $_SESSION['board'] = array_fill(0, 9, '');
    $_SESSION['wins'] = 0;
    $_SESSION['losses'] = 0;
    $_SESSION['draws'] = 0;
    $_SESSION['game_active'] = false;
}

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}
if (isset($_POST['start'])) {
    $_SESSION['board'] = array_fill(0, 9, '');
    $_SESSION['game_active'] = true;
}
if (isset($_POST['menu'])) {
    $_SESSION['board'] = array_fill(0, 9, '');
    $_SESSION['game_active'] = false;
}
if (isset($_POST['restart'])) {
    $_SESSION['board'] = array_fill(0, 9, '');
}

function controlla_vittoria_dettagli($b) {
    $linee = [
        'r0' => [0,1,2], 'r1' => [3,4,5], 'r2' => [6,7,8],
        'c0' => [0,3,6], 'c1' => [1,4,7], 'c2' => [2,5,8],
        'd0' => [0,4,8], 'd1' => [2,4,6]
    ];
    foreach ($linee as $tipo => $l) {
        if ($b[$l[0]] !== '' && $b[$l[0]] === $b[$l[1]] && $b[$l[0]] === $b[$l[2]]) {
            return ['vincitore' => $b[$l[0]], 'linea' => $l, 'tipo' => $tipo];
        }
    }
    return !in_array('', $b) ? ['vincitore' => 'pareggio', 'linea' => [], 'tipo' => ''] : ['vincitore' => null, 'linea' => [], 'tipo' => ''];
}

$risultato_dettagli = controlla_vittoria_dettagli($_SESSION['board']);
$stato = $risultato_dettagli['vincitore'];
$linea_vincente = $risultato_dettagli['linea'];
$tipo_vittoria = $risultato_dettagli['tipo'];

if (isset($_POST['cell']) && $_SESSION['game_active'] && $stato === null) {
    $idx = (int)$_POST['cell'];
    if ($idx >= 0 && $idx <= 8 && $_SESSION['board'][$idx] === '') {
        $_SESSION['board'][$idx] = 'X';
        
        $risultato_dettagli = controlla_vittoria_dettagli($_SESSION['board']);
        if ($risultato_dettagli['vincitore'] === null) {
            $libere = [];
            for ($i = 0; $i < 9; $i++) if ($_SESSION['board'][$i] === '') $libere[] = $i;
            if (!empty($libere)) {
                $_SESSION['board'][$libere[array_rand($libere)]] = 'O';
            }
        }
        
        $risultato_dettagli = controlla_vittoria_dettagli($_SESSION['board']);
        $stato = $risultato_dettagli['vincitore'];
        $linea_vincente = $risultato_dettagli['linea'];
        $tipo_vittoria = $risultato_dettagli['tipo'];
        
        if ($stato === 'X') $_SESSION['wins']++;
        elseif ($stato === 'O') $_SESSION['losses']++;
        elseif ($stato === 'pareggio') $_SESSION['draws']++;

        // salvo il risultato della partita nel database
        if (!$conn->connect_error && $stato !== null) {
            if ($stato === 'X') {
                $sql = "UPDATE giocatori SET vittorie = vittorie + 1 WHERE username = ?";
            } elseif ($stato === 'O') {
                $sql = "UPDATE giocatori SET sconfitte = sconfitte + 1 WHERE username = ?";
            } else {
                $sql = "UPDATE giocatori SET pareggi = pareggi + 1 WHERE username = ?";
            }
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// leggo dal database il punteggio totale del giocatore
if (!$conn->connect_error) {
    $stmt = $conn->prepare("SELECT vittorie, sconfitte, pareggi FROM giocatori WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($vittorie, $sconfitte, $pareggi);
    if ($stmt->fetch()) {
        $_SESSION['wins'] = $vittorie;
        $_SESSION['losses'] = $sconfitte;
        $_SESSION['draws'] = $pareggi;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tris Area Riservata</title>
    <style>
        *, ::before, ::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: #141719; background-image: url('sfondo.png'); 
            background-size: cover; background-position: center; background-repeat: no-repeat; background-attachment: fixed;
            color: #f5f5f5; font-family: 'Chalkboard SE', 'Comic Sans MS', sans-serif;
            display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 10px;
            user-select: none;
        }
        .game-container { width: 100%; max-width: 600px; text-align: center; }
        
        /* INTESTAZIONE */
        .header-players {
            font-size: 1.6rem;
            font-weight: normal;
            color: #ffffff;
            margin-bottom: 5px;
            text-shadow: 1px 1px 2px #000;
        }
        .header-players .neon-x {
            color: #ffcccc;
            font-weight: bold;
            text-shadow: 0 0 8px #990000;
        }
        .header-players .neon-o {
            color: #e6f0ff;
            font-weight: bold;
            text-shadow: 0 0 8px #0044cc;
        }

        .divider-dashed {
            border-top: 1px dashed rgba(255, 255, 255, 0.2);
            width: 60%;
            margin: 10px auto;
        }
        
        .status-text { 
            font-size: 1.4rem; 
            margin: 10px 0 25px 0; 
            font-weight: normal; 
            color: #ffffff; 
            text-shadow: 1px 1px 3px #000; 
            height: 30px;
        }

        /* BOX INIZIALE */
        .setup-box { background: rgba(0, 0, 0, 0.6); border: 2px dashed rgba(255, 255, 255, 0.2); border-radius: 8px; padding: 25px; text-align: left; }
        .form-group-chalk label { display: block; margin-bottom: 8px; font-size: 1.2rem; text-transform: uppercase; color: #fff; text-shadow: 1px 1px 3px #000; }
        .form-group-chalk input, .form-group-chalk select { width: 100%; padding: 12px; background: rgba(0, 0, 0, 0.6); color: #fff; border: 2px dashed rgba(255, 255, 255, 0.3); font-family: inherit; font-size: 1.1rem; border-radius: 6px; }

        /* STATISTICHE */
        .scoreboard { 
            font-size: 1.1rem; 
            background: rgba(0, 0, 0, 0.75); 
            padding: 10px 25px; 
            border-radius: 6px; 
            margin: 25px 0 15px 0; 
            display: inline-block; 
            border: 1px solid rgba(255,255,255,0.15); 
        }
        .scoreboard .sb-x { color: #ffcccc; font-weight: bold; }

        /* CONTENITORE DELLA GRIGLIA */
        .board-wrapper {
            position: relative;
            width: 360px;
            height: 360px;
            margin: 0 auto;
        }

        /* GRIGLIA 3X3 */
        .tris-grid { 
            display: grid; 
            grid-template-columns: repeat(3, 120px); 
            grid-template-rows: repeat(3, 120px); 
            width: 360px;
            height: 360px;
        }
        
        .container-cell { 
            position: relative; 
            width: 120px; 
            height: 120px;
        }
        
        .tris-cell { 
            width: 120px; 
            height: 120px; 
            background: transparent;
            font-size: 4.8rem; 
            font-weight: bold; 
            font-family: inherit;
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            border: none; 
            outline: none;
            padding: 0;
            margin: 0;
        }
        
        /* BORDI EFFETTO GESSO */
        .container-cell:nth-child(1) .tris-cell, .container-cell:nth-child(2) .tris-cell, 
        .container-cell:nth-child(4) .tris-cell, .container-cell:nth-child(5) .tris-cell, 
        .container-cell:nth-child(7) .tris-cell, .container-cell:nth-child(8) .tris-cell { 
            border-right: 8px solid rgba(255, 255, 255, 0.8); 
            box-shadow: 3px 0 6px rgba(255, 255, 255, 0.3), inset -2px 0 4px rgba(255, 255, 255, 0.2);
        }
        .container-cell:nth-child(1) .tris-cell, .container-cell:nth-child(2) .tris-cell, 
        .container-cell:nth-child(3) .tris-cell, .container-cell:nth-child(4) .tris-cell, 
        .container-cell:nth-child(5) .tris-cell, .container-cell:nth-child(6) .tris-cell { 
            border-bottom: 8px solid rgba(255, 255, 255, 0.8); 
            box-shadow: 0 3px 6px rgba(255, 255, 255, 0.3), inset 0 -2px 4px rgba(255, 255, 255, 0.2);
        }
        
        /* ANGOLI INTERNI */
        .container-cell:nth-child(1) .tris-cell, .container-cell:nth-child(2) .tris-cell,
        .container-cell:nth-child(4) .tris-cell, .container-cell:nth-child(5) .tris-cell {
            box-shadow: 3px 3px 6px rgba(255, 255, 255, 0.4);
        }
        
        /* SIMBOLI X E O */
        .cell-X { 
            color: #ffcccc !important; 
            text-shadow: 0 0 4px #fff, 0 0 14px #990000, 0 0 25px #660000; 
        }
        .cell-O { 
            color: #e6f0ff !important; 
            text-shadow: 0 0 4px #fff, 0 0 14px #0044cc, 0 0 25px #002288; 
        }

        /* LINEA DI VITTORIA */
        .chalk-strike-line {
            position: absolute;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 12px rgba(255, 255, 255, 0.9), 0 0 4px rgba(255, 255, 255, 1);
            border-radius: 4px;
            z-index: 99;
            pointer-events: none;
            transform-origin: left center;
            animation: draw-strike 0.25s ease-out forwards;
        }

        .strike-r0 { top: 56px; left: 0; width: 360px; height: 8px; }
        .strike-r1 { top: 176px; left: 0; width: 360px; height: 8px; }
        .strike-r2 { top: 296px; left: 0; width: 360px; height: 8px; }
        
        .strike-c0 { top: 0; left: 56px; width: 8px; height: 360px; transform-origin: top center; animation: draw-strike-v 0.25s ease-out forwards; }
        .strike-c1 { top: 0; left: 176px; width: 8px; height: 360px; transform-origin: top center; animation: draw-strike-v 0.25s ease-out forwards; }
        .strike-c2 { top: 0; left: 296px; width: 8px; height: 360px; transform-origin: top center; animation: draw-strike-v 0.25s ease-out forwards; }
        
        .strike-d0 { top: 0; left: 0; width: 509px; height: 8px; transform: rotate(45deg); }
        .strike-d1 { top: 0; left: 360px; width: 509px; height: 8px; transform: rotate(135deg); }

        @keyframes draw-strike { from { transform: scaleX(0); } }
        @keyframes draw-strike-v { from { transform: scaleY(0); } }

        /* PULSANTI */
        .btn-row { display: flex; gap: 15px; justify-content: center; margin-top: 5px; }
        .btn-flat { 
            padding: 10px 22px; 
            font-size: 1rem; 
            font-weight: bold; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            font-family: inherit;
            text-transform: uppercase;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }
        
        .btn-lime { background: #32cd32 !important; color: #000; } 
        .btn-arancio { background: #ffb366 !important; color: #000; } 
        
        .btn-verde-start { background: #32cd32 !important; color: #000; border-radius: 6px; padding: 12px 25px; font-size: 1.1rem;}
        .btn-rosso-logout { background: #ff4d4d !important; color: #fff; border-radius: 6px; padding: 12px 25px; font-size: 1.1rem;}
    </style>
</head>
<body>
    <div class="game-container">

        <?php if (!$_SESSION['game_active']): ?>
            <form method="POST" action="">
                <div class="setup-box">
                    <div class="form-group-chalk"><label>NOME GIOCATORE 1 (X):</label><input type="text" value="<?php echo htmlspecialchars($username); ?>" readonly></div>
                    <div class="btn-row" style="margin-top:20px;">
                        <button type="submit" name="start" class="btn-verde-start">START</button>
                        <button type="submit" name="logout" class="btn-rosso-logout">LOG OUT</button>
                    </div>
                </div>
            </form>
        <?php else: ?>
            
            <div class="header-players">
                <span class="neon-x">X</span> <?php echo htmlspecialchars($username); ?> vs <span class="neon-o">O</span> Computer
            </div>
            
            <div class="divider-dashed"></div>
            
            <p class="status-text">
                <?php 
                if ($stato === 'X') {
                    echo "Hai vinto!";
                } elseif ($stato === 'O') {
                    echo "Ha vinto Computer!";
                } elseif ($stato === 'pareggio') {
                    echo "Pareggio!";
                } else {
                    echo "Tocca a te!";
                }
                ?>
            </p>
            
            <form method="POST" action="">
                
                <div class="board-wrapper">
                    
                    <?php if ($stato !== null && $stato !== 'pareggio'): ?>
                        <div class="chalk-strike-line strike-<?php echo $tipo_vittoria; ?>"></div>
                    <?php endif; ?>

                    <div class="tris-grid">
                        <?php for($i = 0; $i < 9; $i++): 
                            $val = $_SESSION['board'][$i]; 
                            $cl = ($val !== '') ? 'cell-' . $val : '';
                        ?>
                            <div class="container-cell">
                                <button type="submit" name="cell" value="<?php echo $i; ?>" class="tris-cell <?php echo $cl; ?>" <?php echo ($val !== '' || $stato !== null) ? 'disabled' : ''; ?>><?php echo $val; ?></button>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div class="scoreboard">
                    <span class="sb-x">X</span> Vittorie: <?php echo $_SESSION['wins']; ?> | Sconfitte: <?php echo $_SESSION['losses']; ?> | Pareggi: <?php echo $_SESSION['draws']; ?>
                </div>
                
                <div class="btn-row">
                    <button type="submit" name="restart" class="btn-flat btn-lime">RESTART</button>
                    <button type="submit" name="menu" class="btn-flat btn-arancio">MENU</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
