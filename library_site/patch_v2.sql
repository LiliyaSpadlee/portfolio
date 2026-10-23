-- ============================================================
--  ПАТЧ v2: Поддержка новых интерактивных форм
--  Применить ПОСЛЕ patch_flexible.sql
-- ============================================================

USE usm;

-- Добавляем столбец rep_id (автоинкремент) если его нет
-- (нужен для определения последней записи в add_replenishment.php)
ALTER TABLE пополнение_фондов
    ADD COLUMN IF NOT EXISTS rep_id INT AUTO_INCREMENT PRIMARY KEY FIRST;

-- Убеждаемся что total_newspapers и total_journals есть в фондах
ALTER TABLE фонды
    ADD COLUMN IF NOT EXISTS total_newspapers INT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS total_journals   INT DEFAULT 0;

-- Обновляем представление v_журналпополнений чтобы включить Тип
DROP VIEW IF EXISTS `v_журналпополнений`;
CREATE VIEW `v_журналпополнений` AS
SELECT
    fr.replenishment_date                    AS `Дата пополнения`,
    e.last_name                              AS `Сотрудник`,
    p.title                                  AS `Название книги`,
    lt.type_name                             AS `Тип`,
    fr.quantity_added                        AS `Количество`,
    f.fund_name                              AS `Название фонда`,
    l.city                                   AS `Город`
FROM пополнение_фондов fr
JOIN сотрудники e   ON fr.employee_id    = e.employee_id
JOIN публикации p   ON fr.publication_id = p.publication_id
JOIN типы_литературы lt ON p.literature_type_id = lt.type_id
JOIN фонды f        ON fr.fund_id        = f.fund_id
JOIN библиотеки l   ON f.library_id      = l.library_id;

-- ============================================================
-- ГОТОВО.
-- ============================================================
