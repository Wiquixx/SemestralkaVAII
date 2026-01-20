-- Vzorové dáta pre users
INSERT INTO users (email, password_hash, display_name, status) VALUES
('john.doe@example.com', '$2y$10$GRA8D27bvZZw8b85CAwRee9NH5nj4CQA6PDFMc90pN9Wi4VAWq3yq', 'John Doe', 1);

-- Vzorové dáta pre plants
INSERT INTO plants (user_id, common_name, scientific_name, location, purchase_date, notes) VALUES
(1, 'Ficus', 'Ficus benjamina', 'Obývačka', '2023-03-15', 'Potrebné pravidelné zalievanie.');

-- Vzorové dáta pre images
-- NOTE: use uploads/ path for uploaded files
INSERT INTO images (plant_id, file_path) VALUES
(1, 'uploads/ficus1.jpg');

-- Vzorové dáta pre reminders
INSERT INTO reminders (user_id, plant_id, remind_date, frequency_days, title, notes, active) VALUES
(1, 1, '2025-01-10', 7, 'Zalievanie Ficus', 'Zaliať každých 7 dní.', TRUE);

-- Vzorové dáta pre care_actions
INSERT INTO care_actions (plant_id, action_type, action_date, notes, created_by) VALUES
(1, 'watering', '2025-01-10', 'Zaliate podľa plánu.', 1);
