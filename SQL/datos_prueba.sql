-- 1. Fichajes normales (lunes a sábado, incluyendo festivos)
INSERT INTO Fichaje (id_empleado, fecha, entrada_manana, salida_manana, entrada_tarde, salida_tarde)
SELECT 
    1 AS id_empleado,
    DATE_ADD('2024-01-01', INTERVAL n DAY) AS fecha,
    '09:50:00' AS entrada_manana,
    '14:05:00' AS salida_manana,
    '16:20:00' AS entrada_tarde,
    '20:05:00' AS salida_tarde
FROM (
    SELECT a.n + b.n * 10 + c.n * 100 AS n
    FROM (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) a,
         (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) b,
         (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) c
) t
WHERE 
    DATE_ADD('2024-01-01', INTERVAL n DAY) <= '2024-12-31'
    AND DAYOFWEEK(DATE_ADD('2024-01-01', INTERVAL n DAY)) NOT IN (1); -- Excluir domingos únicamente

-- Garantizar que el primer día de cada mes siempre se incluya
INSERT INTO Fichaje (id_empleado, fecha, entrada_manana, salida_manana, entrada_tarde, salida_tarde)
SELECT 
    1 AS id_empleado,
    primer_dia AS fecha,
    '09:50:00' AS entrada_manana,
    '14:05:00' AS salida_manana,
    '16:20:00' AS entrada_tarde,
    '20:05:00' AS salida_tarde
FROM (
    SELECT DISTINCT CONCAT('2024-', LPAD(mes, 2, '0'), '-01') AS primer_dia
    FROM (
        SELECT 1 AS mes UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 
        UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12
    ) meses
) dias
WHERE NOT EXISTS (
    SELECT 1
    FROM Eventos_especiales
    WHERE dias.primer_dia BETWEEN fecha_inicio AND fecha_fin
);

-- 2. Vacaciones (4 semanas fijas de lunes a domingo)
INSERT INTO Eventos_especiales (id_empleado, tipo_evento, fecha_inicio, fecha_fin, descripcion)
VALUES
(1, 'Vacaciones', '2024-02-05', '2024-02-11', 'Semana de vacaciones'),
(1, 'Vacaciones', '2024-05-13', '2024-05-19', 'Semana de vacaciones'),
(1, 'Vacaciones', '2024-08-05', '2024-08-11', 'Semana de vacaciones'),
(1, 'Vacaciones', '2024-11-18', '2024-11-24', 'Semana de vacaciones');

-- 3. Días de vacaciones aleatorios (un solo día por evento)
INSERT INTO Eventos_especiales (id_empleado, tipo_evento, fecha_inicio, fecha_fin, descripcion)
SELECT 
    1 AS id_empleado,
    'Vacaciones' AS tipo_evento,
    fecha_inicio,
    fecha_inicio AS fecha_fin,
    'Día de vacaciones adicional' AS descripcion
FROM (
    SELECT DISTINCT DATE_ADD('2024-01-01', INTERVAL FLOOR(RAND() * 365) DAY) AS fecha_inicio
    FROM (SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3) t
) subquery
WHERE 
    fecha_inicio <= '2024-12-31' 
    AND DAYOFWEEK(fecha_inicio) NOT IN (1); -- Excluir domingos

-- 4. Libranzas (un día libre entre lunes y sábado por semana, fuera de semanas de vacaciones)
INSERT INTO Eventos_especiales (id_empleado, tipo_evento, fecha_inicio, fecha_fin, descripcion)
SELECT 
    1 AS id_empleado,
    'Libranza' AS tipo_evento,
    libranza_dia AS fecha_inicio,
    libranza_dia AS fecha_fin,
    'Día libre semanal' AS descripcion
FROM (
    SELECT DISTINCT DATE_ADD('2024-01-01', INTERVAL n * 7 + FLOOR(RAND() * 6) DAY) AS libranza_dia
    FROM (
        SELECT a.n + b.n * 10 AS n
        FROM (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) a,
             (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) b
    ) t
    WHERE DATE_ADD('2024-01-01', INTERVAL n * 7 DAY) <= '2024-12-31'
) subquery
WHERE NOT EXISTS (
    SELECT 1 
    FROM Eventos_especiales 
    WHERE tipo_evento = 'Vacaciones' 
      AND libranza_dia BETWEEN fecha_inicio AND fecha_fin
);

-- 5. Días de baja aleatorios
INSERT INTO Eventos_especiales (id_empleado, tipo_evento, fecha_inicio, fecha_fin, descripcion)
SELECT 
    1 AS id_empleado,
    'Baja' AS tipo_evento,
    fecha_inicio,
    fecha_inicio AS fecha_fin,
    'Día de baja médica' AS descripcion
FROM (
    SELECT DISTINCT DATE_ADD('2024-01-01', INTERVAL FLOOR(RAND() * 365) DAY) AS fecha_inicio
    FROM (SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) t
) subquery
WHERE fecha_inicio <= '2024-12-31';
