-- Database schema for donor management app

CREATE DATABASE IF NOT EXISTS donor_management;
USE donor_management;

CREATE TABLE IF NOT EXISTS donatur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(255) NOT NULL,
    alamat TEXT,
    no_hp VARCHAR(20)
);

CREATE TABLE IF NOT EXISTS level (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_level VARCHAR(50) NOT NULL UNIQUE
);

INSERT IGNORE INTO level (id, nama_level) VALUES
(1, 'superadmin'),
(2, 'operator');

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    level INT NOT NULL,
    keterangan TEXT,
    FOREIGN KEY (level) REFERENCES level(id)
);

CREATE TABLE IF NOT EXISTS donasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_donatur INT NOT NULL,
    tanggal DATE NOT NULL,
    waktu TIME NOT NULL,
    sumbangan DECIMAL(15,2) NOT NULL,
    id_user INT NOT NULL,
    FOREIGN KEY (id_donatur) REFERENCES donatur(id),
    FOREIGN KEY (id_user) REFERENCES users(id)
);
