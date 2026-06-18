<?php
namespace Core\Services;
use Core\Models\User;
use Core\Services\DatabaseAdapter;

class AuthService {
    private $userModel;
    private $db;
    private $supabaseClient;

    /**
     * Constructor - accepts existing connection to prevent connection thrashing
     * @param mixed $supabaseClient Existing Supabase client instance (reused connection)
     * @param DatabaseAdapter|null $dbAdapter Optional existing DatabaseAdapter instance
     */
    public function __construct($supabaseClient = null, $dbAdapter = null) {
        // Reuse provided connection instead of creating new one
        $this->supabaseClient = $supabaseClient;
        $this->db = $dbAdapter ?: DatabaseAdapter::create();
        $this->userModel = new User($supabaseClient);
    }

    public function authenticateUser($username, $password, $role) {
        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'All fields required.'];
        }

        try {
            if ($this->db->getDbType() === 'supabase') {
                $client = $this->db->getConnection();
                $directResult = $client->from('tbl_users')
                    ->select('*')
                    ->eq('username', $username)
                    ->execute();

                if (is_object($directResult) && property_exists($directResult, 'data')) {
                    $data = $directResult->data;
                    $user = !empty($data) ? $data[0] : null;
                } else {
                    $user = null;
                }
            } else {
                $query = "SELECT id_user, username, full_name, email, role, password, status FROM tbl_users WHERE username = ? LIMIT 1";
                $result = $this->db->query($query, [$username]);
                $user = $this->db->fetchAssoc($result);
            }

            if (!$user) {
                return ['success' => false, 'message' => 'User ID not found.'];
            }

            if ($user['role'] !== $role) {
                return ['success' => false, 'message' => 'Wrong role.'];
            }

            $status = strtolower($user['status'] ?? '');
            if (!in_array($status, ['active', 'aktif'])) {
                return ['success' => false, 'message' => 'Account inactive.'];
            }

            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'message' => 'Incorrect password.'];
            }

            unset($user['password']);
            return ['success' => true, 'user_data' => $user];
        } catch (\Exception $e) {
            error_log("Authentication error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Authentication system error.'];
        }
    }

    public function createSecureSession($userData) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => $userData['id_user'],
            'username' => $userData['username'],
            'role' => $userData['role'],
            'full_name' => $userData['full_name'] ?? $userData['username'],
            'name' => $userData['full_name'] ?? $userData['username']
        ];

        $_SESSION['last_activity'] = time();
    }

    public function validateSession() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user']['id'])) {
            return ['valid' => false];
        }

        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            session_destroy();
            return ['valid' => false];
        }

        $_SESSION['last_activity'] = time();
        return ['valid' => true];
    }

    public function destroySession() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION = [];
        session_destroy();
    }
}
