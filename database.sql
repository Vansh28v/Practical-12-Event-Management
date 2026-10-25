CREATE DATABASE IF NOT EXISTS event_management
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE event_management;

CREATE TABLE IF NOT EXISTS events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(80) NOT NULL,
    event_date DATE NOT NULL,
    venue VARCHAR(180) NOT NULL,
    status ENUM('open', 'closed') NOT NULL DEFAULT 'open',
    poster_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_event_date (event_date),
    INDEX idx_event_category (category),
    INDEX idx_event_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS event_audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NULL,
    actor VARCHAR(100) NOT NULL,
    action ENUM('CREATE', 'UPDATE', 'DELETE') NOT NULL,
    details VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_event (event_id),
    INDEX idx_audit_created (created_at)
) ENGINE=InnoDB;

INSERT INTO events (title, description, category, event_date, venue, status, poster_path) VALUES
('Campus Tech Summit', 'A student-focused technology and innovation summit with talks and demonstrations.', 'Technology', '2026-11-14', 'Main Auditorium', 'open', NULL),
('Design Thinking Workshop', 'A hands-on workshop for solving problems through design thinking.', 'Workshop', '2026-11-20', 'Innovation Lab', 'open', NULL),
('Annual Cultural Evening', 'Music, dance, performances and student-led cultural activities.', 'Cultural', '2026-12-05', 'Open Air Theatre', 'open', NULL),
('Career Connect', 'Career guidance, alumni talks and industry networking sessions.', 'Career', '2026-12-12', 'Seminar Hall A', 'closed', NULL),
('Cybersecurity Bootcamp', 'Introductory sessions on safe computing and cybersecurity fundamentals.', 'Technology', '2026-11-28', 'Computer Lab 2', 'open', NULL),
('Entrepreneurship Forum', 'A discussion on student startups, product ideas and business models.', 'Business', '2027-01-09', 'Conference Room', 'open', NULL),
('Robotics Showcase', 'Student robotics projects and live demonstrations.', 'Technology', '2027-01-18', 'Project Lab', 'open', NULL),
('Photography Walk', 'A guided campus photography walk for beginners and enthusiasts.', 'Cultural', '2027-01-24', 'Campus Main Gate', 'closed', NULL);
