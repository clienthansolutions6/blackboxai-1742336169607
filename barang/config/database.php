<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'db_inventaris';

// Create database connection
$mysqli = new mysqli($host, $username, $password, $database);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Set charset to utf8
$mysqli->set_charset("utf8");

// Function to escape strings
function escape_string($string) {
    global $mysqli;
    return $mysqli->real_escape_string($string);
}

// Function to execute query and return result
function query($sql) {
    global $mysqli;
    return $mysqli->query($sql);
}

// Function to get single row
function fetch_assoc($result) {
    return $result->fetch_assoc();
}

// Function to get number of rows
function num_rows($result) {
    return $result->num_rows;
}

// Function to get last inserted id
function last_inserted_id() {
    global $mysqli;
    return $mysqli->insert_id;
}

// Function to close database connection
function close_connection() {
    global $mysqli;
    $mysqli->close();
}
?>