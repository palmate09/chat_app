<?php

namespace Models; 

use Core\Database; 
use Core\RequestValidator; 
use Core\Response; 
use Ramsey\Uuid\Uuid;
use PDO; 

class Message{
    private $db; 

    public function __construct(){
        $this->db = Database::getInstance(); 
    }

    public function sendMessage(string $room_id, string $user_id, string $content): ?array{
        
        RequestValidator::validate([
            "room id" => $room_id, 
            "user id" => $user_id, 
            "content" => $content
        ]); 

        try{
            $id = Uuid::uuid4()->toString(); 
            $sql = "INSERT INTO messages(id,room_id, sender_id, content) VALUES (?,?,?,?)"; 
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([$id, $room_id, $user_id, $content]);
            $msg_id = $id;
            
            // create message_status for all members
            $sql = "SELECT user_id FROM chat_room_members WHERE room_id =?"; 
            $stmt2 = $this->db->prepare($sql); 
            $stmt2->execute([$room_id]); 
            $members = $stmt2->fetchAll(PDO::FETCH_COLUMN);
            
            $new_id = Uuid::uuid4()->toString(); 
            $stmt3 = $this->db->prepare("INSERT INTO message_status(id, message_id, user_id) VALUES(?,?,?)"); 
            foreach($members as $uid){
                $stmt3->execute([$new_id, $msg_id, $uid]); 
            }

            return [
                "message_id" => $msg_id, 
                "message_status_id" => $new_id 
            ]; 
        }
        catch(Exception $e){
            return Response::json(["status" => "error", "message" => $e->getMessage()], 500); 
        }
    }

    public function showMessage(string $room_id): ?array{

        RequestValidator::validate([
            "room id" => $room_id
        ]); 

        try{
            $sql = "SELECT m.*, u.username
                    FROM messages m
                    JOIN users u ON u.id = m.sender_id
                    WHERE room_id = ?
                    ORDER_BY m.created_at ASC";

            $stmt = $this->db->prepare($sql); 
            $stmt->execute([$room_id]);
            $message = $stmt->fetchAll(PDO::FETCH_ASSOC); 

            return $message; 
        }
        catch(Exception $e){
            return Response::json(["status" => "error", "message" => $e->getMessage()], 500); 
        }
    }

    public function updateMessage(string $id, string $user_id, string $content): ?array{
        RequestValidator::validate([
            "message id" => $id,
            "content" => $content, 
            "user id" => $user_id
        ]); 

        try{
            // update the messages content
            $sql = "UPDATE messages SET content = ? WHERE id = ? AND user_id = ?";
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([$content, $id, $user_id]);
            
            // show the updated message
            $sql1 = "SELECT content, sender_id FROM messages WHERE id = ?";
            $stmt2 = $this->db->prepare($sql1);
            $stmt2->execute([$id]);
            $updated_message = $stmt2->fetch(PDO::FETCH_ASSOC);

            return $updated_message; 
        }
        catch(Exception $e){
            return Response::json(["status" => "error", "message" => $e->getMessage()], 500); 
        }
    }

    public function removeMessage(string $id): null{
        RequestValidator::validate([
            "message id" => $id
        ]); 

        try{
            $sql = 'DELETE FROM messages WHERE id = ?'; 
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([$id]);
            
            return null; 
        }
        catch(Exception $e){
            return Response::json(["status" => "error", "message" => $e->getMessage()]); 
        }
    }
}
