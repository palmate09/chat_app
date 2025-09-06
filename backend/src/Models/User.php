<?php
namespace Models; 

use Core\Database;
use Core\Auth; 
use Core\Response; 
use Core\RequestValidator; 
use Ramsey\Uuid\Uuid; 
use PDO; 
 

class User {
    private $db; 
    public function __construct(){
        $this->db = Database::getInstance();
    }

    public function register(string $username, string $password, string $name, string $email,): string {
        
        try{
            $id = Uuid::uuid4()->toString(); 
            $password_hash = password_hash($password, PASSWORD_BCRYPT); 

            $stmt = $this->db->prepare('INSERT INTO users(id, username, email, password, name) VALUES(?,?,?,?,?)');
            $stmt->execute([$id, $username, $email, $password_hash, $name]);

            return $id; 
        }
        catch(\Exception $e){
            Response::json(["status" => "error", "message" => $e->getMessage()], 500); 
        }

        return $id; 
    }

    public function login(string $identifier, string $password): ?array {

        try{
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
        }
        catch(\Exception $e){
            Response::json(["status"=>"error", "message" => $e->getMessage()], 500); 
        }
        
        return null; 
    }

    public function showProfile(string $userId): ?array{

        try{ 
            $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);  

            if(empty($user_data)){
                Response::json(["status" => "error", "message" => "profile not found"], 400);
            }

            return $user_data; 

        }
        catch(\Exception $e){
            Response::json(["status" => "error", "message" => $e->getMessage()], 500); 
        }
    }

    public function updateProfile(string $userId, array $input, array $allowed): ?array{

        try{
            // update the profile
            $fields = array_intersect_key($input, array_flip($allowed));
            RequestValidator::validate($fields);

            if(!empty($fields['password'])){
                $fields['password'] = password_hash($fields['password'], PASSWORD_BCRYPT);  
            }
            
            $set = []; 
            foreach($fields as $key => $value){
                $set[] = "$key = :$key";
            } 


            $sql = "UPDATE users SET " . implode(", ", $set). " WHERE id = :id"; 
            $stmt = $this->db->prepare($sql); 

            $fields["id"] = $userId; 
            $stmt->execute($fields);

            // get the updated profile 
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id"); 
            $stmt->execute([":id" => $userId]); 
            $update_user = $stmt->fetch(PDO::FETCH_ASSOC); 

            return $update_user; 
            
        }
        catch(\Exception $e){
            Response::json(["status" => "error", "message" => $e->getMessage()], 500); 
        }
    }


    public function deleteUser(string $userId): null{
        try{
            //delete the user
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id"); 
            $stmt->execute([":id" => $userId]);  

            return null; 
        }
        catch(\Exception $e){
            Response::json(["status" => "error", "message" => $e->getMessage()], 500); 
        }
    }

    public function updateStatus(string $user_id, string $status): null{

        RequestValidator::validate([
            "status" => $status
        ]); 

        try{
            // update the status of the user online: offline
            $stmt = $this->db->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$status, $user_id]);
            return null; 
        }
        catch(\Exception $e){
            return Response::json(["status" => "error", "message" => $e->getMessage()], 500); 
        }
    }
}
 