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
('Member 1 Name', 'Student ID 1', 'Web Server Configuration'),
('Member 2 Name', 'Student ID 2', 'Database Server Configuration'),
('Member 3 Name', 'Student ID 3', 'Load Balancer Testing');

INSERT INTO announcements (title, body) VALUES
('Database Connection Successful', 'This content is loaded from the MySQL database server.'),
('Load Balancer Demo', 'Refresh the page to see whether Instance 1 or Instance 2 serves the request.');
