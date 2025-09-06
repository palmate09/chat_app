<?php

namespace Models; 

use Core\Database; 
use Core\Response; 
use Core\RequestValidator;
use Ramsey\Uuid\Uuid;
use PDO; 


class ChatRoom {
    private $db; 
    private $id; 
    
    public function __construct(){
        $this->db = Database::getInstance(); 
        $this->id = Uuid::uuid4()->toString(); 
    }

    public function create(string $user_id, $name, $is_group): null{
        $id = $this->id; 

        RequestValidator::validate([
            "user id" => $user_id,
            "name" => $name, 
            "is group" => $is_group 
        ]);

        try{
            $sql = 'INSERT INTO chat_rooms(id, name, is_group, created_by) VALUES(?,?,?,?)';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id, $name, $is_group, $user_id]); 

            return null; 
        }
        catch(Exception $e){
            return Response::json(["status" => "error", "message" => $e->getMessage()], 500); 
        }

    }

    // show all the chat room data of all the users
    public function show(string $id): ?array{
        RequestValidator::validate([
            "room id" => $id
        ]); 

        try{
            $sql = "SELECT * FROM chat_rooms WHERE id = ?"; 
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([$id]); 
            $room_data = $stmt->fetch(PDO::FETCH_ASSOC); 

            return $room_data; 
        }
        catch(Exception $e){
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
            $sql = "SELECT * FROM chat_rooms WHERE id = ? AND user_id = ?"; 
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([$id, $user_id]);
            $room_data = $stmt->fetch(PDO::FETCH_ASSOC); 

            return $room_data; 
        }
        catch(Exception $e){
            return Response::json(["status" => "error", "message" => $e->getMessage()], 500); 
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
            $room_data = $stmt->fetch(PDO::FETCH_ASSOC); 

            return $room_data; 
        }
        catch(Exception $e){
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
        catch(Exception $e){
            return Response::json(["status" => "error", "message" => $e->getMessage()], 500); 
        }
    }
}