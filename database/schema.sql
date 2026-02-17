-- Holy Trinity Parish Church Management System
-- Database Schema

CREATE DATABASE IF NOT EXISTS holy_trinity_parish;
USE holy_trinity_parish;

-- ============================================
-- USERS & AUTHENTICATION
-- ============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('parishioner','priest','department_head','admin','super_admin') DEFAULT 'parishioner',
    profile_photo VARCHAR(255) DEFAULT NULL,
    date_of_birth DATE,
    gender ENUM('male','female','other'),
    address TEXT,
    emergency_contact VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    email_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(255),
    reset_token VARCHAR(255),
    reset_token_expiry DATETIME,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- DEPARTMENTS
-- ============================================
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) UNIQUE NOT NULL,
    description TEXT,
    head_user_id INT,
    email VARCHAR(255),
    phone VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (head_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE department_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    user_id INT NOT NULL,
    role VARCHAR(100) DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_dept_member (department_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- CLERGY PROFILES
-- ============================================
CREATE TABLE clergy (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(50) NOT NULL,
    full_name VARCHAR(200) NOT NULL,
    position VARCHAR(150) NOT NULL,
    bio TEXT,
    photo VARCHAR(255),
    ordination_date DATE,
    appointment_date DATE,
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- MASS SCHEDULE & SERVICES
-- ============================================
CREATE TABLE mass_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
    time TIME NOT NULL,
    mass_type VARCHAR(100) NOT NULL,
    language VARCHAR(50) DEFAULT 'English',
    location VARCHAR(150) DEFAULT 'Main Church',
    celebrant_id INT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (celebrant_id) REFERENCES clergy(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- EVENTS & ANNOUNCEMENTS
-- ============================================
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    location VARCHAR(255),
    category VARCHAR(100),
    image VARCHAR(255),
    max_attendees INT DEFAULT 0,
    registration_required TINYINT(1) DEFAULT 0,
    is_featured TINYINT(1) DEFAULT 0,
    status ENUM('draft','published','cancelled') DEFAULT 'published',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE event_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT,
    guest_name VARCHAR(200),
    guest_email VARCHAR(255),
    guest_phone VARCHAR(20),
    num_attendees INT DEFAULT 1,
    status ENUM('registered','confirmed','cancelled') DEFAULT 'registered',
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    category VARCHAR(100),
    priority ENUM('low','normal','high','urgent') DEFAULT 'normal',
    is_pinned TINYINT(1) DEFAULT 0,
    publish_date DATE,
    expiry_date DATE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- APPOINTMENTS
-- ============================================
CREATE TABLE appointment_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT NOT NULL,
    department_id INT,
    day_of_week TINYINT NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    slot_duration INT DEFAULT 30,
    max_bookings INT DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_number VARCHAR(20) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    provider_id INT NOT NULL,
    department_id INT,
    appointment_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    reason VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('pending','approved','declined','completed','cancelled','no_show') DEFAULT 'pending',
    admin_notes TEXT,
    document_path VARCHAR(255),
    reminder_sent TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- SACRAMENTAL RECORDS
-- ============================================
CREATE TABLE sacramental_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_type ENUM('baptism','confirmation','marriage','funeral','first_communion','anointing') NOT NULL,
    reference_number VARCHAR(30) UNIQUE NOT NULL,
    -- Person details
    person_first_name VARCHAR(100) NOT NULL,
    person_last_name VARCHAR(100) NOT NULL,
    person_dob DATE,
    person_gender ENUM('male','female'),
    -- Parents
    father_name VARCHAR(200),
    mother_name VARCHAR(200),
    -- Sacrament details
    sacrament_date DATE NOT NULL,
    minister_name VARCHAR(200),
    minister_id INT,
    place VARCHAR(255) DEFAULT 'Holy Trinity Parish',
    -- Sponsors/Witnesses
    sponsor1_name VARCHAR(200),
    sponsor2_name VARCHAR(200),
    -- Marriage specific
    spouse_name VARCHAR(200),
    spouse_dob DATE,
    spouse_father VARCHAR(200),
    spouse_mother VARCHAR(200),
    -- Funeral specific
    death_date DATE,
    burial_place VARCHAR(255),
    -- General
    register_number VARCHAR(50),
    page_number VARCHAR(20),
    entry_number VARCHAR(20),
    notes TEXT,
    document_path VARCHAR(255),
    recorded_by INT,
    is_archived TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (minister_id) REFERENCES clergy(id) ON DELETE SET NULL,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- DONATIONS & GIVING
-- ============================================
CREATE TABLE donation_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    target_amount DECIMAL(12,2) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE donations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_ref VARCHAR(50) UNIQUE NOT NULL,
    user_id INT,
    donor_name VARCHAR(200),
    donor_email VARCHAR(255),
    donor_phone VARCHAR(20),
    category_id INT,
    amount DECIMAL(12,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'UGX',
    payment_method ENUM('cash','mobile_money','bank_transfer','card','online') DEFAULT 'online',
    payment_status ENUM('pending','completed','failed','refunded') DEFAULT 'pending',
    payment_reference VARCHAR(255),
    donation_date DATE NOT NULL,
    notes TEXT,
    receipt_sent TINYINT(1) DEFAULT 0,
    is_anonymous TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES donation_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- SERMONS & REFLECTIONS
-- ============================================
CREATE TABLE sermons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content TEXT,
    scripture_reference VARCHAR(255),
    preacher_id INT,
    sermon_date DATE,
    audio_url VARCHAR(500),
    video_url VARCHAR(500),
    is_featured TINYINT(1) DEFAULT 0,
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (preacher_id) REFERENCES clergy(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- MINISTRIES
-- ============================================
CREATE TABLE ministries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    leader_id INT,
    meeting_schedule VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (leader_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ministry_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ministry_id INT NOT NULL,
    user_id INT NOT NULL,
    role VARCHAR(100) DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ministry_id) REFERENCES ministries(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_ministry_member (ministry_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- ATTENDANCE TRACKING
-- ============================================
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type ENUM('department','ministry','event','mass') NOT NULL,
    entity_id INT NOT NULL,
    user_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present','absent','excused','late') DEFAULT 'present',
    notes VARCHAR(255),
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- DOCUMENTS & FILES
-- ============================================
CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size INT,
    department_id INT,
    uploaded_by INT,
    category VARCHAR(100),
    is_public TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- NEWSLETTERS
-- ============================================
CREATE TABLE newsletters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    first_name VARCHAR(100),
    is_subscribed TINYINT(1) DEFAULT 1,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- AUDIT LOGS
-- ============================================
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100),
    entity_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- SYSTEM SETTINGS
-- ============================================
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_group VARCHAR(50) DEFAULT 'general',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- NOTIFICATIONS
-- ============================================
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    department_id INT,
    role_target VARCHAR(50),
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info','success','warning','urgent','report') DEFAULT 'info',
    link VARCHAR(500),
    is_read TINYINT(1) DEFAULT 0,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- DEPARTMENT REPORTS (sent to Parish Priest)
-- ============================================
CREATE TABLE department_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    submitted_by INT NOT NULL,
    report_title VARCHAR(255) NOT NULL,
    report_content TEXT NOT NULL,
    report_type ENUM('weekly','monthly','quarterly','annual','special') DEFAULT 'monthly',
    report_period VARCHAR(100),
    attachment_path VARCHAR(500),
    status ENUM('draft','submitted','reviewed','acknowledged') DEFAULT 'submitted',
    priest_notes TEXT,
    reviewed_by INT,
    reviewed_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- SEED DATA
-- ============================================

-- Default Settings
INSERT INTO settings (setting_key, setting_value, setting_group) VALUES
('site_name', 'Holy Trinity Parish', 'general'),
('site_tagline', 'A Community of Faith, Hope & Love', 'general'),
('parish_email', 'info@holytrinityparish.org', 'contact'),
('parish_phone', '+256-XXX-XXXXXX', 'contact'),
('parish_address', 'Holy Trinity Parish, Kampala, Uganda', 'contact'),
('currency', 'UGX', 'finance'),
('timezone', 'Africa/Kampala', 'general'),
('maintenance_mode', '0', 'system');

-- Default Admin User (password: Admin@123)
INSERT INTO users (first_name, last_name, email, phone, password_hash, role, is_active, email_verified) VALUES
('System', 'Administrator', 'admin@holytrinityparish.org', '+256700000000', '$2y$12$2Ck8Ra0uitw63YLhM9p1HOixLnUfUEpR2VMhO/FmbyqztttVcL5.6', 'super_admin', 1, 1);

-- Default Departments
INSERT INTO departments (name, slug, description) VALUES
('Parish Office', 'parish-office', 'Central administrative office of the parish'),
('Finance Department', 'finance', 'Manages parish finances, budgets, and donations'),
('Catechism Department', 'catechism', 'Responsible for faith formation and religious education'),
('Youth Ministry', 'youth-ministry', 'Engages and nurtures the faith of young parishioners'),
('Choir', 'choir', 'Leads the parish in liturgical music and worship'),
('Marriage & Family Life', 'marriage-family', 'Supports married couples and families in faith'),
('Social Outreach', 'social-outreach', 'Coordinates charitable works and community service');

-- Default Donation Categories
INSERT INTO donation_categories (name, description) VALUES
('Sunday Tithe', 'Regular Sunday offering'),
('Building Fund', 'Contributions towards church building and maintenance'),
('Charity Fund', 'Support for the less fortunate in our community'),
('Mission Fund', 'Supporting missionary work and evangelization'),
('Special Collection', 'Special purpose collections as announced');

-- Default Mass Schedule
INSERT INTO mass_schedules (day_of_week, time, mass_type, language) VALUES
('Sunday', '07:00:00', 'Holy Mass', 'English'),
('Sunday', '09:00:00', 'Holy Mass', 'English'),
('Sunday', '11:00:00', 'Holy Mass', 'Luganda'),
('Sunday', '17:00:00', 'Evening Mass', 'English'),
('Monday', '07:00:00', 'Weekday Mass', 'English'),
('Tuesday', '07:00:00', 'Weekday Mass', 'English'),
('Wednesday', '07:00:00', 'Weekday Mass', 'English'),
('Thursday', '07:00:00', 'Weekday Mass', 'English'),
('Thursday', '18:00:00', 'Adoration & Benediction', 'English'),
('Friday', '07:00:00', 'Weekday Mass', 'English'),
('Friday', '15:00:00', 'Stations of the Cross', 'English'),
('Saturday', '07:00:00', 'Weekday Mass', 'English'),
('Saturday', '09:00:00', 'Confessions', 'English'),
('Saturday', '17:00:00', 'Vigil Mass', 'English');

-- Sample Clergy
INSERT INTO clergy (title, full_name, position, bio, display_order) VALUES
('Rev. Fr.', 'John Mukasa', 'Parish Priest', 'Fr. John Mukasa has served as Parish Priest of Holy Trinity Parish since 2020. He holds a Doctorate in Canon Law and is passionate about building a vibrant faith community.', 1),
('Rev. Fr.', 'Peter Ssemakula', 'Assistant Parish Priest', 'Fr. Peter Ssemakula joined Holy Trinity Parish in 2022. He oversees the youth ministry and catechetical programs.', 2),
('Deacon', 'Joseph Kato', 'Permanent Deacon', 'Deacon Joseph Kato was ordained in 2018 and serves the parish through liturgical ministry and charitable works.', 3);

-- Sample Ministries
INSERT INTO ministries (name, description, meeting_schedule) VALUES
('Altar Servers', 'Young people who assist the priest during Mass', 'Every Saturday at 3:00 PM'),
('Legion of Mary', 'A lay apostolic association serving the Church and community', 'Every Wednesday at 5:00 PM'),
('St. Vincent de Paul Society', 'Dedicated to serving the poor and marginalized', 'First Saturday of every month'),
('Catholic Charismatic Renewal', 'A movement of spiritual renewal through the Holy Spirit', 'Every Friday at 6:00 PM'),
('Catholic Women Association', 'Empowering women in faith and service', 'Second Sunday of every month');
