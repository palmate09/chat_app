<?php

namespace Models; 

use Core\Database; 
use Core\Response; 
use Core\RequestValidator;
use Ramsey\Uuid\Uuid;  
use PDO; 

class ChatMember {
    private $db;
    
    public function __construct(){
        $this->db = Database::getInstance();
    }
    
    // set the role admin for
    public function setRole(string $room_id, string $user_id, string $status):null{

        RequestValidator::validate([
            "room id" => $room_id, 
            "user id" => $user_id, 
            "status" => $status,
        ]); 

        try{
            $id = Uuid::uuid4()->toString(); 
            $sql = "SELECT is_group FROM chat_rooms WHERE id = ? AND created_by = ?"; 
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([$room_id, $user_id]); 
            $room_data = $stmt->fetch(PDO::FETCH_ASSOC); 

            if($room_data['is_group'] === true){
                $sql = 'INSERT INTO chat_room_memebers (id, room_id, user_id, role, joined_at) VALUES (?,?,?,"admin",now())';
                $stmt = $this->db->prepare($sql); 
                $stmt->execute([$id, $room_id, $user_id]); 
            }
            return null; 
        }
        catch(Exception $e){
            return Response::json(["status" => "error", 'message' => $e->getMessage()], 500); 
        }
    }

    // add the new member add set the role of it
    public function addMember(string $room_id, string $admin_id, string $user_id , string $role): ?string{

        RequestValidator::validate([
            "room id" => $room_id, 
            "admin id" => $admin_id, 
            "role" => $role, 
            "user id" => $user_id
        ]); 

        try{
            $id = $this->id; 

            $sql = "SELECT role FROM chat_room_members WHERE room_id = ? AND user_id = ?";
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([$room_id, $admin_id]); 
            $member = $stmt->fetch(PDO::FETCH_ASSOC); 

            if($member['role'] === 'admin' && $user_id !== $admin_id){
                $sql = "INSERT INTO chat_room_memebers(id, room_id, user_id, joined_at, role) VALUES(?, ?, ?, now(), ?)"; 
                $stmt = $this->db->prepare($sql); 
                $stmt->exeucte([$id, $room_id, $user_id, $role]); 
                return $id; 
            }

            return null; 
        }
        catch(Exception $e){
            return Response::json([
                "status" => "error", 
                "message" => $e->getMessage()
            ], 500); 
        }
    }

    // remove the member from the particular group
    public function removeMember(string $id, string $room_id, string $user_id): null{
        RequestValidator::validate([
            "members id" => $id, 
            "room id" => $room_id, 
            "user id" => $user_id 
        ]);

        try{
            // remove the memeber from the group
            $sql = "DELETE FROM chat_room_members WHERE id = ? AND room_id = ? AND user_id = ?"; 
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([$id, $room_id, $user_id]); 

            return null; 
        }
        catch(Exception $e){
            return Response::json(["status" => "error", "message" => $e->getMessage()], 500); 
        }
    }

    // show all memeber of the particular group
    public function showAllMemeber(string $id, string $room_id): ?array{

        RequestValidator::validate([
            "members id" => $id, 
            "room id" => $room_id 
        ]);
        
        try{

            $sql = "SELECT
                        u.id AS user_id, name, 
                        FROM chat_room_memebers c
                        LEFT JOIN users u ON c.user_id = u.id
                        WHERE id = ? AND room_id =  ? 
                        ";
                        
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([$id, $room_id]); 
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC); 

            return $users; 
        }
        catch(Exception $e){
            return Response::json(["status" => "error", "message" => $e->getMessage()], 500);
        }
    }
}