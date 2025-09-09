<?php

namespace Models; 

use Core\Database; 
use Core\Response; 
use Core\RequestValidator;
use Ramsey\Uuid\Uuid;
use PDO; 


class ChatRoom {
    private $db; 
    
    public function __construct(){
        $this->db = Database::getInstance(); 
    }

    public function create(string $user_id, bool $is_group, ?string $name=null, ?string $contact_id=null): ?array{
        $id = Uuid::uuid4()->toString(); 

        RequestValidator::validate([
            "user id" => $user_id
        ]);

        try{
            $isGroup = $is_group ? 1:0; 
            
            if($is_group === false){
                RequestValidator::validate([
                    "contact id" => $contact_id
                ]); 

                // first get the information of the member;
                $sql2 = 'SELECT 
                            u.id AS contact_id, 
                            u.name,
                            c.status
                            FROM contacts c
                            LEFT JOIN users u ON u.id = c.contact_id 
                            WHERE c.user_id = ? 
                            AND c.contact_id = ?'; 

                $stmt2 = $this->db->prepare($sql2);
                $stmt2->execute([$user_id, $contact_id]); 
                $user_name = $stmt2->fetch(PDO::FETCH_ASSOC);                

                if($user_name && $user_name['status'] === 'accepted'){
                    // create the new room
                    $id2 = $user_id; 
                    $sql3 = "INSERT INTO chat_rooms(id, name, is_group, created_by) VALUES(?,?,?,?)";
                    $stmt3 = $this->db->prepare($sql3); 
                    $stmt3->execute([$id2, $user_name['name'], $isGroup, $user_id]);  
                    return ["id" => $id2]; 
                }                
            }
            else{
                // create chat room
                $sql = 'INSERT INTO chat_rooms(id, name, is_group, created_by) VALUES(?,?,?,?)';
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$id, $name, $isGroup, $user_id]);

                // assign the group creator as admin role
                $new_id = Uuid::uuid4()->toString(); 
                $sql4 = 'INSERT INTO chat_room_members (id, room_id, user_id, role, joined_at) VALUES (?,?,?,"admin",now())';
                $stmt4 = $this->db->prepare($sql4); 
                $stmt4->execute([$new_id, $id, $user_id]);
            }   
            return [
                "id" => $id,
                "new_id" => $new_id
            ]; 
        }
        catch(\Exception $e){
            Response::json(["status" => "error", "message" => $e->getMessage()], 500); 
        }
    }

    // show all the rooms of particular user
    public function show_user_rooms(string $user_id): ?array{

        RequestValidator::validate([
            "user id" => $user_id
        ]); 

        try{
            $sql = 'SELECT id, name FROM chat_rooms WHERE created_by = ?';
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([$user_id]); 
            $roomsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $roomsData; 
        }
        catch(\Exception $e){
            return Response::json(["status"=>"error", "message" => $e->getMessage()], 500);
        }
    }

    // show all the group chats of particular user
    public function show_group_rooms(string $user_id): ?array{

        RequestValidator::validate([
            "user id" => $user_id
        ]); 

        try{

            $sql = "SELECT is_group FROM chat_rooms WHERE created_by = ?"; 
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);  

            if($user['is_group'] === 1){
                $sql2 = "SELECT 
                            c.id AS group_id, 
                            c.name
                            FROM chat_rooms c
                            WHERE c.created_by = ?
                            AND c.is_group = 1
                            ";
                $stmt2 = $this->db->prepare($sql2); 
                $stmt2->execute([$user_id]); 
                $user2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

                return $user2; 
            }
            return null; 
        }
        catch(\Exception $e){
            return Response::json(["status" => "error", "message" => $e->getMessage()], 500);
        }
    }


    // show the room data of particular user
    public function show_room(string $id, string $user_id): ?array{
        RequestValidator::validate([
            "room id" => $id, 
            "user id" => $user_id
        ]); 

        try{
            $sql = "SELECT * FROM chat_rooms WHERE id = ? AND created_by = ?"; 
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([$id, $user_id]);
            $room_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $room_data; 
        }
        catch(\Exception $e){
            Response::json(["status" => "error", "message" => $e->getMessage()], 500); 
        } 
    }
    
    // update the room data of particular user
    public function update(string $id,string $name): ?array{
        RequestValidator::validate([
            "room id" => $id, 
            "name" => $name
        ]); 

        try{
            $sql = "UPDATE chat_rooms SET name = ? WHERE id = ?"; 
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([$name, $id]);

            // show the updated room data; 
            $sql = "SELECT * FROM chat_rooms WHERE id = ?"; 
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([$id]); 
            $room_data = $stmt->fetchAll(PDO::FETCH_ASSOC); 

            return $room_data; 
        }
        catch(\Exception $e){
            return Response::json(["status" => "error", "message" => $e->getMessage()], 500); 
        }
    }

    // delete complete room data 
    public function delete(string $id): null{
        RequestValidator::validate([
            "id" => $id
        ]); 

        try{
            $sql = "DELETE FROM chat_rooms WHERE id = ?"; 
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([$id]);
            return null;  
        }
        catch(\Exception $e){
            return Response::json(["status" => "error", "message" => $e->getMessage()], 500); 
        }
    }
}