<?php
session_start(); // Start session

// Database credentials
$servername = "db";
$username = "root";
$password = "root";
$dbname = "chuck_norris_jokes";

// Create connection (including database)
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    echo "Something went wrong.";
    exit();
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    // Select the database
    $conn->select_db($dbname);

    // Create users table
    $sql0 = "CREATE TABLE IF NOT EXISTS users (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    // Create categories table
    $sql1 = "CREATE TABLE IF NOT EXISTS categories (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(100) UNIQUE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    // Create jokes table with user_id foreign key
    $sql2 = "CREATE TABLE IF NOT EXISTS jokes (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        joke_text TEXT NOT NULL,
        category_id INT(11),
        api_id VARCHAR(255),
        user_id INT(11),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";

    // Create favorites table for user-joke relationships
    $sql3 = "CREATE TABLE IF NOT EXISTS favorites (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        joke_id INT(11) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_joke (user_id, joke_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (joke_id) REFERENCES jokes(id) ON DELETE CASCADE
    )";

    // Execute table creation
    if ($conn->query($sql0) === TRUE && $conn->query($sql1) === TRUE && $conn->query($sql2) === TRUE && $conn->query($sql3) === TRUE) {
        //echo "Successfully created.";
    } else {
        //echo "Something went wrong.";
    }
} else {
    //echo "Something went wrong.";
}
