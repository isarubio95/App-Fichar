-- Crear la base de datos
CREATE DATABASE fichajes;
USE fichajes;

-- Crear tabla para los empleados
CREATE TABLE Empleado (
    id_empleado INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    contrasena VARCHAR(255) NOT NULL
);

-- Crear tabla para los fichajes
CREATE TABLE Fichaje (
    id_fichaje INT AUTO_INCREMENT PRIMARY KEY,
    id_empleado INT NOT NULL,
    fecha DATE NOT NULL,
    entrada_manana TIME DEFAULT NULL,
    salida_manana TIME DEFAULT NULL,
    entrada_tarde TIME DEFAULT NULL,
    salida_tarde TIME DEFAULT NULL,
    FOREIGN KEY (id_empleado) REFERENCES Empleado(id_empleado)
    ON DELETE CASCADE
);

CREATE TABLE Eventos_especiales (
    id_evento INT AUTO_INCREMENT PRIMARY KEY,
    id_empleado INT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    tipo_evento ENUM('Vacaciones', 'Libranza', 'Baja', 'Permiso') NOT NULL,
    descripcion TEXT, -- Opcional, para detalles del evento
    FOREIGN KEY (id_empleado) REFERENCES Empleado(id_empleado)
);

CREATE TABLE Festivo (
    id_festivo INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    ano INT NOT NULL
);

INSERT INTO Empleado (nombre, apellidos, contrasena) VALUES
('Carlos', 'García Pérez', '1234'),
('María', 'Martínez López', '1234'),
('David', 'Hernández Gómez', '1234'),
('Laura', 'Rodríguez Sánchez', '1234'),
('Sergio', 'Fernández Ruiz', '1234'),
('Ana', 'González Romero', '1234'),
('Javier', 'Díaz Morales', '1234'),
('Elena', 'Torres Castillo', '1234'),
('Luis', 'Ramírez Vega', '1234'),
('Isabel', 'Jiménez Ortiz', '1234');