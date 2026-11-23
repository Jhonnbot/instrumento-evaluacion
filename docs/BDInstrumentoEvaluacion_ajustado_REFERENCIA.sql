
-- =====================================================
-- PROYECTO: INSTRUMENTO DE EVALUACION - COCOSOFT
-- BASE DE DATOS: instrumento_evaluacion
-- MOTOR: MySQL 8.4 LTS
-- TOTAL: 16 TABLAS
-- =====================================================

-- CREACION DE LA BASE DE DATOS

CREATE DATABASE IF NOT EXISTS instrumento_evaluacion
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE instrumento_evaluacion;


-- =====================================================
-- MODULO 1: GESTION DE USUARIOS Y ACCESOS
-- =====================================================

-- TABLA 1: ROLES

CREATE TABLE roles (
    id_rol BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(40) NOT NULL UNIQUE,
    descripcion TEXT NULL
) ENGINE=InnoDB;


-- TABLA 2: USUARIOS

CREATE TABLE usuarios (
    id_usuario BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_rol BIGINT UNSIGNED NOT NULL,
    nombre_completo VARCHAR(150) NOT NULL,
    telefono VARCHAR(20) NULL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    curp CHAR(18) NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    estado ENUM('activo', 'inactivo')
        NOT NULL DEFAULT 'activo',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    remember_token VARCHAR(100) NULL,

    CONSTRAINT fk_usuarios_roles
        FOREIGN KEY (id_rol)
        REFERENCES roles(id_rol)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT chk_usuarios_curp
        CHECK (curp IS NULL OR CHAR_LENGTH(curp) = 18)

) ENGINE=InnoDB;


-- =====================================================
-- MODULO 2: BANCO DE PREGUNTAS
-- =====================================================

-- TABLA 3: CATEGORIAS

CREATE TABLE categorias (
    id_categoria BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL UNIQUE,
    ponderacion DECIMAL(5,4) NOT NULL DEFAULT 0.2000,
    estado BOOLEAN NOT NULL DEFAULT TRUE,

    CONSTRAINT chk_categorias_ponderacion
        CHECK (ponderacion BETWEEN 0 AND 1)

) ENGINE=InnoDB;


-- TABLA 4: TEMAS

CREATE TABLE temas (
    id_tema BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_categoria BIGINT UNSIGNED NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,

    CONSTRAINT fk_temas_categorias
        FOREIGN KEY (id_categoria)
        REFERENCES categorias(id_categoria)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT uk_temas_categoria_nombre
        UNIQUE (id_categoria, nombre)

) ENGINE=InnoDB;


-- TABLA 5: PREGUNTAS

CREATE TABLE preguntas (
    id_pregunta BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_tema BIGINT UNSIGNED NOT NULL,
    enunciado TEXT NOT NULL,

    tipo ENUM(
        'opcion_multiple',
        'seleccion_multiple',
        'verdadero_falso',
        'ordenamiento'
    ) NOT NULL,

    estado BOOLEAN NOT NULL DEFAULT TRUE,

    CONSTRAINT fk_preguntas_temas
        FOREIGN KEY (id_tema)
        REFERENCES temas(id_tema)
        ON UPDATE CASCADE
        ON DELETE RESTRICT

) ENGINE=InnoDB;


-- TABLA 6: OPCIONES_RESPUESTA

CREATE TABLE opciones_respuesta (
    id_opcion BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pregunta BIGINT UNSIGNED NOT NULL,
    texto_opcion TEXT NOT NULL,
    es_correcta BOOLEAN NOT NULL DEFAULT FALSE,
    orden_correcto INT NULL,

    CONSTRAINT fk_opciones_preguntas
        FOREIGN KEY (id_pregunta)
        REFERENCES preguntas(id_pregunta)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT chk_opciones_orden
        CHECK (
            orden_correcto IS NULL
            OR orden_correcto > 0
        )

) ENGINE=InnoDB;


-- =====================================================
-- MODULO 3: EXAMENES FORMALES E INTENTOS
-- =====================================================

-- TABLA 7: EXAMENES

CREATE TABLE examenes (
    id_examen BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    duracion_minutos INT NOT NULL DEFAULT 50,
    minimo_aprobatorio INT NOT NULL DEFAULT 700,
    preguntas_por_pantalla INT NOT NULL DEFAULT 2,
    activo BOOLEAN NOT NULL DEFAULT TRUE,

    CONSTRAINT chk_examen_duracion
        CHECK (duracion_minutos BETWEEN 1 AND 50),

    CONSTRAINT chk_examen_aprobacion
        CHECK (minimo_aprobatorio BETWEEN 0 AND 1000),

    CONSTRAINT chk_examen_pantalla
        CHECK (preguntas_por_pantalla > 0)

) ENGINE=InnoDB;


