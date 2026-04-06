-- Добавить составной PRIMARY KEY (Tirage, n1, n2, n3, n4, n5).
-- Несколько строк на одну дату (main + Lots bonis) — допустимы.

-- Если раньше был PRIMARY KEY (например по Tirage), сначала: ALTER TABLE Vie DROP PRIMARY KEY;
ALTER TABLE Vie ADD PRIMARY KEY (Tirage, n1, n2, n3, n4, n5);
