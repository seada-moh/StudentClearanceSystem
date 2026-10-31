DROP DATABASE IF EXISTS dtuscms;
CREATE DATABASE dtuscms;
USE dtuscms;

CREATE TABLE `admins` (`username` varchar(50) NOT NULL PRIMARY KEY, `password` varchar(255) NOT NULL, `full_name` varchar(100) NOT NULL, `email` varchar(100) NOT NULL, `sex` enum('M','F') NOT NULL DEFAULT 'M', `photo` varchar(255) NOT NULL DEFAULT 'default.png');
CREATE TABLE `students` (`student_id` varchar(50) NOT NULL PRIMARY KEY, `password` varchar(255) NOT NULL, `sname` varchar(50) NOT NULL, `fathername` varchar(50) NOT NULL, `gfathername` varchar(50) NOT NULL, `email` varchar(100) NOT NULL, `photo` varchar(255) NOT NULL, `sex` enum('M','F') NOT NULL, `academic_year` varchar(20) NOT NULL, `department` varchar(100) NOT NULL, `year_of_study` int(11) NOT NULL, `semester` int(11) NOT NULL, `section` varchar(10) NOT NULL, `block_no` int(11) NOT NULL, `dorm_no` int(11) NOT NULL);
CREATE TABLE `dorm_mates` (`mate_id` varchar(50) NOT NULL PRIMARY KEY, `password` varchar(255) NOT NULL, `fname` varchar(50) NOT NULL, `lname` varchar(50) NOT NULL, `email` varchar(100) NOT NULL, `sex` enum('M','F') NOT NULL, `department` varchar(100) NOT NULL, `block_no` int(11) NOT NULL, `dorm_no` int(11) NOT NULL, `photo` varchar(255) NOT NULL);
CREATE TABLE `proctors` (`proctor_id` varchar(50) NOT NULL PRIMARY KEY, `username` varchar(50) NOT NULL UNIQUE, `password` varchar(255) NOT NULL, `fname` varchar(50) NOT NULL, `lname` varchar(50) NOT NULL, `sex` enum('M','F') NOT NULL, `email` varchar(100) NOT NULL, `block_no` int(11) NOT NULL, `photo` varchar(255) NOT NULL);
CREATE TABLE `librarians` (`librarian_id` varchar(50) NOT NULL PRIMARY KEY, `username` varchar(50) NOT NULL UNIQUE, `password` varchar(255) NOT NULL, `fname` varchar(50) NOT NULL, `lname` varchar(50) NOT NULL, `sex` enum('M','F') NOT NULL, `email` varchar(100) NOT NULL, `photo` varchar(255) NOT NULL);
CREATE TABLE `cafeterias` (`cafeteria_id` varchar(50) NOT NULL PRIMARY KEY, `username` varchar(50) NOT NULL UNIQUE, `password` varchar(255) NOT NULL, `fname` varchar(50) NOT NULL, `lname` varchar(50) NOT NULL, `sex` enum('M','F') NOT NULL, `email` varchar(100) NOT NULL, `photo` varchar(255) NOT NULL);
CREATE TABLE `student_services` (`student_service_id` varchar(50) NOT NULL PRIMARY KEY, `username` varchar(50) NOT NULL UNIQUE, `password` varchar(255) NOT NULL, `fname` varchar(50) NOT NULL, `lname` varchar(50) NOT NULL, `sex` enum('M','F') NOT NULL, `email` varchar(100) NOT NULL, `photo` varchar(255) NOT NULL);
CREATE TABLE `registrars` (`registrar_id` varchar(50) NOT NULL PRIMARY KEY, `username` varchar(50) NOT NULL UNIQUE, `password` varchar(255) NOT NULL, `fname` varchar(50) NOT NULL, `lname` varchar(50) NOT NULL, `sex` enum('M','F') NOT NULL, `email` varchar(100) NOT NULL, `photo` varchar(255) NOT NULL);
CREATE TABLE `dept_heads` (`dept_head_id` varchar(50) NOT NULL PRIMARY KEY, `username` varchar(50) NOT NULL UNIQUE, `password` varchar(255) NOT NULL, `fname` varchar(50) NOT NULL, `lname` varchar(50) NOT NULL, `sex` enum('M','F') NOT NULL, `faculty` varchar(100) NOT NULL, `department` varchar(100) NOT NULL, `email` varchar(100) NOT NULL, `photo` varchar(255) NOT NULL);

CREATE TABLE `clearance_requests` (
  `student_id` varchar(50) NOT NULL PRIMARY KEY, `reason` varchar(100) NOT NULL, `personal_items` text NOT NULL,
  `dormmate_status` enum('pending','approved','rejected') NOT NULL, `dormmate_date` datetime NULL,
  `proctor_status` enum('pending','approved','rejected') NOT NULL, `proctor_date` datetime NULL,
  `librarian_status` enum('pending','approved','rejected') NOT NULL, `librarian_date` datetime NULL,
  `cafeteria_status` enum('pending','approved','rejected') NOT NULL, `cafeteria_date` datetime NULL,
  `student_service_status` enum('pending','approved','rejected') NOT NULL, `student_service_date` datetime NULL,
  `dept_head_status` enum('pending','approved','rejected') NOT NULL, `dept_head_date` datetime NULL,
  `registrar_status` enum('pending','approved','rejected') NOT NULL, `registrar_date` datetime NULL,
  `request_date` datetime NOT NULL, `rejection_reason` text NOT NULL,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`student_id`) ON DELETE CASCADE
);

CREATE TABLE `activity_logs` (`log_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `action` enum('added', 'deleted', 'updated') NOT NULL, `role` varchar(50) NOT NULL, `log_date` timestamp DEFAULT CURRENT_TIMESTAMP);

INSERT INTO `admins` (`username`, `password`, `full_name`, `email`, `sex`) VALUES ('DTUadmin', '$2y$10$wT8K./Q9yqJ2NfKx8Ym.Y.xZfO2Ie3GgYhO5H7Z/N3.Z6t.8.X3yG', 'Clearance Administrator', 'dtuadmin@gmail.com', 'M');