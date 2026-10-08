-- CommunityConnect database schema + seed data
-- Sto. Niño Barangay Hall, Eustaquio St. announcement & document request system

CREATE DATABASE IF NOT EXISTS communityconnect2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE communityconnect2;

-- Allows re-running this script on a database that already has these tables,
-- even though some tables below have foreign keys to others (drop order
-- would otherwise matter).
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Table: admin_users
-- ------------------------------------------------------------
DROP TABLE IF EXISTS admin_users;
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Demo login: admin / admin123
INSERT INTO admin_users (username, password_hash, full_name) VALUES
('admin', '$2y$10$xAcXerJXMuxcIXVB57JPdO/7mKQim/lx2be.aNAp7uH37NI87WMf2', 'Barangay Staff Admin'),
('Jovi', '$2y$10$mLiAgh.N5LFva2lbCGCgpulVZuanblgCqBQf0PTrNccTXtHf1BFKq', 'Jovi'),
('mharn', '$2y$10$.TEAEU3.HhcGiKi4BAqzeOYjEiOQacILQCPwCF/f70uM/F.fwLG.2', 'Mharn'),
('maurice', '$2y$10$Waa2RPfK3rcyXUl9HDdCk.foE291jSn6OOhjz/G/iC/BAyGVrYaw.', 'Maurice');

-- ------------------------------------------------------------
-- Table: residents (public resident accounts — completely separate from
-- admin_users; residents log in to submit document requests, problem
-- reports, and lost item reports, but can never reach the admin panel)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS residents;
CREATE TABLE residents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    contact_number VARCHAR(50) DEFAULT NULL,
    address VARCHAR(200) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at DATETIME DEFAULT NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: categories
