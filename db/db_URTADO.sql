-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost
-- Généré le : mar. 20 mai 2025 à 11:35
-- Version du serveur : 10.5.27-MariaDB
-- Version de PHP : 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `db_URTADO`
--

-- --------------------------------------------------------

--
-- Structure de la table `cars`
--

CREATE TABLE `cars` (
  `id` int(11) NOT NULL,
  `badge` varchar(50) DEFAULT NULL,
  `marque` varchar(100) NOT NULL,
  `modele` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `motorisation` varchar(50) NOT NULL,
  `prix` decimal(10,2) NOT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`images`)),
  `image_url` varchar(255) NOT NULL,
  `available_from` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `staged` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `cars`
--

INSERT INTO `cars` (`id`, `badge`, `marque`, `modele`, `type`, `motorisation`, `prix`, `images`, `image_url`, `available_from`, `created_at`, `staged`) VALUES
(29, 'Premium', 'Ferrari', 'Enzo', 'Coupé', 'Thermique', 10000.00, '[\"assets\\/images\\/cars\\/voiture_29\\/voiture_29_1.webp\",\"assets\\/images\\/cars\\/voiture_29\\/voiture_29_2.jpg\",\"assets\\/images\\/cars\\/voiture_29\\/voiture_29_3.jpeg\"]', 'assets/images/cars/voiture_29/voiture_29_1.webp', '2025-05-19', '2025-05-15 13:38:00', 1),
(30, 'Édition Limitée', 'Porsche', '911 Carrera', 'Coupé', 'Thermique', 2100.00, '[\"assets\\/images\\/cars\\/voiture_30\\/voiture_30_1.jpg\",\"assets\\/images\\/cars\\/voiture_30\\/voiture_30_2.jpg\",\"assets\\/images\\/cars\\/voiture_30\\/voiture_30_3.jpg\"]', 'assets/images/cars/voiture_30/voiture_30_1.jpg', '2025-12-17', '2025-05-17 15:04:56', 1),
(31, 'Exclusivité', 'Rolls-Royce', 'Phantom', 'Berline', 'Thermique', 5000.00, '[\"assets\\/images\\/cars\\/voiture_31\\/voiture_31_1.jpg\",\"assets\\/images\\/cars\\/voiture_31\\/voiture_31_2.jpg\",\"assets\\/images\\/cars\\/voiture_31\\/voiture_31_3.jpg\"]', 'assets/images/cars/voiture_31/voiture_31_1.jpg', '2025-12-09', '2025-05-17 19:52:10', 1),
(32, 'Prestige', 'Bentley', 'Continental GT', 'Coupé', 'Thermique', 3500.00, '[\"assets\\/images\\/cars\\/voiture_32\\/voiture_32_1.jpg\",\"assets\\/images\\/cars\\/voiture_32\\/voiture_32_2.jpg\",\"assets\\/images\\/cars\\/voiture_32\\/voiture_32_3.jpg\"]', 'assets/images/cars/voiture_32/voiture_32_1.jpg', '2025-05-19', '2025-05-17 19:52:10', 1),
(33, 'Premium', 'Mercedes', 'S-Class', 'Berline', 'Thermique', 4000.00, '[\"assets\\/images\\/cars\\/voiture_33\\/voiture_33_1.jpg\",\"assets\\/images\\/cars\\/voiture_33\\/voiture_33_2.jpg\",\"assets\\/images\\/cars\\/voiture_33\\/voiture_33_3.jpg\"]', 'assets/images/cars/voiture_33/voiture_33_1.jpg', '2025-05-19', '2025-05-17 19:52:10', 1),
(34, 'Prestige', 'Aston Martin', 'DB11', 'Coupé', 'Thermique', 3800.00, '[\"assets\\/images\\/cars\\/voiture_34\\/voiture_34_1.jpg\",\"assets\\/images\\/cars\\/voiture_34\\/voiture_34_2.jpg\",\"assets\\/images\\/cars\\/voiture_34\\/voiture_34_3.jpg\"]', 'assets/images/cars/voiture_34/voiture_34_1.jpg', '2025-05-19', '2025-05-17 19:52:10', 1),
(35, 'Performance', 'Ferrari', '812 Superfast', 'Coupé', 'Thermique', 6000.00, '[\"assets\\/images\\/cars\\/voiture_35\\/voiture_35_1.jpg\",\"assets\\/images\\/cars\\/voiture_35\\/voiture_35_2.jpg\",\"assets\\/images\\/cars\\/voiture_35\\/voiture_35_3.jpg\"]', 'assets/images/cars/voiture_35/voiture_35_1.jpg', '2025-05-19', '2025-05-17 19:52:10', 1),
(36, 'Sport', 'Lamborghini', 'Aventador', 'Coupé', 'Thermique', 5500.00, '[\"assets\\/images\\/cars\\/voiture_36\\/voiture_36_1.jpg\",\"assets\\/images\\/cars\\/voiture_36\\/voiture_36_2.jpg\",\"assets\\/images\\/cars\\/voiture_36\\/voiture_36_3.jpg\"]', 'assets/images/cars/voiture_36/voiture_36_1.jpg', '2025-10-20', '2025-05-17 19:52:10', 1),
(37, 'Sport', 'Porsche', '911 Turbo S', 'Coupé', 'Thermique', 3000.00, '[\"assets\\/images\\/cars\\/voiture_37\\/voiture_37_1.jpg\",\"assets\\/images\\/cars\\/voiture_37\\/voiture_37_2.jpg\",\"assets\\/images\\/cars\\/voiture_37\\/voiture_37_3.jpg\"]', 'assets/images/cars/voiture_37/voiture_37_1.jpg', '2025-12-20', '2025-05-17 19:52:10', 1),
(38, 'Luxe', 'Maserati', 'Quattroporte', 'Berline', 'Thermique', 2500.00, '[\"assets\\/images\\/cars\\/voiture_38\\/voiture_38_1.jpg\",\"assets\\/images\\/cars\\/voiture_38\\/voiture_38_2.jpg\",\"assets\\/images\\/cars\\/voiture_38\\/voiture_38_3.jpg\"]', 'assets/images/cars/voiture_38/voiture_38_1.jpg', '2025-12-20', '2025-05-17 19:52:10', 1),
(39, 'Performance', 'BMW', 'M8 Competition', 'Coupé', 'Thermique', 2800.00, '[\"assets\\/images\\/cars\\/voiture_39\\/voiture_39_1.jpg\",\"assets\\/images\\/cars\\/voiture_39\\/voiture_39_2.jpg\",\"assets\\/images\\/cars\\/voiture_39\\/voiture_39_3.jpg\"]', 'assets/images/cars/voiture_39/voiture_39_1.jpg', '2026-02-26', '2025-05-17 19:52:10', 1),
(40, 'Performance', 'Audi', 'R8 V10 Performance', 'Coupé', 'Thermique', 3200.00, '[\"assets\\/images\\/cars\\/voiture_40\\/voiture_40_1.jpg\",\"assets\\/images\\/cars\\/voiture_40\\/voiture_40_2.jpg\",\"assets\\/images\\/cars\\/voiture_40\\/voiture_40_3.jpg\"]', 'assets/images/cars/voiture_40/voiture_40_1.jpg', '2025-12-02', '2025-05-17 19:52:10', 1),
(41, 'Exclusivité', 'Rolls-Royce', 'Cullinan', 'SUV', 'Thermique', 5200.00, '[\"assets\\/images\\/cars\\/voiture_41\\/voiture_41_1.jpg\",\"assets\\/images\\/cars\\/voiture_41\\/voiture_41_2.jpg\",\"assets\\/images\\/cars\\/voiture_41\\/voiture_41_3.jpg\"]', 'assets/images/cars/voiture_41/voiture_41_1.jpg', '2025-05-19', '2025-05-17 19:52:10', 1),
(42, 'Prestige', 'Bentley', 'Bentayga', 'SUV', 'Thermique', 3600.00, '[\"assets\\/images\\/cars\\/voiture_42\\/voiture_42_1.jpg\",\"assets\\/images\\/cars\\/voiture_42\\/voiture_42_2.jpg\",\"assets\\/images\\/cars\\/voiture_42\\/voiture_42_3.jpg\"]', 'assets/images/cars/voiture_42/voiture_42_1.jpg', '2025-05-19', '2025-05-17 19:52:10', 1),
(43, 'Performance', 'Mercedes-AMG', 'GT 63 S 4-Door Coupé', 'Coupé', 'Thermique', 3300.00, '[\"assets\\/images\\/cars\\/voiture_43\\/voiture_43_1.jpg\",\"assets\\/images\\/cars\\/voiture_43\\/voiture_43_2.jpg\",\"assets\\/images\\/cars\\/voiture_43\\/voiture_43_3.jpg\"]', 'assets/images/cars/voiture_43/voiture_43_1.jpg', '2025-12-16', '2025-05-17 19:52:10', 1),
(44, 'Prestige', 'Aston Martin', 'Rapide S', 'Berline', 'Thermique', 3700.00, '[\"assets\\/images\\/cars\\/voiture_44\\/voiture_44_1.jpg\",\"assets\\/images\\/cars\\/voiture_44\\/voiture_44_2.jpg\",\"assets\\/images\\/cars\\/voiture_44\\/voiture_44_3.jpg\"]', 'assets/images/cars/voiture_44/voiture_44_1.jpg', '2025-05-19', '2025-05-17 19:52:10', 1),
(45, 'Sport', 'Ferrari', 'Portofino', 'Cabriolet', 'Thermique', 4500.00, '[\"assets\\/images\\/cars\\/voiture_45\\/voiture_45_1.jpg\",\"assets\\/images\\/cars\\/voiture_45\\/voiture_45_2.jpg\",\"assets\\/images\\/cars\\/voiture_45\\/voiture_45_3.jpg\"]', 'assets/images/cars/voiture_45/voiture_45_1.jpg', '2025-05-19', '2025-05-17 19:52:10', 1),
(46, 'Sport', 'Lamborghini', 'Huracán EVO', 'Coupé', 'Thermique', 4200.00, '[\"assets\\/images\\/cars\\/voiture_46\\/voiture_46_1.jpg\",\"assets\\/images\\/cars\\/voiture_46\\/voiture_46_2.jpg\",\"assets\\/images\\/cars\\/voiture_46\\/voiture_46_3.jpg\"]', 'assets/images/cars/voiture_46/voiture_46_1.jpg', '2025-05-19', '2025-05-17 19:52:10', 1),
(47, 'Innovation', 'Porsche', 'Panamera Turbo S E-Hybrid', 'Berline', 'Hybride', 3100.00, '[\"assets\\/images\\/cars\\/voiture_47\\/voiture_47_1.jpg\",\"assets\\/images\\/cars\\/voiture_47\\/voiture_47_2.jpg\",\"assets\\/images\\/cars\\/voiture_47\\/voiture_47_3.jpg\"]', 'assets/images/cars/voiture_47/voiture_47_1.jpg', '2025-05-19', '2025-05-17 19:52:10', 1),
(48, 'Luxe', 'Maserati', 'Levante Trofeo', 'SUV', 'Thermique', 2900.00, '[\"assets\\/images\\/cars\\/voiture_48\\/voiture_48_1.jpg\",\"assets\\/images\\/cars\\/voiture_48\\/voiture_48_2.jpg\",\"assets\\/images\\/cars\\/voiture_48\\/voiture_48_3.jpg\"]', 'assets/images/cars/voiture_48/voiture_48_1.jpg', '2025-05-19', '2025-05-17 19:52:10', 1),
(49, 'Innovation', 'BMW', 'i8 Roadster', 'Cabriolet', 'Hybride', 2700.00, '[\"assets\\/images\\/cars\\/voiture_49\\/voiture_49_1.jpg\",\"assets\\/images\\/cars\\/voiture_49\\/voiture_49_2.jpg\",\"assets\\/images\\/cars\\/voiture_49\\/voiture_49_3.jpg\"]', 'assets/images/cars/voiture_49/voiture_49_1.jpg', '2025-05-19', '2025-05-17 19:52:10', 1),
(50, 'Sport', 'Audi', 'RS7 Sportback', 'Berline', 'Thermique', 2800.00, '[\"assets\\/images\\/cars\\/voiture_50\\/voiture_50_1.jpg\",\"assets\\/images\\/cars\\/voiture_50\\/voiture_50_2.jpg\",\"assets\\/images\\/cars\\/voiture_50\\/voiture_50_3.jpg\"]', 'assets/images/cars/voiture_50/voiture_50_1.jpg', '2031-11-20', '2025-05-17 19:52:10', 1),
(51, 'Sport', 'Jaguar', 'F-Type SVR', 'Coupé', 'Thermique', 2600.00, '[\"assets\\/images\\/cars\\/voiture_51\\/voiture_51_1.jpg\",\"assets\\/images\\/cars\\/voiture_51\\/voiture_51_2.jpg\",\"assets\\/images\\/cars\\/voiture_51\\/voiture_51_3.jpg\"]', 'assets/images/cars/voiture_51/voiture_51_1.jpg', '2025-05-19', '2025-05-17 19:52:10', 1),
(52, 'Confort', 'Lexus', 'LC 500', 'Coupé', 'Thermique', 2400.00, '[\"assets\\/images\\/cars\\/voiture_52\\/voiture_52_1.jpg\",\"assets\\/images\\/cars\\/voiture_52\\/voiture_52_2.jpg\",\"assets\\/images\\/cars\\/voiture_52\\/voiture_52_3.jpg\"]', 'assets/images/cars/voiture_52/voiture_52_1.jpg', '2025-11-17', '2025-05-17 19:52:10', 1),
(53, 'Aventure', 'Land Rover', 'Range Rover SVAutobiography', 'SUV', 'Electrique', 3400.00, '[\"assets\\/images\\/cars\\/voiture_53\\/voiture_53_1.jpg\",\"assets\\/images\\/cars\\/voiture_53\\/voiture_53_2.jpg\",\"assets\\/images\\/cars\\/voiture_53\\/voiture_53_3.jpg\"]', 'assets/images/cars/voiture_53/voiture_53_1.jpg', '2025-05-19', '2025-05-17 19:52:10', 1),
(54, 'Racing', 'McLaren', '570S', 'Coupé', 'Thermique', 4000.00, '[\"assets\\/images\\/cars\\/voiture_54\\/voiture_54_1.jpg\",\"assets\\/images\\/cars\\/voiture_54\\/voiture_54_2.jpg\",\"assets\\/images\\/cars\\/voiture_54\\/voiture_54_3.jpg\"]', 'assets/images/cars/voiture_54/voiture_54_1.jpg', '2025-05-19', '2025-05-17 19:52:10', 1),
(55, 'Prestige', 'Bugatti', 'Chiron', 'Coupé', 'Thermique', 12000.00, '[\"assets\\/images\\/cars\\/voiture_55\\/voiture_55_1.jpg\",\"assets\\/images\\/cars\\/voiture_55\\/voiture_55_2.jpg\",\"assets\\/images\\/cars\\/voiture_55\\/voiture_55_3.jpg\"]', 'assets/images/cars/voiture_55/voiture_55_1.jpg', '2025-05-19', '2025-05-17 19:52:10', 1),
(56, 'Prestige', 'Pagani', 'Huayra', 'Coupé', 'Thermique', 10000.00, '[\"assets\\/images\\/cars\\/voiture_56\\/voiture_56_1.jpg\",\"assets\\/images\\/cars\\/voiture_56\\/voiture_56_2.jpg\",\"assets\\/images\\/cars\\/voiture_56\\/voiture_56_3.jpg\"]', 'assets/images/cars/voiture_56/voiture_56_1.jpg', '2027-04-19', '2025-05-17 19:52:10', 1);

