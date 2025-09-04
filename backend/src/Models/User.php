<?php
namespace Models; 

use Core\Database;
use Core\Auth; 
use Ramsey\Uuid\Uuid; 
use PDO; 
 

class User {
    private $db; 
    private function __construct(){
        $this->db = Database::getInstance();
    }

    public function register(string $username, string $password, string $name, string $email,): string {
        $id = Uuid::uuid4()->toString(); 
        $password_hash = password_hash($password, PASSWORD_BCRYPT); 

        $stmt = $this->db->prepare('INSERT INTO users(id, username, email, password, name) VALUES(?,?,?,?,?)');
        $stmt->execute([$id, $username, $email, $password, $name]); 

        return $id; 
    }

    public function login(string $identifier, string $password): ?array {

        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = :identifier OR email = :identifier');
        $stmt->execute([
            ":identifier" => $identifier
        ]); 

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $payload = [
            'iss' => 'http://localhost:8080', 
            'iat' => time(), 
            'exp' => time() + (60 * 60 * 60), 
            'id' => $user['id'],
            'username' => $user['username'] 
        ]; 

        $token = Auth::generateToken($payload); 

        if($user && password_verify($password, $user['password'])){
            return [
                "user" => $user, 
                "token" => $token
            ]; 
        }
        
        return null; 
    }

    public function profile($token){

        // $user_id = Auth::verifyToken($token, ) 

    }
}
 