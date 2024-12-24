-- Eliminar referencias en la tabla Fichaje
DELETE FROM Fichaje WHERE id_empleado = 1;

-- Eliminar referencias en la tabla Eventos_especiales
DELETE FROM Eventos_especiales WHERE id_empleado = 1;

-- Finalmente, eliminar al empleado en la tabla Empleado
DELETE FROM Empleado WHERE id_empleado = 1;

-- Crear al empleado nuevamente
INSERT INTO Empleado (id_empleado, nombre, apellidos, contrasena, horas_jornada)
VALUES (1, 'Juan', 'Pérez', '1234', 8);