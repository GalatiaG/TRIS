SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Creazione e selezione del database principale
CREATE DATABASE IF NOT EXISTS `tictactoe` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `tictactoe`;

-- Struttura tabella per la gestione degli utenti e delle loro statistiche di gioco
CREATE TABLE `giocatori` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `vittorie` int(11) DEFAULT 0,
  `sconfitte` int(11) DEFAULT 0,
  `pareggi` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Inserimento utenti iniziali (le password rispettano i criteri di sicurezza dell'esame)
INSERT INTO `giocatori` (`id`, `username`, `password`, `vittorie`, `sconfitte`, `pareggi`) VALUES
(1, 'studente_test', 'Ancona@2026', 5, 2, 3),
(2, 'giocatore1', 'Milano#99!', 12, 4, 6);

-- Struttura tabella per il controllo dei tentativi di richiesta (Sicurezza Anti-Flood)
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `ip_address` varchar(45) NOT NULL,
  `attempts` int(11) NOT NULL DEFAULT 1,
  `last_attempt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;