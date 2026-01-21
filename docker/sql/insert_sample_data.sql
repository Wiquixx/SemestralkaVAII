-- Vytvorené s pomocou Github Copilot
-- Vzorové dáta pre users
INSERT INTO users (email, password_hash, display_name, status) VALUES
        ('john.doe@example.com', '$2y$10$GRA8D27bvZZw8b85CAwRee9NH5nj4CQA6PDFMc90pN9Wi4VAWq3yq', 'John Doe', 1),
        ('jane.smith@example.com', '$2y$10$GRA8D27bvZZw8b85CAwRee9NH5nj4CQA6PDFMc90pN9Wi4VAWq3yq', 'Jane Smith', 2),
        ('mark.novak@example.com', '$2y$10$GRA8D27bvZZw8b85CAwRee9NH5nj4CQA6PDFMc90pN9Wi4VAWq3yq', 'Mark Novak', 2);

-- Vzorové dáta pre plants
INSERT INTO plants (user_id, common_name, scientific_name, location, purchase_date, notes) VALUES
        (1, 'Ficus', 'Ficus benjamina', 'Obývačka', '2023-03-15', 'Potrebné pravidelné zalievanie.'),
        (1, 'Monstera', 'Monstera deliciosa', 'Spálňa', '2023-06-10', 'Má rada rozptýlené svetlo.'),
        (1, 'Aloe Vera', 'Aloe vera', 'Kuchyňa', '2024-01-05', 'Zalievať len zriedka.'),
        (2, 'Potos', 'Epipremnum aureum', 'Obývačka', '2023-02-20', 'Nenáročná rastlina.'),
        (2, 'Levanduľa', 'Lavandula angustifolia', 'Balkón', '2024-04-12', 'Vyžaduje veľa slnka.'),
        (2, 'Kaktus', 'Echinocactus grusonii', 'Pracovňa', '2022-09-01', 'Zalievať minimálne.'),
        (3, 'Dracéna', 'Dracaena marginata', 'Chodba', '2023-11-18', 'Neznáša preliatie.'),
        (3, 'Bazalka', 'Ocimum basilicum', 'Kuchyňa', '2024-05-03', 'Pravidelné strihanie podporuje rast.'),
        (3, 'Papraď', 'Nephrolepis exaltata', 'Kúpeľňa', '2023-07-22', 'Má rada vyššiu vlhkosť.');

-- Vzorové dáta pre images
-- NOTE: use uploads/ path for uploaded files
INSERT INTO images (plant_id, file_path) VALUES
    (1, 'uploads/ficus1.jpg');

-- Vzorové dáta pre reminders
INSERT INTO reminders (user_id, plant_id, remind_date, frequency_days, title, notes, active) VALUES
        (1, 1, '2025-01-10', 7, 'Zalievanie Ficus', 'Zaliať každých 7 dní.', TRUE),
        (1, 2, '2025-01-12', 10, 'Zalievanie Monstera', 'Zaliať každých 10 dní.', TRUE),
        (2, 4, '2025-01-11', 7, 'Zalievanie Potos', 'Udržiavať mierne vlhkú pôdu.', TRUE),
        (2, 5, '2025-01-20', 14, 'Kontrola levandule', 'Skontrolovať suchosť pôdy.', TRUE),
        (3, 7, '2025-01-15', 10, 'Zalievanie Dracéna', 'Zaliať po preschnutí vrchnej vrstvy.', TRUE),
        (3, 9, '2025-01-18', 5, 'Rosenie paprade', 'Udržiavať vysokú vlhkosť.', TRUE);