-- ------------------------------------------------------------
DROP TABLE IF EXISTS categories;
CREATE TABLE categories (
    id VARCHAR(20) PRIMARY KEY,
    name VARCHAR(60) NOT NULL UNIQUE,
    color VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO categories (id, name, color) VALUES
('services', 'Community Services', '#c9962d'),
('events', 'Events', '#1f8f8a'),
('general', 'General Notice', '#657577'),
('health', 'Health & Wellness', '#3a7bd5'),
('jobs', 'Livelihood & Jobs', '#2a9d5c'),
('advisory', 'Public Advisory', '#e8734a');

-- ------------------------------------------------------------
-- Table: announcements
-- ------------------------------------------------------------
DROP TABLE IF EXISTS announcements;
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    category VARCHAR(50) NOT NULL,
    short_description VARCHAR(300) NOT NULL,
    content TEXT NOT NULL,
    desk_name VARCHAR(100) NOT NULL DEFAULT 'Barangay Office',
    image_path VARCHAR(255) DEFAULT NULL,
    views INT NOT NULL DEFAULT 0,
    is_urgent TINYINT(1) NOT NULL DEFAULT 0,
    is_trending TINYINT(1) NOT NULL DEFAULT 0,
    is_recommended TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: announcement_photos (multiple photos per announcement)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS announcement_photos;
CREATE TABLE announcement_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: document_requests
-- ------------------------------------------------------------
DROP TABLE IF EXISTS document_requests;
CREATE TABLE document_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_number VARCHAR(20) NOT NULL UNIQUE,
    resident_name VARCHAR(150) NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    address VARCHAR(200) NOT NULL,
    purpose VARCHAR(255) NOT NULL,
    id_proof VARCHAR(255) DEFAULT NULL,
    id_proof_image VARCHAR(255) DEFAULT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Pending',
    resident_id INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: document_request_photos (multiple photos per request)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS document_request_photos;
CREATE TABLE document_request_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES document_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: problem_reports
-- ------------------------------------------------------------
DROP TABLE IF EXISTS problem_reports;
CREATE TABLE problem_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_number VARCHAR(20) NOT NULL UNIQUE,
    resident_name VARCHAR(150) NOT NULL,
    location VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    photo_path VARCHAR(255) DEFAULT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Pending',
    resolved_at DATETIME DEFAULT NULL,
    resident_id INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: problem_report_photos (multiple photos per report)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS problem_report_photos;
CREATE TABLE problem_report_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES problem_reports(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: problem_report_after_photos ("After" proof-of-fix photos, added by admin)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS problem_report_after_photos;
CREATE TABLE problem_report_after_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES problem_reports(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: lost_found_items (independent from announcements)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS lost_found_items;
CREATE TABLE lost_found_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    photo_path VARCHAR(255) DEFAULT NULL,
    date_found DATE NOT NULL,
    location_found VARCHAR(200) NOT NULL,
    resolved_lost_report_id INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resolved_lost_report_id) REFERENCES lost_item_reports(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: lost_found_item_photos (multiple photos per item)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS lost_found_item_photos;
CREATE TABLE lost_found_item_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES lost_found_items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- No seed rows: every Lost Item requires a real uploaded photo,
-- so items are added through the admin panel (Admin -> Lost Item -> + New Item).

-- ------------------------------------------------------------
-- Table: lost_item_reports (resident-submitted "I lost this" reports,
-- separate from the staff-posted lost_found_items "Found" listings)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS lost_item_reports;
CREATE TABLE lost_item_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    date_lost DATE NOT NULL,
    location_lost VARCHAR(200) NOT NULL,
    contact_info VARCHAR(255) DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Pending',
    resident_id INT DEFAULT NULL,
    resident_name VARCHAR(150) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: lost_item_report_photos (multiple photos per resident report)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS lost_item_report_photos;
CREATE TABLE lost_item_report_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES lost_item_reports(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: community_posts (general resident feed — not official
-- announcements; anything a resident wants to share with the community)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS community_posts;
CREATE TABLE community_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resident_id INT DEFAULT NULL,
    resident_name VARCHAR(150) NOT NULL,
    content VARCHAR(1000) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: community_post_photos (multiple photos per post)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS community_post_photos;
CREATE TABLE community_post_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES community_posts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: post_comments
-- ------------------------------------------------------------
DROP TABLE IF EXISTS post_comments;
CREATE TABLE post_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    resident_id INT DEFAULT NULL,
    resident_name VARCHAR(150) NOT NULL,
    comment_text VARCHAR(500) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES community_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: post_reactions (Facebook-style; one row per resident per post —
-- the UNIQUE key is what enforces "one reaction per resident per post" and
-- lets us look up/replace/remove a resident's specific reaction)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS post_reactions;
CREATE TABLE post_reactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    resident_id INT NOT NULL,
    reaction_type VARCHAR(20) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_post_resident (post_id, resident_id),
    FOREIGN KEY (post_id) REFERENCES community_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: chatbot_messages (Gemini-powered assistant conversation log,
-- also used to enforce the per-resident hourly rate limit)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS chatbot_messages;
CREATE TABLE chatbot_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resident_id INT NOT NULL,
    role ENUM('user','assistant') NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_resident_created (resident_id, created_at),
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: advisory_banner (single editable row for the public urgent banner)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS advisory_banner;
CREATE TABLE advisory_banner (
    id INT PRIMARY KEY,
    message VARCHAR(500) NOT NULL,
    severity ENUM('yellow','red') NOT NULL DEFAULT 'red',
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO advisory_banner (id, message, severity, is_active) VALUES
(1, 'Water interruption is scheduled in Purok 1 to 4 from 10:00 PM tonight until 6:00 AM tomorrow. Please store water in advance.', 'red', 1);

-- ------------------------------------------------------------
-- Seed data: 10 sample announcements for Sto. Niño Barangay Hall, Eustaquio St.
-- ------------------------------------------------------------
INSERT INTO announcements (title, category, short_description, content, desk_name, views, is_urgent, is_trending, is_recommended, created_at) VALUES
('Scheduled Water Interruption on Sept 18', 'Public Advisory',
 'Water supply will be interrupted in Purok 1-4 due to pipeline maintenance.',
 'The Sto. Niño Barangay Hall, Eustaquio St. Water Committee, in coordination with the local water district, announces a scheduled water interruption on September 18, 2026, from 8:00 AM to 5:00 PM. Affected areas include Purok 1, 2, 3, and 4. Residents are advised to store enough water for their household needs. We apologize for the inconvenience.',
 'Barangay Engineering Desk', 482, 1, 1, 0, DATE_SUB(NOW(), INTERVAL 1 DAY)),

('Free Anti-Rabies Vaccination Drive for Pets', 'Health & Wellness',
 'Free rabies vaccination for dogs and cats this weekend at the covered court.',
 'In partnership with the City Veterinary Office, Sto. Niño Barangay Hall, Eustaquio St. will hold a free anti-rabies vaccination drive for all pet dogs and cats on September 20, 2026, 8:00 AM to 3:00 PM at the Barangay Covered Court. Please bring your pets on a leash or in a carrier, along with their previous vaccination record if available.',
 'Health & Sanitation Desk', 356, 0, 1, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),

('Job Opening: Barangay Health Worker (2 Slots)', 'Livelihood & Jobs',
 'Sto. Niño Barangay Hall, Eustaquio St. is hiring two Barangay Health Workers. Apply on or before Sept 30.',
 'The Sto. Niño Barangay Hall, Eustaquio St. is looking for two (2) Barangay Health Workers to assist in community health programs. Requirements: at least high school graduate, resident of Sto. Niño Barangay Hall, Eustaquio St. for at least 2 years, willing to undergo training. Interested applicants may submit their resume at the Barangay Health Center on or before September 30, 2026.',
 'Barangay Human Resource Desk', 601, 0, 1, 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),

('Sto. Niño Fiesta 2026: Schedule of Activities', 'Events',
 'Join us for the annual barangay fiesta celebration from October 3-5, 2026.',
 'Sto. Niño Barangay Hall, Eustaquio St. invites all residents to the annual fiesta celebration on October 3-5, 2026. Activities include a basketball league finals, talent night, food fair, and a grand parade. A detailed schedule will be posted at the barangay hall and on our official social media page. Sponsorships and donations are welcome and may be coursed through the Barangay Treasurer.',
 'Barangay Events Committee', 734, 0, 1, 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),

('New Barangay ID Application Requirements Updated', 'General Notice',
 'Updated list of requirements for applying or renewing your Barangay ID.',
 'Effective September 15, 2026, the Sto. Niño Barangay Hall, Eustaquio St. has updated the requirements for Barangay ID application and renewal: (1) Valid government-issued ID or Birth Certificate, (2) Proof of residency (utility bill or lease contract), (3) 1x1 ID photo, (4) Duly accomplished application form available at the Barangay Hall. Processing fee remains at Php 50.00.',
 'Civil Registry Desk', 267, 0, 0, 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),

('Feeding Program for Malnourished Children Resumes', 'Community Services',
 'The 120-day supplementary feeding program restarts this September for identified beneficiaries.',
 'The Barangay Nutrition Council announces the resumption of the supplementary feeding program for malnourished children ages 0-5 years old, starting September 22, 2026. The program will run for 120 days and will be held every weekday at the Day Care Center. Parents of identified beneficiaries are requested to coordinate with the Barangay Nutrition Scholar.',
 'Barangay Nutrition Council', 198, 0, 0, 0, DATE_SUB(NOW(), INTERVAL 6 DAY)),

('Road Closure: Purok 5 Access Road (Sept 19-20)', 'Public Advisory',
 'Purok 5 access road will be closed for concrete road repair over the weekend.',
 'Please be advised that the access road going to Purok 5 will be closed to all vehicular traffic on September 19-20, 2026, due to concrete road repair works by the City Engineering Office. Residents are advised to use the alternate route via Purok 6. We appreciate your understanding and patience.',
 'Barangay Engineering Desk', 412, 1, 0, 0, DATE_SUB(NOW(), INTERVAL 1 DAY)),

('Free Skills Training: Basic Computer Literacy', 'Livelihood & Jobs',
 'Free 5-day computer literacy training for out-of-school youth and unemployed residents.',
 'In partnership with TESDA, Sto. Niño Barangay Hall, Eustaquio St. will conduct a free 5-day Basic Computer Literacy Training from September 29 to October 3, 2026, at the Barangay Multi-Purpose Hall. Priority slots are given to out-of-school youth and unemployed residents. Only 30 slots available. Register at the Barangay Livelihood Desk.',
 'Barangay Livelihood Desk', 523, 0, 1, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),

('Barangay Assembly Meeting: September 25, 2026', 'General Notice',
 'All household heads are required to attend the quarterly barangay assembly.',
 'Notice is hereby given that the Quarterly Barangay Assembly will be held on September 25, 2026, 2:00 PM at the Barangay Covered Court. All household heads or their representatives are required to attend. Agenda includes budget updates, peace and order report, and open forum. Attendance will be recorded per purok.',
 'Office of the Punong Barangay', 389, 0, 0, 0, DATE_SUB(NOW(), INTERVAL 4 DAY)),

('Clean-Up Drive and Tree Planting Activity', 'Community Services',
 'Join the monthly clean-up drive and tree planting along the riverbank this Saturday.',
 'The Sto. Niño Barangay Hall, Eustaquio St. Environmental Committee invites all residents to join the monthly Clean-Up Drive and Tree Planting Activity on September 21, 2026, 6:00 AM at the riverbank area near Purok 3. Please bring your own gloves and gardening tools if available. Light snacks will be provided to all volunteers.',
 'Environmental Committee', 176, 0, 0, 0, DATE_SUB(NOW(), INTERVAL 5 DAY));

SET FOREIGN_KEY_CHECKS = 1;
