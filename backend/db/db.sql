create table users(
    id          varchar(50) PRIMARY KEY, 
    username    varchar(50) NOT NULL,
    password    varchar(255) NOT NULL,
    email       varchar(100) NOT NULL  UNIQUE, 
    name        varchar(100) DEFAULT NULL, 
    avatar VARCHAR(255) DEFAULT NULL, 
    status ENUM('online','offline') DEFAULT 'offline',
    last_seen DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
); 

create table password_resets(
    id     INT     AUTO_INCREMENT PRIMARY KEY,
    email  varchar(255) NOT NULL,
    token  varchar(64) NOT NULL, 
    expire_at   DATETIME NOT NULL, 
    created_at  TIMESTAMP DEFAULT   CURRENT_TIMESTAMP,  
);

create table contacts (
    id varchar(50) PRIMARY KEY,
    user_id varchar(50) NOT NULL,
    contact_id varchar(50) NOT NULL,
    status ENUM('pending','accepted','blocked') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_contact (user_id, contact_id)
);

create table chat_rooms (
    id varchar(50) PRIMARY KEY,
    name VARCHAR(100) DEFAULT NULL,
    is_group BOOLEAN DEFAULT FALSE,
    created_by varchar(50) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

create table chat_room_members (
    id varchar(50) PRIMARY KEY,
    room_id varchar(50) NOT NULL,
    user_id varchar(50) NOT NULL,
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_member (room_id, user_id)
);


CREATE TABLE messages (
    id VARCHAR(50) PRIMARY KEY,
    room_id varchar(50) NOT NULL,
    sender_id varchar(50) NOT NULL,
    content TEXT,                 -- text message
    media_url VARCHAR(255),       -- image/file path
    media_type ENUM('image','video','file','none') DEFAULT 'none',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
);


create table message_status (
    id varchar(50) PRIMARY KEY,
    message_id varchar(50) NOT NULL,
    user_id varchar(50) NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    read_at DATETIME DEFAULT NULL,
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_message_user (message_id, user_id)
);










