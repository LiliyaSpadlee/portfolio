-- ============================================================
--  ПАТЧ: Гибкие процедуры и исправленные представления
--  База данных: usm
--  Применить в MySQL Workbench: открыть файл → Run (молния)
-- ============================================================

USE usm;

-- ------------------------------------------------------------
-- ПРЕДСТАВЛЕНИЯ: исправляем жёсткие года на YEAR(CURDATE())
-- ------------------------------------------------------------

-- 1. Аналитика персонала — возраст и стаж теперь считаются автоматически
DROP VIEW IF EXISTS `v_аналитика_персонала`;
CREATE VIEW `v_аналитика_персонала` AS
SELECT
    e.last_name                              AS `Фамилия`,
    e.position                               AS `Должность`,
    l.name                                   AS `Библиотека`,
    l.city                                   AS `Город`,
    (YEAR(CURDATE()) - e.birth_year)         AS `Возраст`,
    (YEAR(CURDATE()) - e.employment_year)    AS `Стаж работы`,
    e.education                              AS `Образование`,
    e.salary                                 AS `Зарплата`
FROM сотрудники e
JOIN библиотеки l ON e.library_id = l.library_id;

-- 2. Актуальность изданий — статус теперь динамический (не привязан к 2023)
DROP VIEW IF EXISTS `v_актуальность_изданий`;
CREATE VIEW `v_актуальность_изданий` AS
SELECT
    p.title                                  AS `Название`,
    p.publication_date                       AS `Дата выхода`,
    CASE
        WHEN YEAR(p.publication_date) >= YEAR(CURDATE()) - 2 THEN 'Новинка (последние 3 года)'
        WHEN YEAR(p.publication_date) >= YEAR(CURDATE()) - 10 THEN 'Актуальное'
        ELSE 'Архивный фонд'
    END                                      AS `Статус актуальности`,
    pub.name                                 AS `Издатель`,
    lt.type_name                             AS `Тип литературы`
FROM публикации p
JOIN издатели pub ON p.publisher_id = pub.publisher_id
JOIN типы_литературы lt ON p.literature_type_id = lt.type_id;

-- 3. Каталог изданий — добавляем дату и статус
DROP VIEW IF EXISTS `v_каталогизданий`;
CREATE VIEW `v_каталогизданий` AS
SELECT
    p.publication_id                         AS `ID`,
    p.title                                  AS `Название книги`,
    pub.name                                 AS `Издательство`,
    lt.type_name                             AS `Категория`,
    p.publication_date                       AS `Дата издания`
FROM публикации p
JOIN издатели pub ON p.publisher_id = pub.publisher_id
JOIN типы_литературы lt ON p.literature_type_id = lt.type_id;

-- 4. Журнал пополнений — добавляем библиотеку для полноты
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

-- ------------------------------------------------------------
-- ПРОЦЕДУРЫ: делаем гибкими с параметрами поиска/фильтрации
-- ------------------------------------------------------------

-- 1. Поиск книги по части названия (уже был LIKE, добавляем тип и издателя)
DROP PROCEDURE IF EXISTS `пр_ПоискКниги`;
DELIMITER ;;
CREATE PROCEDURE `пр_ПоискКниги`(
    IN п_фраза       VARCHAR(100),   -- часть названия, '' = все
    IN п_тип_id      INT,            -- тип литературы, 0 = все
    IN п_издатель_id INT             -- издатель, 0 = все
)
BEGIN
    SELECT
        p.title             AS `Название`,
        lt.type_name        AS `Тип`,
        pub.name            AS `Издатель`,
        p.publication_date  AS `Дата издания`,
        CASE
            WHEN YEAR(p.publication_date) >= YEAR(CURDATE()) - 2 THEN 'Новинка'
            WHEN YEAR(p.publication_date) >= YEAR(CURDATE()) - 10 THEN 'Актуальное'
            ELSE 'Архив'
        END                 AS `Статус`
    FROM публикации p
    JOIN типы_литературы lt ON p.literature_type_id = lt.type_id
    JOIN издатели pub       ON p.publisher_id = pub.publisher_id
    WHERE
        (п_фраза = ''       OR p.title LIKE CONCAT('%', п_фраза, '%'))
        AND (п_тип_id = 0   OR p.literature_type_id = п_тип_id)
        AND (п_издатель_id = 0 OR p.publisher_id = п_издатель_id)
    ORDER BY p.publication_date DESC;
