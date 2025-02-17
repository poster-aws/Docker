CREATE TABLE IF NOT EXISTS `data_table` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `date` DATE NOT NULL,
    `value` INT NOT NULL
);

INSERT INTO `data_table` (`date`, `value`) VALUES 
('2025-02-01', 150),
('2025-02-02', 200),
('2025-02-03', 250);
