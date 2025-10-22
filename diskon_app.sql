-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versi server:                 8.0.30 - MySQL Community Server - GPL
-- OS Server:                    Win64
-- HeidiSQL Versi:               12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Membuang struktur basisdata untuk diskon_app
DROP DATABASE IF EXISTS `diskon_app`;
CREATE DATABASE IF NOT EXISTS `diskon_app` /*!40100 DEFAULT CHARACTER SET armscii8 COLLATE armscii8_bin */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `diskon_app`;

-- membuang struktur untuk table diskon_app.detail_barang
DROP TABLE IF EXISTS `detail_barang`;
CREATE TABLE IF NOT EXISTS `detail_barang` (
  `id` int NOT NULL AUTO_INCREMENT,
  `transaksi_id` int NOT NULL,
  `nama_barang` varchar(100) COLLATE armscii8_bin NOT NULL,
  `harga_satuan` decimal(15,2) NOT NULL,
  `jumlah` int NOT NULL,
  `harga_total` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `transaksi_id` (`transaksi_id`),
  CONSTRAINT `detail_barang_ibfk_1` FOREIGN KEY (`transaksi_id`) REFERENCES `transaksi` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=armscii8 COLLATE=armscii8_bin;

-- Membuang data untuk tabel diskon_app.detail_barang: ~8 rows (lebih kurang)
INSERT INTO `detail_barang` (`id`, `transaksi_id`, `nama_barang`, `harga_satuan`, `jumlah`, `harga_total`) VALUES
	(13, 7, 'aa', 15000.00, 10, 150000.00),
	(14, 7, 'bb', 32500.00, 25, 812500.00),
	(15, 8, 'aadfad', 15000.00, 54, 810000.00),
	(16, 8, 'asdfasd', 3500.00, 44, 154000.00),
	(17, 8, 'asdfasdf', 27500.00, 22, 605000.00),
	(19, 10, 'teuing ah', 1245000.00, 1, 1245000.00),
	(20, 11, 'asdfasdf', 1500000.00, 1, 1500000.00),
	(21, 11, 'asdfasdfadsfsdafsadf', 2450000.00, 1, 2450000.00);

-- membuang struktur untuk table diskon_app.transaksi
DROP TABLE IF EXISTS `transaksi`;
CREATE TABLE IF NOT EXISTS `transaksi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_transaksi` varchar(20) COLLATE armscii8_bin NOT NULL,
  `tanggal` datetime NOT NULL,
  `total_harga` decimal(15,2) NOT NULL,
  `jenis_diskon` enum('persen','nominal') COLLATE armscii8_bin NOT NULL,
  `diskon_persen` decimal(5,2) DEFAULT '0.00',
  `diskon_nominal` decimal(15,2) DEFAULT '0.00',
  `diskon_member` decimal(5,2) DEFAULT '0.00',
  `total_diskon` decimal(15,2) NOT NULL,
  `harga_akhir` decimal(15,2) NOT NULL,
  `jumlah_bayar` decimal(15,2) DEFAULT '0.00',
  `kembalian` decimal(15,2) DEFAULT '0.00',
  `tipe_pelanggan` varchar(20) COLLATE armscii8_bin NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=armscii8 COLLATE=armscii8_bin;

-- Membuang data untuk tabel diskon_app.transaksi: ~4 rows (lebih kurang)
INSERT INTO `transaksi` (`id`, `kode_transaksi`, `tanggal`, `total_harga`, `jenis_diskon`, `diskon_persen`, `diskon_nominal`, `diskon_member`, `total_diskon`, `harga_akhir`, `jumlah_bayar`, `kembalian`, `tipe_pelanggan`, `created_at`) VALUES
	(7, 'TRX20251022051823', '2025-10-22 05:18:23', 962500.00, 'persen', 0.00, 0.00, 0.00, 0.00, 962500.00, 1000000.00, 37500.00, 'reguler', '2025-10-22 05:18:23'),
	(8, 'TRX20251022052822', '2025-10-22 05:28:22', 1569000.00, 'persen', 0.00, 0.00, 0.00, 0.00, 1569000.00, 1600000.00, 31000.00, 'reguler', '2025-10-22 05:28:22'),
	(10, 'TRX20251022054020', '2025-10-22 05:40:20', 1245000.00, 'persen', 10.00, 0.00, 5.00, 186750.00, 1058250.00, 1100000.00, 41750.00, 'silver', '2025-10-22 05:40:20'),
	(11, 'TRX20251022054249', '2025-10-22 05:42:49', 3950000.00, 'nominal', 0.00, 450000.00, 0.00, 450000.00, 3500000.00, 3500000.00, 0.00, 'reguler', '2025-10-22 05:42:49');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
