-- Holy Trinity Parish - Migration V2
-- Staff Management, Lay Groups, SCCs, Liturgical, HR Features
-- Run this after the initial schema.sql

USE holy_trinity_parish;

-- ============================================
-- EXTEND USER ROLES to include staff types
-- ============================================
ALTER TABLE users MODIFY COLUMN role ENUM(
    'parishioner','priest','department_head','admin','super_admin',
    'guard','house_helper','general_worker','parish_executive','liturgical_coordinator'
) DEFAULT 'parishioner';

-- ============================================
-- SMALL CHRISTIAN COMMUNITIES (SCCs)
-- ============================================
CREATE TABLE IF NOT EXISTS small_christian_communities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    description TEXT,
    zone VARCHAR(150),
    leader_user_id INT,
    patron_saint VARCHAR(200),
    meeting_day VARCHAR(50),
    meeting_time TIME,
    meeting_venue VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (leader_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS scc_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scc_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('member','leader','secretary','treasurer','coordinator') DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (scc_id) REFERENCES small_christian_communities(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_scc_member (scc_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- LAY GROUPS (Catholic associations, societies)
-- ============================================
CREATE TABLE IF NOT EXISTS lay_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    description TEXT,
    group_type ENUM('association','society','movement','guild','other') DEFAULT 'association',
    leader_user_id INT,
    patron_saint VARCHAR(200),
    meeting_schedule VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (leader_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lay_group_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('member','leader','secretary','treasurer','coordinator') DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES lay_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_group_member (group_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- UNIFIED REPORTS SYSTEM
-- Reports from departments, lay groups, SCCs
-- Sent to parish priest or parish executive
-- ============================================
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_type ENUM('department','lay_group','scc','liturgical') NOT NULL,
    source_id INT NOT NULL,
    submitted_by INT NOT NULL,
    recipient_type ENUM('parish_priest','parish_executive','both') DEFAULT 'parish_priest',
    report_title VARCHAR(255) NOT NULL,
    report_content TEXT NOT NULL,
    report_type ENUM('weekly','monthly','quarterly','annual','special','activity','financial') DEFAULT 'monthly',
    report_period VARCHAR(100),
    attachment_path VARCHAR(500),
    status ENUM('draft','submitted','reviewed','acknowledged','returned') DEFAULT 'submitted',
    reviewer_notes TEXT,
    reviewed_by INT,
    reviewed_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- DOCUMENT SUBMISSIONS
-- SCCs/Lay groups send documents to parish executive
-- ============================================
CREATE TABLE IF NOT EXISTS document_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_type ENUM('department','lay_group','scc','liturgical','staff') NOT NULL,
    source_id INT NOT NULL,
    submitted_by INT NOT NULL,
    recipient_type ENUM('parish_priest','parish_executive','both') DEFAULT 'parish_executive',
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size INT,
    status ENUM('pending','received','reviewed','approved','rejected') DEFAULT 'pending',
    reviewer_notes TEXT,
    reviewed_by INT,
    reviewed_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- STAFF MANAGEMENT & ATTENDANCE
-- Guards, house helpers, general workers
-- ============================================
CREATE TABLE IF NOT EXISTS staff_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    staff_type ENUM('guard','house_helper','general_worker') NOT NULL,
    employee_id VARCHAR(50) UNIQUE,
    department VARCHAR(150),
    position_title VARCHAR(150),
    hire_date DATE,
    contract_type ENUM('permanent','contract','casual') DEFAULT 'permanent',
    salary DECIMAL(12,2) DEFAULT 0,
    bank_name VARCHAR(150),
    bank_account VARCHAR(100),
    nrc_number VARCHAR(50),
    next_of_kin_name VARCHAR(200),
    next_of_kin_phone VARCHAR(20),
    next_of_kin_relation VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS staff_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_user_id INT NOT NULL,
    clock_in DATETIME NOT NULL,
    clock_out DATETIME,
    clock_in_location VARCHAR(255),
    clock_out_location VARCHAR(255),
    hours_worked DECIMAL(5,2) DEFAULT 0,
    status ENUM('present','late','early_departure','absent','half_day') DEFAULT 'present',
    notes TEXT,
    shift_type ENUM('day','night','morning','afternoon') DEFAULT 'day',
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- PAYSLIPS
-- ============================================
CREATE TABLE IF NOT EXISTS payslips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_user_id INT NOT NULL,
    pay_period VARCHAR(50) NOT NULL,
    pay_date DATE NOT NULL,
    basic_salary DECIMAL(12,2) NOT NULL,
    allowances DECIMAL(12,2) DEFAULT 0,
    overtime_pay DECIMAL(12,2) DEFAULT 0,
    deductions DECIMAL(12,2) DEFAULT 0,
    tax DECIMAL(12,2) DEFAULT 0,
    napsa DECIMAL(12,2) DEFAULT 0,
    nhima DECIMAL(12,2) DEFAULT 0,
    net_pay DECIMAL(12,2) NOT NULL,
    payment_method ENUM('bank_transfer','cash','mobile_money') DEFAULT 'bank_transfer',
    payment_reference VARCHAR(100),
    notes TEXT,
    generated_by INT,
    status ENUM('draft','approved','paid','cancelled') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- LEAVE MANAGEMENT
-- Approved by parish executive, NOT priest
-- ============================================
CREATE TABLE IF NOT EXISTS leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_user_id INT NOT NULL,
    leave_type ENUM('annual','sick','compassionate','maternity','paternity','unpaid','study','other') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    days_requested INT NOT NULL,
    reason TEXT NOT NULL,
    attachment_path VARCHAR(500),
    status ENUM('pending','approved','rejected','cancelled') DEFAULT 'pending',
    approved_by INT,
    approver_notes TEXT,
    approved_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- LITURGICAL MANAGEMENT
-- ============================================
CREATE TABLE IF NOT EXISTS liturgical_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    liturgical_date DATE NOT NULL,
    liturgical_season VARCHAR(100),
    celebration VARCHAR(255),
    mass_time TIME,
    celebrant_id INT,
    readings TEXT,
    hymns TEXT,
    special_notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (celebrant_id) REFERENCES clergy(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS liturgical_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    role_name ENUM('lector','eucharistic_minister','altar_server','sacristan','usher','choir','cantor','commentator','gift_bearer') NOT NULL,
    assigned_user_id INT,
    assigned_name VARCHAR(200),
    status ENUM('assigned','confirmed','declined','substitute') DEFAULT 'assigned',
    notes VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (schedule_id) REFERENCES liturgical_schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS liturgical_issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reported_by INT NOT NULL,
    issue_type ENUM('vestment','vessel','book','furniture','sound_system','lighting','decoration','other') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('low','medium','high','urgent') DEFAULT 'medium',
    status ENUM('open','in_progress','resolved','closed') DEFAULT 'open',
    assigned_to INT,
    resolution_notes TEXT,
    resolved_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- PURCHASE REQUESTS & BUDGETS
-- Sent to parish executive for approval
-- ============================================
CREATE TABLE IF NOT EXISTS purchase_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requested_by INT NOT NULL,
    source_type ENUM('department','liturgical','lay_group','scc','general') NOT NULL,
    source_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    items_json JSON,
    total_amount DECIMAL(12,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'ZMW',
    urgency ENUM('low','normal','high','urgent') DEFAULT 'normal',
    status ENUM('draft','submitted','under_review','approved','rejected','purchased','cancelled') DEFAULT 'submitted',
    approved_by INT,
    approver_notes TEXT,
    approved_at DATETIME,
    receipt_path VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    submitted_by INT NOT NULL,
    source_type ENUM('department','liturgical','lay_group','scc','parish') NOT NULL,
    source_id INT,
    title VARCHAR(255) NOT NULL,
    budget_period VARCHAR(100) NOT NULL,
    description TEXT,
    items_json JSON,
    total_amount DECIMAL(12,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'ZMW',
    status ENUM('draft','submitted','under_review','approved','rejected','revised') DEFAULT 'submitted',
    approved_by INT,
    approver_notes TEXT,
    approved_at DATETIME,
    attachment_path VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- SEED DATA FOR NEW FEATURES
-- ============================================

-- Parish Executive user (password: Admin@123)
INSERT INTO users (first_name, last_name, email, phone, password_hash, role, is_active, email_verified) VALUES
('Michael', 'Chanda', 'executive@holytrinityparish.org', '+260711000010', '$2y$12$2Ck8Ra0uitw63YLhM9p1HOixLnUfUEpR2VMhO/FmbyqztttVcL5.6', 'parish_executive', 1, 1);

-- Liturgical Coordinator (password: Admin@123)
INSERT INTO users (first_name, last_name, email, phone, password_hash, role, is_active, email_verified) VALUES
('Theresa', 'Mwape', 'liturgy@holytrinityparish.org', '+260711000011', '$2y$12$2Ck8Ra0uitw63YLhM9p1HOixLnUfUEpR2VMhO/FmbyqztttVcL5.6', 'liturgical_coordinator', 1, 1);

-- Sample Staff: Guards (password: Staff@123 = same hash for demo)
INSERT INTO users (first_name, last_name, email, phone, password_hash, role, is_active, email_verified) VALUES
('John', 'Mwila', 'guard1@holytrinityparish.org', '+260711000020', '$2y$12$2Ck8Ra0uitw63YLhM9p1HOixLnUfUEpR2VMhO/FmbyqztttVcL5.6', 'guard', 1, 1),
('Emmanuel', 'Kapata', 'guard2@holytrinityparish.org', '+260711000021', '$2y$12$2Ck8Ra0uitw63YLhM9p1HOixLnUfUEpR2VMhO/FmbyqztttVcL5.6', 'guard', 1, 1);

-- Sample Staff: House Helpers
INSERT INTO users (first_name, last_name, email, phone, password_hash, role, is_active, email_verified) VALUES
('Esther', 'Lungu', 'helper1@holytrinityparish.org', '+260711000030', '$2y$12$2Ck8Ra0uitw63YLhM9p1HOixLnUfUEpR2VMhO/FmbyqztttVcL5.6', 'house_helper', 1, 1);

-- Sample Staff: General Workers
INSERT INTO users (first_name, last_name, email, phone, password_hash, role, is_active, email_verified) VALUES
('Patrick', 'Zulu', 'worker1@holytrinityparish.org', '+260711000040', '$2y$12$2Ck8Ra0uitw63YLhM9p1HOixLnUfUEpR2VMhO/FmbyqztttVcL5.6', 'general_worker', 1, 1);

-- Staff profiles (IDs depend on insert order - adjust as needed)
-- These use placeholder user IDs that should be updated after running
INSERT INTO staff_profiles (user_id, staff_type, employee_id, position_title, hire_date, contract_type, salary) VALUES
((SELECT id FROM users WHERE email='guard1@holytrinityparish.org'), 'guard', 'HTP-G001', 'Security Guard - Day Shift', '2023-01-15', 'permanent', 3500.00),
((SELECT id FROM users WHERE email='guard2@holytrinityparish.org'), 'guard', 'HTP-G002', 'Security Guard - Night Shift', '2023-03-01', 'permanent', 3500.00),
((SELECT id FROM users WHERE email='helper1@holytrinityparish.org'), 'house_helper', 'HTP-H001', 'House Helper - Rectory', '2022-06-01', 'permanent', 3000.00),
((SELECT id FROM users WHERE email='worker1@holytrinityparish.org'), 'general_worker', 'HTP-W001', 'General Maintenance Worker', '2023-06-15', 'permanent', 3200.00);

-- Sample SCCs
INSERT INTO small_christian_communities (name, slug, description, zone, meeting_day, meeting_time) VALUES
('St. Joseph SCC', 'st-joseph-scc', 'Small Christian Community of St. Joseph', 'Zone A - Kabwe Central', 'Thursday', '17:00:00'),
('St. Monica SCC', 'st-monica-scc', 'Small Christian Community of St. Monica', 'Zone B - Kabwe East', 'Wednesday', '17:00:00'),
('St. Paul SCC', 'st-paul-scc', 'Small Christian Community of St. Paul', 'Zone C - Kabwe West', 'Friday', '17:00:00'),
('Holy Family SCC', 'holy-family-scc', 'Small Christian Community of the Holy Family', 'Zone A - Kabwe Central', 'Tuesday', '17:30:00'),
('St. Theresa SCC', 'st-theresa-scc', 'Small Christian Community of St. Theresa', 'Zone B - Kabwe East', 'Saturday', '14:00:00');

-- Sample Lay Groups
INSERT INTO lay_groups (name, slug, description, group_type, meeting_schedule) VALUES
('Legion of Mary', 'legion-of-mary', 'A lay apostolic association serving the Church and community through prayer and evangelization', 'association', 'Every Wednesday at 5:00 PM'),
('Catholic Women Association', 'catholic-women', 'Empowering women in faith, service, and community development', 'association', 'Second Sunday of every month'),
('Catholic Men Association', 'catholic-men', 'Men united in faith, service, and leadership', 'association', 'First Sunday of every month'),
('St. Vincent de Paul Society', 'st-vincent-de-paul', 'Dedicated to serving the poor and marginalized in our community', 'society', 'First Saturday of every month'),
('Catholic Youth Organisation', 'catholic-youth', 'Engaging young Catholics in faith formation and community service', 'movement', 'Every Saturday at 2:00 PM'),
('Sacred Heart Confraternity', 'sacred-heart', 'Devotion to the Sacred Heart of Jesus through prayer and service', 'guild', 'First Friday of every month');
