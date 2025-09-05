<?php
namespace Models; 

use Core\Database; 
use Core\Response; 
use Core\RequestValidator;
use Ramsey\Uuid\Uuid;  
use PDO; 

class Contact{

    private $db; 
    public function __construct(){
        $this->db = Database::getInstance(); 
    }

    public function request(string $contact_id, string $user_id): null{
        
        if(!$contact_id || $contact_id === $user_id){
            Response::json(["status" => "error", 'message' => "Invalid Contact id"], 404); 
        }

        try{

            $id = Uuid::uuid4()->toString(); 

            $sql = "SELECT status FROM contacts WHERE user_id = :user_id AND contact_id = :contact_id"; 
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([
                ":user_id" => $user_id, 
                ":contact_id" => $contact_id
            ]); 
            $existing = $stmt->fetch(PDO::FETCH_ASSOC); 

            if($existing){
                switch($existing['status']){
                    case 'pending':
                        Response::json(["status" => "error", "message" => "Follow request already sent"], 409);
                        break; 
                    case 'accepted': 
                        Response::json(["status" => "error", "message" => "You already follow this user"], 409);
                        break; 
                    case 'blocked': 
                        Response::json(['status' => 'error', 'message' => 'User blocked']); 
                        break; 
                }
                exit; 
            }

            // Insert new follow request;
            $sql = "INSERT INTO contacts(id, user_id, contact_id, status) VALUES(?,?,?,'pending')"; 
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([$id, $user_id, $contact_id]);
            
            return null; 

        }
        catch(Exception $e){
            Response::json(["status"=>"error","message"=>$e->getMessage()]);
        }
    }

    public function add(string $contact_id, string $user_id): ?array{

        RequestValidator::validate([
            "contact id" => $contact_id, 
            "user id" => $user_id
        ]);
        
        try{
            $sql = 'SELECT status FROM contacts WHERE contact_id = ? AND user_id = ?'; 
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([$contact_id, $user_id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC); 

            if($data['status'] === 'pending'){
                $status = 'accepted'; 
                $sql = 'UPDATE contacts SET status = ? WHERE contact_id = ? and user_id = ?'; 
                $stmt = $this->db->prepare($sql); 
                $stmt->execute([$status, $contact_id, $user_id]); 
            }

            $sql = 'SELECT status FROM contacts WHERE contact_id = ? AND user_id = ?'; 
            $stmt = $this->db->prepare($sql); 
            $stmt->exectue([$contact_id, $user_id]); 
            $data = $stmt->fetch(PDO::FETCH_ASSOC); 
            
            return $data; 
        }
        catch(Exception $e){
            Response::json(["status" => "error", "message" => $e->getMessage()]); 
        }
    }

    public function update(string $contact_id, string $user_id): ?array{

        RequestValidator::validate([
            "contact id" => $contact_id, 
            "user id" => $user_id
        ]); 

        try{
            $sql = 'SELECT status FROM contacts WHERE contact_id = ? AND user_id = ?'; 
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([$contact_id, $user_id]); 
            $data = $stmt->fetch(PDO::FETCH_ASSOC); 

            if($data['status'] === 'blocked'){
                $status = 'accepted'; 
                $sql = 'UPDATE contacts SET status = ? WHERE contact_id = ? AND user_id = ?';
                $stmt = $this->db->prepare($sql); 
                $stmt->execute([$status, $contact_id, $user_id]); 
            } 

            $sql = 'SELECT status FROM contacts WHERE contact_id=? AND user_id=?'; 
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([$contact_id, $user_id]); 
            $data = $stmt->fetch(PDO::FETCH_ASSOC); 

            return $data; 
        }
        catch(Exception $e){
            Response::json(["status" => "error", "message" => $e->getMessage()]); 
        }
    }

    // show all the contacts related to the user_id
    public function show(string $user_id): ?array{

        RequestValidator::validate([
            "user id" => $user_id
        ]); 
        
        try{
            $sql = "SELECT
                        c.id AS  contact_id, c.username
                        FROM contacts c 
                        LEFT JOIN users u ON u.id = c.id
                        WHERE user_id = ? ";

            $stmt = $this->db->prepare($sql); 
            $stmt->execute([$user_id]); 
            $user = $stmt->fetch(PDO::FETCH_ASSOC); 

            return $user;
        }
        catch(Exception $e){
            Response::json(["status" => "error", "message" => $e->getMessage()], 500); 
        }
    }

    // show particular conatct detail of the user_id and contact_id
    public function show_particular_contact(string $user_id , string $contact_id): ?array{
        RequestValidator::validate([
            "user id" => $user_id, 
            "contact id" => $contact_id
        ]); 

        try{

            $sql = "SELECT * FROM contacts WHERE user_id = ? AND contact_id = ?"; 
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([$user_id, $contact_id]); 
            $user = $stmt->fetch(PDO::FETCH_ASSOC); 

            return $user; 
        }
        catch(Exception $e){
            Response::json(["status" => "error", "message" => $e->getMessage()]); 
        }
    } 

    public function removeStatus(string $user_id, string $contact_id): null{

        RequestValidator::validate([
            "user id" => $user_id, 
            "contact id" => $contact_id
        ]);

        try{
            $sql = "SELECT status FROM contacts WHERE user_id = ? AND contact_id = ?"; 
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([$status, $user_id, $contact_id]); 
            $data = $stmt->fetch(PDO::FETCH_ASSOC); 

            if($data['status'] === 'accepted'){
                $status = 'pending';
                $sql = "UPDATE contacts SET status = ? WHERE user_id = ? AND contact_id = ?";
                $stmt = $this->db->prepare($sql); 
                $stmt->execute([$status, $user_id, $contact_id]);  
            }

            return null; 
        }

        catch(Exception $e){
            Response::json(["status" => "error", "message" => $e->getMessage()]); 
        }

    }

    public function removeContact(string $user_id, string $contact_id):null{
        
        RequestValidator::validate([
            "user id" => $user_id, 
            "contact id" => $contact_id
        ]);

        try{

            $sql = "DELETE FROM contacts WHERE user_id = ? AND contact_id = ?"; 
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([$user_id, $contact_id]); 

            return null; 
        }
        catch(Exception $e){
            Response::json(["status" => "error", "message" => $e->getMessage()], 500); 
        }
    }
}
