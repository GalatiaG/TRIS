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

-- Utenti di prova
INSERT INTO `giocatori` (`id`, `username`, `password`, `vittorie`, `sconfitte`, `pareggi`) VALUES
(1, 'studente_test', '$2y$10$c13Yml8ZgVshxJw0GA/LmOvfKHAzrvmCeziWGcZ5lVrQMvDI7J4nu', 5, 2, 3),
(2, 'giocatore1', '$2y$10$i6d9Yt4822rSf6Y7MXebCeJpVamhLMTCUfh.P31w.TqfRozi8xJpW', 12, 4, 6);
COMMIT;