-- ============================================================
--  ПАТЧ: Адреса библиотек
--  Применить в MySQL Workbench: открыть файл → Run (молния)
--  Или: SOURCE /path/to/patch_addresses.sql
-- ============================================================

USE usm;

-- Убеждаемся что колонка address существует
ALTER TABLE библиотеки
    ADD COLUMN IF NOT EXISTS address VARCHAR(255) DEFAULT NULL;

-- Москва
UPDATE библиотеки SET address = 'ул. Пушкина, 10'
    WHERE name LIKE '%Пушкина%' AND city = 'Москва';

UPDATE библиотеки SET address = 'ул. Пушкина, 10'
    WHERE name LIKE '%Фонд периодики%' AND city = 'Москва';

UPDATE библиотеки SET address = 'ул. Новый Арбат, 8'
    WHERE name LIKE '%искусств%' AND city = 'Москва';

UPDATE библиотеки SET address = 'Дмитровское шоссе, 107'
    WHERE name LIKE '%Северный%' AND city = 'Москва';

-- Санкт-Петербург
UPDATE библиотеки SET address = 'Невский пр., 20'
    WHERE name LIKE '%Маяковского%' AND city = 'Санкт-Петербург' AND name NOT LIKE '%(Филиал)%';

UPDATE библиотеки SET address = 'наб. Фонтанки, 44'
    WHERE name LIKE '%Маяковского%' AND city = 'Санкт-Петербург' AND name LIKE '%(Филиал)%';

UPDATE библиотеки SET address = 'Среднеохтинский пр., 8'
    WHERE name LIKE '%Гоголя%' AND city = 'Санкт-Петербург';

-- Новосибирск
UPDATE библиотеки SET address = 'Вокзальная магистраль, 16'
    WHERE name LIKE '%научная%' AND city = 'Новосибирск';

UPDATE библиотеки SET address = 'ул. Ильича, 6, Академгородок'
    WHERE name LIKE '%Академгородок%' AND city = 'Новосибирск';

-- Омск
UPDATE библиотеки SET address = 'ул. Красный Путь, 4'
    WHERE name LIKE '%областная%' AND city = 'Омск';

UPDATE библиотеки SET address = 'ул. Лермонтова, 62'
    WHERE name LIKE '%Книжный остров%' AND city = 'Омск';

UPDATE библиотеки SET address = 'ул. Декабристов, 45'
    WHERE name LIKE '%читальный зал%' AND city = 'Омск';

-- Уфа
UPDATE библиотеки SET address = 'ул. Ленина, 4'
    WHERE name LIKE '%Валиди%' AND city = 'Уфа';

UPDATE библиотеки SET address = 'ул. Комсомольская, 138'
    WHERE name LIKE '%городская%' AND city = 'Уфа';

UPDATE библиотеки SET address = 'пр. Октября, 114'
    WHERE name LIKE '%№17%' AND city = 'Уфа';

-- ============================================================
-- ГОТОВО. Адреса обновлены.
-- ============================================================
