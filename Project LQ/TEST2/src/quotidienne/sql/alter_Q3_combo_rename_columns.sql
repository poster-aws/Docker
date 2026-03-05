-- Редактирование таблиц Q3_combo: переименование jours -> days, max -> max_days
-- Выполнить после создания таблиц (если у них колонки jours и max).

ALTER TABLE Q3_combo_stats_order
  CHANGE COLUMN jours days INT NULL,
  CHANGE COLUMN `max` max_days INT NULL;

ALTER TABLE Q3_combo_stats_norder
  CHANGE COLUMN jours days INT NULL,
  CHANGE COLUMN `max` max_days INT NULL;
