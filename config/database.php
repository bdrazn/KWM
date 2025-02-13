<?php
class Database {
    private $conn;

    public function __construct() {
        $dbFile = __DIR__ . '/../database/database.sqlite';
        $isNewDb = !file_exists($dbFile);
        
        try {
            // Create database directory with proper permissions
            $dbDir = dirname($dbFile);
            if (!file_exists($dbDir)) {
                if (!mkdir($dbDir, 0777, true)) {
                    throw new Exception("Failed to create database directory: $dbDir");
                }
                chmod($dbDir, 0777);
            }
            
            // Create and set permissions for database file
            if (!file_exists($dbFile)) {
                if (!touch($dbFile)) {
                    throw new Exception("Failed to create database file: $dbFile");
                }
                chmod($dbFile, 0666);
            }
            
            // Connect to database
            $this->conn = new SQLite3($dbFile);
            $this->conn->enableExceptions(true);
            
            // Set proper permissions for new database
            chmod($dbFile, 0666);
            
            // Enable foreign keys
            $this->conn->exec('PRAGMA foreign_keys = ON');
            
            if ($isNewDb) {
                $this->createTables();
            }
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }

    private function createTables() {
        // Users table
        $this->conn->exec('
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                user_type TEXT CHECK(user_type IN ("student", "vendor")) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');

        // Vendor profiles
        $this->conn->exec('
            CREATE TABLE IF NOT EXISTS vendor_profiles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER UNIQUE,
                name TEXT,
                description TEXT,
                skills TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ');

        // Services
        $this->conn->exec('
            CREATE TABLE IF NOT EXISTS services (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                vendor_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                description TEXT NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                duration INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (vendor_id) REFERENCES users(id)
            )
        ');

        // Bookings
        $this->conn->exec('
            CREATE TABLE IF NOT EXISTS bookings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                service_id INTEGER NOT NULL,
                student_id INTEGER NOT NULL,
                booking_date DATE NOT NULL,
                booking_time TIME NOT NULL,
                status TEXT CHECK(status IN ("pending", "confirmed", "completed", "cancelled")) DEFAULT "pending",
                price DECIMAL(10,2) NOT NULL,
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (service_id) REFERENCES services(id),
                FOREIGN KEY (student_id) REFERENCES users(id)
            )
        ');

        // Notifications
        $this->conn->exec('
            CREATE TABLE IF NOT EXISTS notifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                message TEXT NOT NULL,
                type TEXT NOT NULL,
                seen INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ');
        
        // Messages
        $this->conn->exec('
            CREATE TABLE IF NOT EXISTS messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                sender_id INTEGER NOT NULL,
                receiver_id INTEGER NOT NULL,
                message TEXT NOT NULL,
                seen INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (sender_id) REFERENCES users(id),
                FOREIGN KEY (receiver_id) REFERENCES users(id)
            )
        ');

        // Bookings updated_at trigger
        $this->conn->exec('
            CREATE TRIGGER IF NOT EXISTS update_bookings_timestamp 
            AFTER UPDATE ON bookings
            BEGIN
                UPDATE bookings SET updated_at = CURRENT_TIMESTAMP
                WHERE id = NEW.id;
            END
        ');
    }

    public function getConnection() {
        return $this->conn;
    }
}
?>