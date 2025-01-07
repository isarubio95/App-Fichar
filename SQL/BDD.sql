-- Crear la base de datos
CREATE DATABASE fichajes;
USE fichajes;

-- Crear tabla para los empleados
CREATE TABLE Empleado (
    id_empleado INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    horas_jornada FLOAT(8, 2) NOT NULL
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

-- Crear tabla para los eventos especiales
CREATE TABLE Eventos_especiales (
    id_evento INT AUTO_INCREMENT PRIMARY KEY,
    id_empleado INT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    tipo_evento ENUM('Vacaciones', 'Libranza', 'Baja', 'Permiso') NOT NULL,
    descripcion TEXT, 
    FOREIGN KEY (id_empleado) REFERENCES Empleado(id_empleado)
);

-- Crear tabla para los festivos
CREATE TABLE Festivo (
    id_festivo INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    ano INT NOT NULL
);

-- Insertar datos de ejemplo en la tabla Empleado
INSERT INTO Empleado (nombre, apellidos, contrasena) VALUES
('Carmen', 'García Pérez', '0000'),
('David', 'Bartolomé', '0000'),
('Mohammed', 'Idir El Fizazi', '0000'),
('Miguel Andrés', 'Gómez', '0000'),
('Isaías', 'Rubio Hernández', '0000'),
('Álvaro Eladio', 'López', '0000'),
('Arkaitz', 'Pérez Moreno', '0000'),
('Cristian', 'Pereira', '0000'),
('David', 'Jiménez', '0000'),
('Raquel', 'Pérez', '0000');