-- phpMyAdmin SQL Dump
-- version 4.9.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Gegenereerd op: 12 jun 2020 om 23:21
-- Serverversie: 10.4.11-MariaDB
-- PHP-versie: 7.4.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS = @@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION = @@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `twitch_dl`
--
CREATE DATABASE IF NOT EXISTS `twitch_dl` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `twitch_dl`;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `streams`
--

DROP TABLE IF EXISTS `streams`;
CREATE TABLE `streams`
(
    `id`     varchar(57)                     NOT NULL,
    `title`  varchar(200)                    NOT NULL,
    `game`   varchar(100) CHARACTER SET utf8 NOT NULL,
    `status` varchar(25)                     NOT NULL,
    `fkey`   varchar(8)                      NOT NULL,
    `date`   datetime                        NOT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1;

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `streams`
--
ALTER TABLE `streams`
    ADD PRIMARY KEY (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT = @OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS = @OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION = @OLD_COLLATION_CONNECTION */;
