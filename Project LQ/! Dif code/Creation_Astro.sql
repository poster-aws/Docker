
-- Creation de DB
CREATE DATABASE IF NOT EXISTS astro
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

-- Grant Privileges
GRANT ALL PRIVILEGES ON astro.* TO 'user'@'%';
FLUSH PRIVILEGES;


-- Creation de Tabl
USE astro;

-- Drop Tabl if exist
DROP TABLE IF EXISTS Astro;

-- Создаём таблицу для комбинаций из 3 чисел (1–70)
CREATE TABLE Astro (
  Tirage DATE NOT NULL,
  jour TINYINT UNSIGNED NOT NULL,
  mois VARCHAR(10) NOT NULL,
  annee TINYINT UNSIGNED NOT NULL,
  signe VARCHAR(11) NOT NULL,
  PRIMARY KEY (Tirage)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
