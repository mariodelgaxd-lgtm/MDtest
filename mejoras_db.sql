-- Mejoras a la base de datos para MDTest2

-- Tabla de Niveles de Usuario
CREATE TABLE IF NOT EXISTS niveles (
    id_nivel INT NOT NULL AUTO_INCREMENT,
    nombre_nivel VARCHAR(50) NOT NULL,
    descripcion VARCHAR(255),
    tests_minimos INT NOT NULL DEFAULT 0,
    icono VARCHAR(100),
    PRIMARY KEY (id_nivel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

INSERT IGNORE INTO niveles (id_nivel, nombre_nivel, descripcion, tests_minimos, icono) VALUES
(1, 'Novato', 'Primeros pasos en la autoescuela', 0, 'novato.png'),
(2, 'Aprendiz', 'Comenzando a dominar las normas', 5, 'aprendiz.png'),
(3, 'Conductor', 'Nivel intermedio alcanzado', 15, 'conductor.png'),
(4, 'Experto', 'Gran conocimiento de la vía', 30, 'experto.png'),
(5, 'Maestro', 'Dominio total de la conducción', 50, 'maestro.png'),
(6, 'Leyenda', 'Eres una leyenda del volante', 100, 'leyenda.png');

-- Añadir campos a usuarios si no existen
SET @dbname = 'mdtest2';
SET @tablename = 'usuarios';

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns 
                      WHERE table_schema = @dbname AND table_name = @tablename 
                      AND column_name = 'id_nivel');
                      
SET @sql = IF(@column_exists = 0, 'ALTER TABLE usuarios ADD COLUMN id_nivel INT DEFAULT 1', 'SELECT "Columna id_nivel ya existe"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns 
                      WHERE table_schema = @dbname AND table_name = @tablename 
                      AND column_name = 'racha_perdida');
                      
SET @sql = IF(@column_exists = 0, 'ALTER TABLE usuarios ADD COLUMN racha_perdida INT DEFAULT 0', 'SELECT "Columna racha_perdida ya existe"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns 
                      WHERE table_schema = @dbname AND table_name = @tablename 
                      AND column_name = 'fecha_ultimo_test');
                      
SET @sql = IF(@column_exists = 0, 'ALTER TABLE usuarios ADD COLUMN fecha_ultimo_test DATE DEFAULT NULL', 'SELECT "Columna fecha_ultimo_test ya existe"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns 
                      WHERE table_schema = @dbname AND table_name = @tablename 
                      AND column_name = 'tests_completados');
                      
SET @sql = IF(@column_exists = 0, 'ALTER TABLE usuarios ADD COLUMN tests_completados INT DEFAULT 0', 'SELECT "Columna tests_completados ya existe"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Añadir clave foránea si no existe
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.table_constraints 
                  WHERE table_schema = @dbname AND table_name = @tablename 
                  AND constraint_name = 'fk_usuarios_nivel');
                  
SET @sql = IF(@fk_exists = 0, 'ALTER TABLE usuarios ADD CONSTRAINT fk_usuarios_nivel FOREIGN KEY (id_nivel) REFERENCES niveles(id_nivel)', 'SELECT "Clave foránea ya existe"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Nuevos logros
INSERT INTO logros (id_logro, nombre, descripcion, icono_url) VALUES
(4, 'Maestro de Tema', 'Acierta 10 preguntas seguidas de un mismo tema', 'assets/img/logros/maestro_tema.png'),
(5, 'Comeback', 'Recupera tu racha después de perderla', 'assets/img/logros/comeback.png'),
(6, 'Constancia', 'Haz tests 7 días seguidos', 'assets/img/logros/constancia.png'),
(7, 'Perfecto en Repaso', 'Acierta todas las preguntas de repaso en un test', 'assets/img/logros/perfecto_repaso.png'),
(8, 'Superviviente', 'Sobrevive a un test con exactamente 3 fallos (límite)', 'assets/img/logros/superviviente.png'),
(9, 'Velocista', 'Completa un test en menos de 5 minutos', 'assets/img/logros/velocista.png'),
(10, 'Especialista en Señales', 'Acierta 20 preguntas de señales seguidas', 'assets/img/logros/especialista_senales.png'),
(11, 'Veterano', 'Completa 50 tests', 'assets/img/logros/veterano.png'),
(12, 'Inmortal', 'Llega a una racha de 50 días', 'assets/img/logros/inmortal.png')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), descripcion=VALUES(descripcion);
