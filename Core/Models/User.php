<?php
namespace Core\Models;

class User {
    private $supabase;
    
    public function __construct($supabaseClient) {
        $this->supabase = $supabaseClient;
    }
    
    public function findByUsername($username) {
        $result = $this->supabase
            ->from('tbl_users')
            ->select('id_user, username, password, role, status, full_name')
            ->eq('username', $username)
            ->limit(1)
            ->execute();
        
        return !empty($result->data) ? $result->data[0] : null;
    }
    
    public function verifyPassword($plain, $hash) {
        return password_verify($plain, $hash);
    }
    
    public function isActive($user) {
        $status = strtolower($user['status'] ?? '');
        return in_array($status, ['active', 'aktif']);
    }
}
