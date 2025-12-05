-- Table: users
CREATE TABLE users (
    user_id SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Table: plants
CREATE TABLE plants (
    plant_id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    common_name VARCHAR(255) NOT NULL,
    scientific_name VARCHAR(255),
    location VARCHAR(255),
    purchase_date DATE,
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Table: images
CREATE TABLE images (
    image_id SERIAL PRIMARY KEY,
    plant_id INTEGER NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plant_id) REFERENCES plants(plant_id)
);

-- Table: reminders
CREATE TABLE reminders (
    reminder_id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    plant_id INTEGER NOT NULL,
    remind_date DATE NOT NULL,
    frequency_days INTEGER,
    title VARCHAR(255),
    notes TEXT,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (plant_id) REFERENCES plants(plant_id)
);

-- Table: care_actions
CREATE TABLE care_actions (
    action_id SERIAL PRIMARY KEY,
    plant_id INTEGER NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    action_date DATE NOT NULL,
    notes TEXT,
    created_by INTEGER NOT NULL,
    FOREIGN KEY (plant_id) REFERENCES plants(plant_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Index for users.email
CREATE UNIQUE INDEX idx_users_email ON users(email);

