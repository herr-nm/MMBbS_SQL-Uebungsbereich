-- SQL-Dump für 3D-Druck Übungsumgebung
-- Kompatibel mit SQLite

-- Tabelle: Drucker
CREATE TABLE IF NOT EXISTS drucker (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    modell TEXT NOT NULL,
    typ TEXT CHECK(typ IN ('FDM', 'SLA')),
    baujahr INTEGER,
    status TEXT DEFAULT 'bereit'
);

-- Tabelle: Filamente
CREATE TABLE IF NOT EXISTS filamente (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    material TEXT NOT NULL,
    farbe TEXT NOT NULL,
    hersteller TEXT,
    restgewicht_gramm INTEGER
);

-- Tabelle: Druckauftraege
CREATE TABLE IF NOT EXISTS druckauftraege (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    objekt_name TEXT NOT NULL,
    drucker_id INTEGER,
    filament_id INTEGER,
    dauer_minuten INTEGER,
    erfolgreich BOOLEAN,
    FOREIGN KEY (drucker_id) REFERENCES drucker(id),
    FOREIGN KEY (filament_id) REFERENCES filamente(id)
);

-- Testdaten einfügen
INSERT INTO drucker (modell, typ, baujahr, status) VALUES 
('Prusa i3 MK3S+', 'FDM', 2021, 'bereit'),
('Creality Ender 3', 'FDM', 2022, 'wartung'),
('Anycubic Photon Mono', 'SLA', 2023, 'bereit'),
('Bambu Lab X1C', 'FDM', 2024, 'druckend');

INSERT INTO filamente (material, farbe, hersteller, restgewicht_gramm) VALUES 
('PLA', 'Anthrazit', 'Prusament', 750),
('PETG', 'Signalorange', 'Extrudr', 400),
('ABS', 'Weiß', 'Sunlu', 200),
('Resin', 'Grau', 'Anycubic', 1000);

INSERT INTO druckauftraege (objekt_name, drucker_id, filament_id, dauer_minuten, erfolgreich) VALUES 
('Benchy - Testschiff', 1, 1, 110, 1),
('Gehäusedeckel V2', 1, 2, 450, 1),
('Miniatur-Held', 3, 4, 180, 1),
('Halterung Wand', 4, 1, 90, 0);