-- TABLA 8: SOLICITUDES_INTENTO

CREATE TABLE solicitudes_intento (
    id_solicitud BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_usuario BIGINT UNSIGNED NOT NULL,
    id_coordinador BIGINT UNSIGNED NULL,
    fecha_solicitud DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    estado ENUM(
        'pendiente',
        'aprobada',
        'rechazada'
    ) NOT NULL DEFAULT 'pendiente',

    fecha_resolucion DATETIME NULL,
    observaciones TEXT NULL,

    CONSTRAINT fk_solicitudes_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_solicitudes_coordinador
        FOREIGN KEY (id_coordinador)
        REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT

) ENGINE=InnoDB;


-- TABLA 9: INTENTOS

CREATE TABLE intentos (
    id_intento BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_usuario BIGINT UNSIGNED NOT NULL,
    id_examen BIGINT UNSIGNED NOT NULL,
    id_solicitud BIGINT UNSIGNED NULL,

    numero_intento INT NOT NULL,

    fecha_inicio DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    fecha_fin DATETIME NULL,

    estado ENUM(
        'en_curso',
        'enviado',
        'tiempo_agotado'
    ) NOT NULL DEFAULT 'en_curso',

    CONSTRAINT fk_intentos_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_intentos_examen
        FOREIGN KEY (id_examen)
        REFERENCES examenes(id_examen)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_intentos_solicitud
        FOREIGN KEY (id_solicitud)
        REFERENCES solicitudes_intento(id_solicitud)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT uk_intentos_numero
        UNIQUE (id_usuario, id_examen, numero_intento),

    CONSTRAINT uk_intentos_solicitud
        UNIQUE (id_solicitud),

    CONSTRAINT chk_intentos_numero
        CHECK (numero_intento >= 1),

    CONSTRAINT chk_intentos_fechas
        CHECK (
            fecha_fin IS NULL
            OR fecha_fin >= fecha_inicio
        )

) ENGINE=InnoDB;


-- TABLA 10: PREGUNTAS_INTENTO

CREATE TABLE preguntas_intento (
    id_intento_pregunta BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_intento BIGINT UNSIGNED NOT NULL,
    id_pregunta BIGINT UNSIGNED NOT NULL,
    orden_presentacion INT NOT NULL,

    CONSTRAINT fk_preguntas_intento_intentos
        FOREIGN KEY (id_intento)
        REFERENCES intentos(id_intento)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_preguntas_intento_preguntas
        FOREIGN KEY (id_pregunta)
        REFERENCES preguntas(id_pregunta)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT uk_preguntas_intento
        UNIQUE (id_intento, id_pregunta),

    CONSTRAINT uk_preguntas_intento_orden
        UNIQUE (id_intento, orden_presentacion),

    CONSTRAINT chk_preguntas_orden
        CHECK (orden_presentacion > 0)

) ENGINE=InnoDB;


-- TABLA 11: RESPUESTAS_INTENTO

CREATE TABLE respuestas_intento (
    id_respuesta BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_intento_pregunta BIGINT UNSIGNED NOT NULL,
    fecha_respuesta DATETIME NULL,
    es_correcta BOOLEAN NULL,

    CONSTRAINT fk_respuestas_intento_pregunta
        FOREIGN KEY (id_intento_pregunta)
        REFERENCES preguntas_intento(id_intento_pregunta)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT uk_respuesta_pregunta
        UNIQUE (id_intento_pregunta)

) ENGINE=InnoDB;


-- TABLA 12: RESPUESTAS_OPCIONES

CREATE TABLE respuestas_opciones (
    id_respuesta BIGINT UNSIGNED NOT NULL,
    id_opcion BIGINT UNSIGNED NOT NULL,
    orden_seleccion INT NULL,

    PRIMARY KEY (id_respuesta, id_opcion),

    CONSTRAINT fk_respuestas_opciones_respuesta
        FOREIGN KEY (id_respuesta)
        REFERENCES respuestas_intento(id_respuesta)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_respuestas_opciones_opcion
        FOREIGN KEY (id_opcion)
        REFERENCES opciones_respuesta(id_opcion)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT uk_respuestas_orden
        UNIQUE (id_respuesta, orden_seleccion),

    CONSTRAINT chk_respuestas_orden
        CHECK (
            orden_seleccion IS NULL
            OR orden_seleccion > 0
        )

) ENGINE=InnoDB;


-- =====================================================
-- MODULO 4: RESULTADOS Y CALIFICACIONES
-- =====================================================

-- TABLA 13: RESULTADOS