-- --------------------------------------------------------

--
-- Structure de la table `carts`
--

CREATE TABLE `carts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `favorites`
--

INSERT INTO `favorites` (`id`, `user_id`, `car_id`, `created_at`) VALUES
(203, 22, 38, '2025-05-20 06:14:03'),
(168, 9, 30, '2025-05-19 19:41:22'),
(198, 22, 30, '2025-05-20 01:05:11'),
(167, 9, 52, '2025-05-19 19:41:20'),
(199, 22, 52, '2025-05-20 01:05:12'),
(146, 4, 51, '2025-05-19 06:59:30'),
(151, 4, 52, '2025-05-19 12:06:51'),
(158, 4, 38, '2025-05-19 14:27:58'),
(166, 9, 38, '2025-05-19 19:41:19'),
(147, 4, 49, '2025-05-19 06:59:31'),
(183, 22, 31, '2025-05-19 22:39:22'),
(182, 22, 45, '2025-05-19 22:39:21'),
(181, 22, 29, '2025-05-19 22:39:20'),
(205, 22, 51, '2025-05-20 06:28:28'),
(207, 24, 52, '2025-05-20 07:17:24'),
(208, 24, 40, '2025-05-20 07:17:25'),
(209, 24, 38, '2025-05-20 07:27:54');

-- --------------------------------------------------------

--
-- Structure de la table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `car_marque` varchar(100) DEFAULT NULL,
  `car_modele` varchar(100) DEFAULT NULL,
  `car_type` varchar(50) DEFAULT NULL,
  `car_motorisation` varchar(50) DEFAULT NULL,
  `car_prix` decimal(10,2) DEFAULT NULL,
  `car_image` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `payment_amount` decimal(10,2) NOT NULL,
  `payment_id` varchar(100) DEFAULT NULL,
  `payment_status` enum('pending','completed','failed','refunded') NOT NULL DEFAULT 'completed',
  `payment_date` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
  `pickup_code` varchar(8) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `reservations`
--

INSERT INTO `reservations` (`id`, `user_id`, `car_id`, `car_marque`, `car_modele`, `car_type`, `car_motorisation`, `car_prix`, `car_image`, `start_date`, `end_date`, `total_price`, `payment_amount`, `payment_id`, `payment_status`, `payment_date`, `status`, `pickup_code`, `created_at`) VALUES
(29, 22, 52, 'Lexus', 'LC 500', 'Coupé', 'Thermique', 2400.00, 'assets/images/cars/voiture_52/voiture_52_1.jpg', '2025-05-20', '2025-11-17', 12000.00, 12000.00, 'PP-682bb1d8197f8', 'completed', '2025-05-20 00:34:00', 'paid', 'JEXYVZWS', '2025-05-19 22:34:00'),
(32, 21, 39, 'BMW', 'M8 Competition', 'Coupé', 'Thermique', 2800.00, 'assets/images/cars/voiture_39/voiture_39_1.jpg', '2025-05-20', '2026-02-26', 25200.00, 25200.00, 'PP-682bb2f0de896', 'completed', '2025-05-20 00:38:40', 'paid', 'PJ3F1574', '2025-05-19 22:38:40'),
(33, 21, 30, 'Porsche', '911 Carrera', 'Coupé', 'Thermique', 2100.00, 'assets/images/cars/voiture_30/voiture_30_1.jpg', '2025-05-20', '2025-12-17', 12600.00, 12600.00, 'PP-682bb31f3d9df', 'completed', '2025-05-20 00:39:27', 'paid', 'NYVJ19PH', '2025-05-19 22:39:27'),
(34, 21, 40, 'Audi', 'R8 V10 Performance', 'Coupé', 'Thermique', 3200.00, 'assets/images/cars/voiture_40/voiture_40_1.jpg', '2025-05-20', '2025-12-02', 19200.00, 19200.00, 'PP-682bb3555e529', 'completed', '2025-05-20 00:40:21', 'paid', 'AS3UIFJW', '2025-05-19 22:40:21'),
(35, 22, 43, 'Mercedes-AMG', 'GT 63 S 4-Door Coupé', 'Coupé', 'Thermique', 3300.00, 'assets/images/cars/voiture_43/voiture_43_1.jpg', '2025-05-20', '2025-12-16', 19800.00, 19800.00, 'PP-682bb3a7a099e', 'completed', '2025-05-20 00:41:43', 'paid', 'J03MFEK1', '2025-05-19 22:41:43'),
(36, 22, 31, 'Rolls-Royce', 'Phantom', 'Berline', 'Thermique', 5000.00, 'assets/images/cars/voiture_31/voiture_31_1.jpg', '2025-05-20', '2025-12-09', 30000.00, 30000.00, 'PP-682bb3d7d76fa', 'completed', '2025-05-20 00:42:31', 'paid', 'BIK3NGO7', '2025-05-19 22:42:31'),
(38, 20, 37, 'Porsche', '911 Turbo S', 'Coupé', 'Thermique', 3000.00, 'assets/images/cars/voiture_37/voiture_37_1.jpg', '2025-05-20', '2025-12-20', 21000.00, 21000.00, 'PP-682bb514c08ee', 'completed', '2025-05-20 00:47:48', 'paid', '1HMRSZI5', '2025-05-19 22:47:48'),
(39, 20, 36, 'Lamborghini', 'Aventador', 'Coupé', 'Thermique', 5500.00, 'assets/images/cars/voiture_36/voiture_36_1.jpg', '2025-05-20', '2025-10-20', 27500.00, 27500.00, 'PP-682bb5fbd1ee8', 'completed', '2025-05-20 00:51:39', 'paid', 'T6HFBJCA', '2025-05-19 22:51:39'),
(40, 22, 38, 'Maserati', 'Quattroporte', 'Berline', 'Thermique', 2500.00, 'assets/images/cars/voiture_38/voiture_38_1.jpg', '2025-05-20', '2025-12-20', 17500.00, 17500.00, 'PP-682c218d0b479', 'completed', '2025-05-20 08:30:37', 'paid', 'JKET7W3P', '2025-05-20 06:30:37'),
(41, 24, 50, 'Audi', 'RS7 Sportback', 'Berline', 'Thermique', 2800.00, 'assets/images/cars/voiture_50/voiture_50_1.jpg', '2025-05-20', '2031-11-20', 218400.00, 218400.00, 'PP-682c2411048e0', 'completed', '2025-05-20 08:41:21', 'paid', '6LN9GPZB', '2025-05-20 06:41:21');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('user','commercial','admin') NOT NULL DEFAULT 'user',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `role_id` int(11) DEFAULT 2
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `role_id`) VALUES
(15, 'Stéphanie', 'stephanie@com-novaloc.com', '$2y$10$e1IxNLXaSFRmSgjPHMNdUek.cANR7pJlOjA.eviRcxa6vy9LBMpou', 'commercial', '2025-05-19 18:49:00', 1),
(16, 'Catherine', 'catherine@com-novaloc.com', '$2y$10$b3Ut/ZM02U1Nx.czVSK5LuWGbcqzY5lRQN4RFALdudXdi/5Kmdpt6', 'commercial', '2025-05-19 18:49:51', 1),
(17, 'Pierre', 'pierre@admin-novaloc.com', '$2y$10$QA9zYtvx1B26t0JTmOKeRuF7svBmgYkmphIi/PaHU1QuDlKRpvwYK', 'admin', '2025-05-19 18:51:32', 0),
(18, 'Thomas', 'thomas@admin-novaloc.com', '$2y$10$LxAQ65r4hTbllf1AiLKPceaVb3zlzS2t/8QPLpBBdCGDD5ebJE5nO', 'admin', '2025-05-19 22:13:07', 0),
(20, 'Alexandre', 'alexandre@gmail.com', '$2y$10$2joqzXOKZB9vQ4LfnObpuOPbxbO7VP.WABju2cVuA4jBruC91eED2', 'user', '2025-05-19 23:22:06', 2),
(21, 'Nicolas', 'nicolas@gmail.com', '$2y$10$47bUL3ohyqQ0cGjLoeiaS.2ybwC8s4uFYeuD/b7WFH002OBf9vi/e', 'user', '2025-05-19 23:22:43', 2),
(22, 'hugo', 'hugo@gmail.com', '$2y$10$Jeb27P87Gjedvn.84H8lNO/oyy4VrQ1OnP1KkqfIoiB2aFxXF2h3O', 'user', '2025-05-19 23:23:29', 2),
(23, 'Test', 'test@gmail.com', '$2y$10$XEw86yY9up3OBKAoRwgUxeafl5WQoa3k4XS0vmGxOJZu9Y1aXUtdq', 'user', '2025-05-20 01:55:26', 2),
(24, 'Mathis', 'mathis@gmail.com', '$2y$10$0NrHfI/FVRgv/nVAiswl2.0cdf8Oa3Y5SJUW3rT1lpfUs72n1tzNi', 'user', '2025-05-20 08:40:36', 2);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `car_id` (`car_id`);

--
-- Index pour la table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favorite` (`user_id`,`car_id`),
  ADD KEY `car_id` (`car_id`);

--
-- Index pour la table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_pickup_code` (`pickup_code`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `car_id` (`car_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT pour la table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=210;

--
-- AUTO_INCREMENT pour la table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_2` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
