-- Table: users
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    status TINYINT NOT NULL DEFAULT 2 COMMENT '1=admin,2=user',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table: plants
CREATE TABLE plants (
    plant_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    common_name VARCHAR(255) NOT NULL,
    scientific_name VARCHAR(255),
    location VARCHAR(255),
    purchase_date DATE,
    notes TEXT,
    CONSTRAINT fk_plants_user FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- Table: images
CREATE TABLE images (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    plant_id INT NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_images_plant FOREIGN KEY (plant_id) REFERENCES plants(plant_id)
) ENGINE=InnoDB;

-- Table: reminders
CREATE TABLE reminders (
    reminder_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plant_id INT NOT NULL,
    remind_date DATE NOT NULL,
    frequency_days INT,
    title VARCHAR(255),
    notes TEXT,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reminders_user FOREIGN KEY (user_id) REFERENCES users(user_id),
    CONSTRAINT fk_reminders_plant FOREIGN KEY (plant_id) REFERENCES plants(plant_id)
) ENGINE=InnoDB;

-- Index for users.email
CREATE UNIQUE INDEX idx_users_email ON users(email);