CREATE TABLE resultados (
    id_resultado BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_intento BIGINT UNSIGNED NOT NULL UNIQUE,
    calificacion_final INT NOT NULL,

    dictamen ENUM(
        'APROBADO',
        'REPROBADO'
    ) NOT NULL,

    fecha_calificacion DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_resultados_intento
        FOREIGN KEY (id_intento)
        REFERENCES intentos(id_intento)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT chk_resultados_calificacion
        CHECK (calificacion_final BETWEEN 0 AND 1000),

    CONSTRAINT chk_resultados_dictamen
        CHECK (
            (calificacion_final >= 700
                AND dictamen = 'APROBADO')
            OR
            (calificacion_final < 700
                AND dictamen = 'REPROBADO')
        )

) ENGINE=InnoDB;


-- TABLA 14: DETALLE_RESULTADO_CATEGORIA

CREATE TABLE detalle_resultado_categoria (
    id_detalle_categoria BIGINT UNSIGNED
        AUTO_INCREMENT PRIMARY KEY,

    id_resultado BIGINT UNSIGNED NOT NULL,
    id_categoria BIGINT UNSIGNED NOT NULL,
    puntaje_obtenido DECIMAL(7,2) NOT NULL,
    ponderacion_aplicada DECIMAL(5,4)
        NOT NULL DEFAULT 0.2000,

    CONSTRAINT fk_detalle_categoria_resultado
        FOREIGN KEY (id_resultado)
        REFERENCES resultados(id_resultado)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_detalle_categoria_categoria
        FOREIGN KEY (id_categoria)
        REFERENCES categorias(id_categoria)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT uk_detalle_categoria
        UNIQUE (id_resultado, id_categoria),

    CONSTRAINT chk_detalle_categoria_puntaje
        CHECK (puntaje_obtenido BETWEEN 0 AND 200),

    CONSTRAINT chk_detalle_categoria_ponderacion
        CHECK (ponderacion_aplicada BETWEEN 0 AND 1)

) ENGINE=InnoDB;


-- TABLA 15: DETALLE_RESULTADO_TEMA

CREATE TABLE detalle_resultado_tema (
    id_detalle_tema BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_resultado BIGINT UNSIGNED NOT NULL,
    id_tema BIGINT UNSIGNED NOT NULL,
    preguntas_totales INT NOT NULL DEFAULT 0,
    respuestas_correctas INT NOT NULL DEFAULT 0,

    CONSTRAINT fk_detalle_tema_resultado
        FOREIGN KEY (id_resultado)
        REFERENCES resultados(id_resultado)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_detalle_tema_tema
        FOREIGN KEY (id_tema)
        REFERENCES temas(id_tema)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT uk_detalle_tema
        UNIQUE (id_resultado, id_tema),

    CONSTRAINT chk_detalle_tema_preguntas
        CHECK (
            preguntas_totales >= 0
            AND respuestas_correctas >= 0
            AND respuestas_correctas <= preguntas_totales
        )

) ENGINE=InnoDB;


-- =====================================================
-- MODULO 5: CONSTANCIAS DIGITALES
-- =====================================================

-- TABLA 16: CONSTANCIAS

CREATE TABLE constancias (
    id_constancia BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_resultado BIGINT UNSIGNED NOT NULL UNIQUE,
    folio VARCHAR(64) NOT NULL UNIQUE,
    clave_verificacion VARCHAR(128) NOT NULL UNIQUE,
    fecha_emision DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,
    ruta_pdf VARCHAR(255) NOT NULL,

    estado ENUM(
        'vigente',
        'revocada'
    ) NOT NULL DEFAULT 'vigente',

    CONSTRAINT fk_constancias_resultado
        FOREIGN KEY (id_resultado)
        REFERENCES resultados(id_resultado)
        ON UPDATE CASCADE
        ON DELETE RESTRICT

) ENGINE=InnoDB;


-- =====================================================
-- INSERCION DE ROLES INICIALES
-- =====================================================

INSERT INTO roles (nombre, descripcion)
VALUES
(
    'Alumno',
    'Usuario que realiza simuladores y examenes'
),
(
    'Coordinador',
    'Usuario que administra alumnos, preguntas y evaluaciones'
)
ON DUPLICATE KEY UPDATE
    descripcion = VALUES(descripcion);


-- =====================================================
-- COMPROBACION DE LA BASE DE DATOS
-- =====================================================

-- Listar las tablas creadas

SHOW TABLES;


-- Comprobar cantidad de tablas

SELECT COUNT(*) AS total_tablas
FROM information_schema.tables
WHERE table_schema = 'instrumento_evaluacion'
AND table_type = 'BASE TABLE';


-- Consultar los roles registrados

SELECT * FROM roles;


-- =====================================================
-- FIN DEL SCRIPT SQL
-- =====================================================
