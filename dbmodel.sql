

-- Table for Countries
CREATE TABLE Paises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE
);

-- Insert some example countries.
INSERT INTO Paises (nombre) VALUES
('Panama'),
('Costa Rica'),
('Colombia'),
('Argentina'),
('Mexico'),
('España');

-- Table for Technological Interests
CREATE TABLE TemasInteres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tema VARCHAR(100) NOT NULL UNIQUE
);

-- Insert some example technological interests
INSERT INTO TemasInteres (tema) VALUES
('Inteligencia Artificial'),
('Desarrollo Web'),
('Ciberseguridad'),
('Blockchain'),
('Ciencia de Datos'),
('IoT (Internet de las Cosas)');

-- Table for Registrants (Inscriptores)
CREATE TABLE Inscriptores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    apellido VARCHAR(50) NOT NULL,
    correo VARCHAR(100) NOT NULL UNIQUE,
    celular VARCHAR(25) NULL,
    edad INT NOT NULL,
    sexo ENUM('Masculino', 'Femenino', 'Otro') NOT NULL,
    pais_residencia_id INT NOT NULL,
    nacionalidad_id INT NOT NULL,
    observaciones TEXT,
    fecha_registro DATE NOT NULL,
    FOREIGN KEY (pais_residencia_id) REFERENCES Paises(id),
    FOREIGN KEY (nacionalidad_id) REFERENCES Paises(id)
);

-- Junction Table for Inscriptores and TemasInteres (Many-to-Many relationship)
CREATE TABLE Inscriptor_Temas (
    inscriptor_id INT NOT NULL,
    tema_id INT NOT NULL,
    PRIMARY KEY (inscriptor_id, tema_id),
    FOREIGN KEY (inscriptor_id) REFERENCES Inscriptores(id) ON DELETE CASCADE,
    FOREIGN KEY (tema_id) REFERENCES TemasInteres(id) ON DELETE CASCADE
);