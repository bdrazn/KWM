<?php
session_start();

class Auth {
    private $db;

    public function __construct($db) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->db = $db;
    }

    public function register($email, $password, $user_type) {
        try {
            // Check if email already exists
            $stmt = $this->db->getConnection()->prepare('SELECT id FROM users WHERE email = ?');
            if (!$stmt) {
                error_log("Prepare failed: " . $this->db->getConnection()->lastErrorMsg());
                return ['success' => false, 'message' => 'Database error occurred'];
            }
            
            $stmt->bindValue(1, $email, SQLITE3_TEXT);
            $result = $stmt->execute();
            
            if (!$result) {
                error_log("Execute failed: " . $this->db->getConnection()->lastErrorMsg());
                return ['success' => false, 'message' => 'Database error occurred'];
            }

            if ($result->fetchArray()) {
                return ['success' => false, 'message' => 'Email already registered'];
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $this->db->getConnection()->prepare('INSERT INTO users (email, password, user_type) VALUES (?, ?, ?)');
            if (!$stmt) {
                error_log("Prepare failed: " . $this->db->getConnection()->lastErrorMsg());
                return ['success' => false, 'message' => 'Database error occurred'];
            }

            $stmt->bindValue(1, $email, SQLITE3_TEXT);
            $stmt->bindValue(2, $hashed_password, SQLITE3_TEXT);
            $stmt->bindValue(3, $user_type, SQLITE3_TEXT);
            
            $result = $stmt->execute();
            
            if (!$result) {
                error_log("Insert failed: " . $this->db->getConnection()->lastErrorMsg());
                return ['success' => false, 'message' => 'Registration failed'];
            }

            // If user is a vendor, create vendor profile
            if ($user_type === 'vendor') {
                $user_id = $this->db->getConnection()->lastInsertRowID();
                $stmt = $this->db->getConnection()->prepare('INSERT INTO vendor_profiles (user_id) VALUES (?)');
                if ($stmt) {
                    $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
                    $stmt->execute();
                }
            }

            return ['success' => true, 'message' => 'Registration successful'];
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred during registration'];
        }
    }

    public function login($email, $password) {
        try {
            $stmt = $this->db->getConnection()->prepare('SELECT * FROM users WHERE email = ?');
            if (!$stmt) {
                error_log("Prepare failed: " . $this->db->getConnection()->lastErrorMsg());
                return ['success' => false, 'message' => 'Database error occurred'];
            }

            $stmt->bindValue(1, $email, SQLITE3_TEXT);
            $result = $stmt->execute();
            
            if (!$result) {
                error_log("Execute failed: " . $this->db->getConnection()->lastErrorMsg());
                return ['success' => false, 'message' => 'Database error occurred'];
            }

            $user = $result->fetchArray(SQLITE3_ASSOC);

            if (!$user) {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['email'] = $user['email'];
                return ['success' => true, 'message' => 'Login successful', 'user_type' => $user['user_type']];
            }
            
            return ['success' => false, 'message' => 'Invalid email or password'];
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred during login'];
        }
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $stmt = $this->db->getConnection()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->bindValue(1, $_SESSION['user_id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        return $result ? $result->fetchArray(SQLITE3_ASSOC) : null;
    }

    public function addService($title, $description, $category, $price) {
        if (!$this->isLoggedIn() || $_SESSION['user_type'] !== 'vendor') {
            return ['success' => false, 'message' => 'Unauthorized access'];
        }

        try {
            $stmt = $this->db->getConnection()->prepare('
                INSERT INTO services (vendor_id, title, description, category, price) 
                VALUES (?, ?, ?, ?, ?)
            ');
            
            $stmt->bindValue(1, $_SESSION['user_id'], SQLITE3_INTEGER);
            $stmt->bindValue(2, $title, SQLITE3_TEXT);
            $stmt->bindValue(3, $description, SQLITE3_TEXT);
            $stmt->bindValue(4, $category, SQLITE3_TEXT);
            $stmt->bindValue(5, $price, SQLITE3_FLOAT);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Service added successfully'];
            }
            return ['success' => false, 'message' => 'Failed to add service'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'An error occurred while adding the service'];
        }
    }

    public function getVendorServices() {
        if (!$this->isLoggedIn() || $_SESSION['user_type'] !== 'vendor') {
            return ['success' => false, 'message' => 'Unauthorized access'];
        }

        try {
            $stmt = $this->db->getConnection()->prepare('
                SELECT * FROM services WHERE vendor_id = ? ORDER BY created_at DESC
            ');
            $stmt->bindValue(1, $_SESSION['user_id'], SQLITE3_INTEGER);
            $result = $stmt->execute();
            
            $services = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $services[] = $row;
            }
            
            return ['success' => true, 'services' => $services];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to fetch services'];
        }
    }
}
?>