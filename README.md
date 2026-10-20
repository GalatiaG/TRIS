# Tris – gioco web con login (PHP e MySQL)

Esercizio del corso "Software Developer for Web Application" (ITS Prodigi, Prato), modulo database e PHP, 2026.

Il giocatore si registra o accede, poi gioca a tris contro il computer. Vittorie, sconfitte e pareggi vengono salvati nel database e restano anche dopo il logout.

## Funzioni
- Registrazione con controllo captcha e password salvata con `password_hash`.
- Login con query parametrizzate (`prepare` / `bind_param`).
- Partita contro il computer, con controllo di vittoria e pareggio lato server (PHP e sessioni).
- Salvataggio del risultato di ogni partita nella tabella `giocatori` (query `UPDATE` parametrizzata).
- Logout che chiude la sessione.

## Tecnologie
PHP, MySQL (phpMyAdmin), HTML e CSS. Testato in locale con XAMPP.

## Come avviarlo
1. Copia la cartella in `xampp/htdocs/tictactoe`.
2. Avvia Apache e MySQL dal pannello di XAMPP.
3. Apri http://localhost/phpmyadmin e importa `tictactoe_database.sql`.
4. Apri http://localhost/tictactoe/login.php.

Utente di prova: `studente_test`, password `Ancona@2026`.

## File
| File | Ruolo |
|---|---|
| login.php | accesso e registrazione |
| captcha_gen.php | immagine del codice captcha |
| index.php | partita e punteggio |
| logout.php | chiusura della sessione |
| tictactoe_database.sql | creazione del database e utenti di prova |
