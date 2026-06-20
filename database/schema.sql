CREATE DATABASE IF NOT EXISTS cloud_project;
USE cloud_project;

CREATE TABLE IF NOT EXISTS group_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_name VARCHAR(100) NOT NULL,
  student_id VARCHAR(30) NOT NULL,
  role_name VARCHAR(60) NOT NULL
);

CREATE TABLE IF NOT EXISTS announcements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(120) NOT NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO group_members (student_name, student_id, role_name) VALUES
('Maurithania Joleesha Maria Tjakra', '102022340119', 'Chief Meteorologist'),
('Thiflan Hakim Alfarizzy', '102022340401', 'Atmospheric Analyst'),
('Gusti Muhammad Malvin Athallahsyah', '102022340306', 'Radar Engineer'),
('Christhofer Risaldy Kobong', '102022340376', 'Field Observer');

INSERT INTO announcements (title, body) VALUES
('Database Connection Successful', 'This content is loaded from the MySQL database server.'),
('Load Balancer Demo', 'Refresh the page to see whether Instance 1 or Instance 2 serves the request.');

-- Sessions table for shared session storage across load-balanced instances
CREATE TABLE IF NOT EXISTS sessions (
  id VARCHAR(128) PRIMARY KEY,
  data TEXT NOT NULL,
  last_accessed INT UNSIGNED NOT NULL,
  INDEX idx_last_accessed (last_accessed)
);
