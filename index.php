<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

require 'vendor/autoload.php';
require_once '../config.php';

$db = new db();

$app = new \Slim\App;

$app->post('/event', function($request, $response, $args) use($db) {
    try {
        $conn = $db->connect();
        $data = $request->getParsedBody();
        if(!isset($data['name']) || !isset($data['category']) || !isset($data['date']) || !isset($data['time']) || !isset($data['location'])) {
            throw new Exception("Data is required.");
        }
        $name = $data['name'];
        $category = $data['category'];
        $date = $data['date'];
        $time = $data['time'];
        $location = $data['location'];
        $description = $data['description'];
        $sql = "INSERT INTO events (name, category, date, time, location, description)
        VALUES (:name, :category, :date, :time, :location, :description)";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':category', $category);
        $stmt->bindValue(':date', $date);
        $stmt->bindValue(':time', $time);
        $stmt->bindValue(':location', $location);
        $stmt->bindValue(':description', $description);
        $stmt->execute();
        $eventId = $conn->lastInsertId();

        return $response->withJson([
            "message" => "Event created successfully",
            "eventId" => $eventId
        ], 201);
    } catch (Exception $e) {
        return $response->withJson(["error" => "Error creating user: " . $e->getMessage()]);
    }
});

$app->post('/ticket', function($request, $response, $args) use($db) {
    try {
        $conn = $db->connect();
        $data = $request->getParsedBody();
        if(!isset($data['name']) || !isset($data['quantity']) || !isset($data['price']) || !isset($data['startDate']) || !isset($data['startTime']) || !isset($data['endDate']) || !isset($data['endTime'])) {
            throw new Exception("Data is required.");
        }
        $name = $data['name'];
        $quantity = $data['quantity'];
        $price = $data['price'];
        $description = $data['description'];
        $startDate = $data['startDate'];
        $startTime = $data['startTime'];
        $endDate = $data['endDate'];
        $endTime = $data['endTime'];
        $eventId = $data['eventId'];
        $sql = "INSERT INTO tickets (name, quantity, price, description, startDate, startTime, endDate, endTime, eventId)
        VALUES (:name, :quantity, :price, :description, :startDate, :startTime, :endDate, :endTime, :eventId)";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':quantity', $quantity);
        $stmt->bindValue(':price', $price);
        $stmt->bindValue(':description', $description);
        $stmt->bindValue(':startDate', $startDate);
        $stmt->bindValue(':startTime', $startTime);
        $stmt->bindValue(':endDate', $endDate);
        $stmt->bindValue(':endTime', $endTime);
        $stmt->bindValue(':eventId', $eventId);
        
        $stmt->execute();
        $ticketId = $conn->lastInsertId();

        return $response->withJson([
            "message" => "Ticket created successfully",
            "ticketId" => $ticketId
        ], 201);
    } catch (Exception $e) {
        return $response->withJson(["error" => "Error creating user: " . $e->getMessage()]);
    }
});

$app->run();


