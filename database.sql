-- =============================================
-- HOSTEL MANAGEMENT SYSTEM - DATABASE SCHEMA
-- MySQL (XAMPP)
-- =============================================

CREATE DATABASE IF NOT EXISTS hostel_management;
USE hostel_management;

-- Users table for authentication
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'student') NOT NULL DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE hostel (
    hostel_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    address VARCHAR(255),
    total_rooms INT
);

CREATE TABLE room (
    room_id INT PRIMARY KEY AUTO_INCREMENT,
    hostel_id INT,
    room_number VARCHAR(10),
    room_type VARCHAR(50),
    capacity INT,
    status VARCHAR(20) DEFAULT 'available',
    FOREIGN KEY (hostel_id) REFERENCES hostel(hostel_id)
);

CREATE TABLE student (
    student_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    name VARCHAR(100),
    roll_no VARCHAR(50) UNIQUE,
    department VARCHAR(100),
    year INT,
    phone VARCHAR(15),
    email VARCHAR(100),
    room_id INT,
    FOREIGN KEY (room_id) REFERENCES room(room_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE complaint (
    complaint_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    category VARCHAR(100),
    description TEXT,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES student(student_id)
);

CREATE TABLE mess (
    mess_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    hostel_id INT,
    FOREIGN KEY (hostel_id) REFERENCES hostel(hostel_id)
);

CREATE TABLE mess_menu (
    menu_id INT PRIMARY KEY AUTO_INCREMENT,
    mess_id INT,
    day VARCHAR(20),
    breakfast TEXT,
    lunch TEXT,
    dinner TEXT,
    FOREIGN KEY (mess_id) REFERENCES mess(mess_id)
);

CREATE TABLE dashboard_stats (
    stat_id INT PRIMARY KEY AUTO_INCREMENT,
    total_students INT DEFAULT 0,
    total_rooms INT DEFAULT 0,
    occupied_rooms INT DEFAULT 0,
    pending_complaints INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- SEED DATA
-- =============================================

-- Admin user (password: admin123)
INSERT INTO users (username, password, role) VALUES
('admin', '$2y$10$uUValIFetOl3/5d9p2Z9kuX695fN75htm4ZIB/jpc15kJd.vgvl9G', 'admin');

-- Hostels
INSERT INTO hostel (name, address, total_rooms) VALUES
('Ganga Hostel', 'Block A, Campus Road', 50),
('Yamuna Hostel', 'Block B, Campus Road', 40);

-- Rooms
INSERT INTO room (hostel_id, room_number, room_type, capacity, status) VALUES
(1, '101', 'Single', 1, 'available'),
(1, '102', 'Double', 2, 'occupied'),
(1, '103', 'Triple', 3, 'available'),
(1, '104', 'Double', 2, 'occupied'),
(1, '105', 'Single', 1, 'maintenance'),
(2, '201', 'Double', 2, 'available'),
(2, '202', 'Triple', 3, 'occupied'),
(2, '203', 'Single', 1, 'available');

-- Students (password: student123)
INSERT INTO users (username, password, role) VALUES
('2021CS001', '$2y$10$JTOo1eu1sr7EygOWvYsaJuKkn0WGRW8MTHr6tDsUoWxtgZkaeWzma', 'student'),
('2021CS002', '$2y$10$JTOo1eu1sr7EygOWvYsaJuKkn0WGRW8MTHr6tDsUoWxtgZkaeWzma', 'student');

INSERT INTO student (user_id, name, roll_no, department, year, phone, email, room_id) VALUES
(2, 'Rahul Sharma', '2021CS001', 'Computer Science', 3, '9876543210', 'rahul@student.edu', 2),
(3, 'Priya Singh', '2021CS002', 'Computer Science', 2, '9876543211', 'priya@student.edu', 4);

-- Mess
INSERT INTO mess (name, hostel_id) VALUES
('Ganga Mess', 1),
('Yamuna Mess', 2);

-- Mess Menu
INSERT INTO mess_menu (mess_id, day, breakfast, lunch, dinner) VALUES
(1, 'Monday', 'Poha, Tea, Banana', 'Dal, Rice, Sabzi, Roti, Salad', 'Paneer Curry, Rice, Roti, Curd'),
(1, 'Tuesday', 'Idli, Sambar, Chutney', 'Rajma, Rice, Roti, Salad', 'Aloo Curry, Dal, Roti'),
(1, 'Wednesday', 'Paratha, Curd, Pickle', 'Chole, Rice, Roti, Salad', 'Mix Veg, Dal, Rice, Roti'),
(1, 'Thursday', 'Upma, Tea, Fruit', 'Dal Makhani, Rice, Roti, Salad', 'Kadai Paneer, Rice, Roti'),
(1, 'Friday', 'Bread, Butter, Egg', 'Palak Dal, Rice, Roti, Salad', 'Dum Aloo, Roti, Rice, Raita'),
(1, 'Saturday', 'Puri, Sabzi, Tea', 'Special Biryani, Raita, Salad', 'Dal Fry, Roti, Rice, Kheer'),
(1, 'Sunday', 'Dosa, Chutney, Sambar', 'Special Lunch, Salad, Sweet', 'Special Dinner, Dessert'),
(2, 'Monday', 'Paratha, Curd', 'Dal, Rice, Sabzi, Roti', 'Paneer, Rice, Roti'),
(2, 'Tuesday', 'Idli, Chutney', 'Rajma, Rice, Roti', 'Aloo Curry, Dal, Roti'),
(2, 'Wednesday', 'Poha, Tea', 'Chole, Rice, Roti', 'Mix Veg, Dal, Roti'),
(2, 'Thursday', 'Bread, Egg, Juice', 'Dal Makhani, Rice, Roti', 'Kadai Paneer, Rice'),
(2, 'Friday', 'Upma, Tea', 'Palak Dal, Rice, Roti', 'Dum Aloo, Roti, Rice'),
(2, 'Saturday', 'Puri, Sabzi', 'Biryani, Raita', 'Dal Fry, Roti, Kheer'),
(2, 'Sunday', 'Dosa, Sambar', 'Special Lunch, Sweet', 'Special Dinner, Dessert');

-- Complaints
INSERT INTO complaint (student_id, category, description, status) VALUES
(1, 'Maintenance', 'Water tap is leaking in room 102', 'pending'),
(1, 'Electricity', 'Light bulb fused in corridor', 'resolved'),
(2, 'Mess', 'Food quality needs improvement', 'pending');

-- Dashboard Stats
INSERT INTO dashboard_stats (total_students, total_rooms, occupied_rooms, pending_complaints) VALUES
(2, 8, 3, 2);
