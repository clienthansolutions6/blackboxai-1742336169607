-- Create database
CREATE DATABASE IF NOT EXISTS db_inventaris;
USE db_inventaris;

-- Users table
CREATE TABLE users (
    id_user INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'kepala_gudang', 'mahasiswa', 'dosen', 'staff') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Locations table
CREATE TABLE lokasi (
    id_lokasi INT PRIMARY KEY AUTO_INCREMENT,
    nama_lokasi VARCHAR(100) NOT NULL,
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE jenis_barang (
    id_jenis INT PRIMARY KEY AUTO_INCREMENT,
    nama_jenis VARCHAR(50) NOT NULL,
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Units table
CREATE TABLE satuan (
    id_satuan INT PRIMARY KEY AUTO_INCREMENT,
    nama_satuan VARCHAR(50) NOT NULL,
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Items table
CREATE TABLE barang (
    id_barang INT PRIMARY KEY AUTO_INCREMENT,
    kode_barang VARCHAR(20) NOT NULL UNIQUE,
    nama_barang VARCHAR(100) NOT NULL,
    id_jenis INT,
    id_satuan INT,
    jenis_item ENUM('tetap', 'habis_pakai') NOT NULL,
    stok INT DEFAULT 0,
    minimal_stok INT DEFAULT 0,
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_jenis) REFERENCES jenis_barang(id_jenis),
    FOREIGN KEY (id_satuan) REFERENCES satuan(id_satuan)
);

-- Item locations
CREATE TABLE barang_lokasi (
    id_barang_lokasi INT PRIMARY KEY AUTO_INCREMENT,
    id_barang INT,
    id_lokasi INT,
    jumlah INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_barang) REFERENCES barang(id_barang),
    FOREIGN KEY (id_lokasi) REFERENCES lokasi(id_lokasi)
);

-- Incoming items
CREATE TABLE barang_masuk (
    id_barang_masuk INT PRIMARY KEY AUTO_INCREMENT,
    id_barang INT,
    jumlah INT NOT NULL,
    tanggal_masuk DATE NOT NULL,
    keterangan TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_barang) REFERENCES barang(id_barang),
    FOREIGN KEY (created_by) REFERENCES users(id_user)
);

-- Outgoing items (for consumables)
CREATE TABLE barang_keluar (
    id_barang_keluar INT PRIMARY KEY AUTO_INCREMENT,
    id_barang INT,
    jumlah INT NOT NULL,
    tanggal_keluar DATE NOT NULL,
    keterangan TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_barang) REFERENCES barang(id_barang),
    FOREIGN KEY (created_by) REFERENCES users(id_user)
);

-- Item requests (for consumables)
CREATE TABLE permintaan_barang (
    id_permintaan INT PRIMARY KEY AUTO_INCREMENT,
    id_user INT,
    id_barang INT,
    jumlah INT NOT NULL,
    tanggal_permintaan DATE NOT NULL,
    status ENUM('pending', 'disetujui', 'ditolak') DEFAULT 'pending',
    keterangan TEXT,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id_user),
    FOREIGN KEY (id_barang) REFERENCES barang(id_barang),
    FOREIGN KEY (approved_by) REFERENCES users(id_user)
);

-- Borrowing (for fixed assets)
CREATE TABLE peminjaman (
    id_peminjaman INT PRIMARY KEY AUTO_INCREMENT,
    id_user INT,
    id_barang INT,
    jumlah INT NOT NULL,
    tanggal_pinjam DATE NOT NULL,
    tanggal_kembali DATE,
    lokasi_peminjaman ENUM('dalam_kampus', 'luar_kampus') NOT NULL,
    status ENUM('pending', 'dipinjam', 'dikembalikan', 'ditolak') DEFAULT 'pending',
    keterangan TEXT,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id_user),
    FOREIGN KEY (id_barang) REFERENCES barang(id_barang),
    FOREIGN KEY (approved_by) REFERENCES users(id_user)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, nama_lengkap, role) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin');

-- Insert some default units
INSERT INTO satuan (nama_satuan) VALUES 
('Unit'),
('Pcs'),
('Set'),
('Pack'),
('Box');

-- Insert some default categories
INSERT INTO jenis_barang (nama_jenis) VALUES 
('Elektronik'),
('Furniture'),
('Alat Tulis'),
('Peralatan Lab'),
('Perlengkapan Kantor');

-- Insert some default locations
INSERT INTO lokasi (nama_lokasi) VALUES 
('Gudang Utama'),
('Laboratorium Komputer'),
('Ruang Kelas'),
('Perpustakaan'),
('Ruang Administrasi');