END ;;
DELIMITER ;

-- 2. Книги по типу — теперь возвращает больше полей
DROP PROCEDURE IF EXISTS `пр_КнигиПоТипу`;
DELIMITER ;;
CREATE PROCEDURE `пр_КнигиПоТипу`(IN п_id_типа INT)
BEGIN
    SELECT
        p.title             AS `Название`,
        pub.name            AS `Издатель`,
        p.publication_date  AS `Дата издания`,
        CASE
            WHEN YEAR(p.publication_date) >= YEAR(CURDATE()) - 2 THEN 'Новинка'
            WHEN YEAR(p.publication_date) >= YEAR(CURDATE()) - 10 THEN 'Актуальное'
            ELSE 'Архив'
        END                 AS `Статус`
    FROM публикации p
    JOIN издатели pub ON p.publisher_id = pub.publisher_id
    WHERE p.literature_type_id = п_id_типа
    ORDER BY p.publication_date DESC;
END ;;
DELIMITER ;

-- 3. Отчёт пополнений — ДИАПАЗОН дат вместо точной даты
DROP PROCEDURE IF EXISTS `пр_ОтчетПополнений`;
DELIMITER ;;
CREATE PROCEDURE `пр_ОтчетПополнений`(
    IN п_дата_с  DATE,   -- начало периода
    IN п_дата_по DATE    -- конец периода
)
BEGIN
    SELECT
        fr.replenishment_date   AS `Дата`,
        f.fund_name             AS `Фонд`,
        l.city                  AS `Город`,
        p.title                 AS `Книга`,
        e.last_name             AS `Сотрудник`,
        fr.quantity_added       AS `Кол-во`
    FROM пополнение_фондов fr
    JOIN фонды f      ON fr.fund_id        = f.fund_id
    JOIN библиотеки l ON f.library_id      = l.library_id
    JOIN публикации p ON fr.publication_id = p.publication_id
    JOIN сотрудники e ON fr.employee_id    = e.employee_id
    WHERE fr.replenishment_date BETWEEN п_дата_с AND п_дата_по
    ORDER BY fr.replenishment_date DESC;
END ;;
DELIMITER ;

-- 4. Сотрудники библиотеки — добавляем фильтр по должности
DROP PROCEDURE IF EXISTS `пр_СотрудникиБиблиотеки`;
DELIMITER ;;
CREATE PROCEDURE `пр_СотрудникиБиблиотеки`(
    IN п_id_биб    INT,           -- ID библиотеки, 0 = все
    IN п_должность VARCHAR(100)   -- часть должности, '' = все
)
BEGIN
    SELECT
        e.last_name                             AS `Фамилия`,
        e.position                              AS `Должность`,
        l.name                                  AS `Библиотека`,
        l.city                                  AS `Город`,
        e.salary                                AS `Зарплата`,
        e.education                             AS `Образование`,
        (YEAR(CURDATE()) - e.employment_year)   AS `Стаж`
    FROM сотрудники e
    JOIN библиотеки l ON e.library_id = l.library_id
    WHERE
        (п_id_биб = 0     OR e.library_id = п_id_биб)
        AND (п_должность = '' OR e.position LIKE CONCAT('%', п_должность, '%'))
    ORDER BY e.salary DESC;
END ;;
DELIMITER ;

-- 5. Добавить сотрудника — без изменений, уже хорошая
-- (оставляем как есть)

-- ============================================================
-- ГОТОВО. Теперь все процедуры принимают гибкие параметры.
-- ============================================================
