<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

require 'vendor/autoload.php';
require_once 'config.php';

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

// GET /forum - Get all forum threads
$app->get('/forum', function($request, $response, $args) use($db) {
    try {
        $conn = $db->connect();
        $sql = "SELECT * FROM forum_threads ORDER BY created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $threads = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get replies for each thread
        foreach ($threads as &$thread) {
            $replySql = "SELECT * FROM forum_replies WHERE thread_id = :thread_id ORDER BY created_at ASC";
            $replyStmt = $conn->prepare($replySql);
            $replyStmt->bindValue(':thread_id', $thread['id']);
            $replyStmt->execute();
            $replies = $replyStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format the response to match 
            $thread = [
                'id' => (int)$thread['id'],
                'title' => $thread['title'],
                'mainPost' => [
                    'author' => $thread['author'],
                    'content' => $thread['content'],
                    'date' => $thread['created_at'],
                    'likes' => (int)$thread['likes'],
                    'isOrganizer' => (bool)$thread['is_organizer']
                ],
                'replies' => array_map(function($reply) {
                    return [
                        'author' => $reply['author'],
                        'content' => $reply['content'],
                        'date' => $reply['created_at'],
                        'likes' => (int)$reply['likes'],
                        'isOrganizer' => (bool)$reply['is_organizer']
                    ];
                }, $replies)
            ];
        }
        
        return $response->withJson($threads, 200);
    } catch (Exception $e) {
        return $response->withJson(["error" => "Error fetching forum threads: " . $e->getMessage()], 500);
    }
});

// POST /forum - Create a new forum thread
$app->post('/forum', function($request, $response, $args) use($db) {
    try {
        $conn = $db->connect();
        $data = $request->getParsedBody();
        
        if(!isset($data['title']) || !isset($data['mainPost'])) {
            throw new Exception("Title and mainPost are required.");
        }
        
        $title = $data['title'];
        $mainPost = $data['mainPost'];
        
        if(!isset($mainPost['author']) || !isset($mainPost['content'])) {
            throw new Exception("Author and content are required in mainPost.");
        }
        
        $author = $mainPost['author'];
        $content = $mainPost['content'];
        $likes = isset($mainPost['likes']) ? $mainPost['likes'] : 0;
        $isOrganizer = isset($mainPost['isOrganizer']) ? $mainPost['isOrganizer'] : false;
        
        $sql = "INSERT INTO forum_threads (title, author, content, likes, is_organizer, created_at)
                VALUES (:title, :author, :content, :likes, :is_organizer, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':title', $title);
        $stmt->bindValue(':author', $author);
        $stmt->bindValue(':content', $content);
        $stmt->bindValue(':likes', $likes);
        $stmt->bindValue(':is_organizer', $isOrganizer);
        $stmt->execute();
        
        $threadId = $conn->lastInsertId();
        
        return $response->withJson([
            "message" => "Forum thread created successfully",
            "threadId" => $threadId
        ], 201);
    } catch (Exception $e) {
        return $response->withJson(["error" => "Error creating forum thread: " . $e->getMessage()], 500);
    }
});

// POST /forum/{id}/reply - Add a reply to a forum thread
$app->post('/forum/{id}/reply', function($request, $response, $args) use($db) {
    try {
        $conn = $db->connect();
        $threadId = $args['id'];
        $data = $request->getParsedBody();
        
        if(!isset($data['author']) || !isset($data['content'])) {
            throw new Exception("Author and content are required.");
        }
        
        $author = $data['author'];
        $content = $data['content'];
        $likes = isset($data['likes']) ? $data['likes'] : 0;
        $isOrganizer = isset($data['isOrganizer']) ? $data['isOrganizer'] : false;
        
        // Check if thread exists
        $checkSql = "SELECT id FROM forum_threads WHERE id = :thread_id";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bindValue(':thread_id', $threadId);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            throw new Exception("Thread not found.");
        }
        
        $sql = "INSERT INTO forum_replies (thread_id, author, content, likes, is_organizer, created_at)
                VALUES (:thread_id, :author, :content, :likes, :is_organizer, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':thread_id', $threadId);
        $stmt->bindValue(':author', $author);
        $stmt->bindValue(':content', $content);
        $stmt->bindValue(':likes', $likes);
        $stmt->bindValue(':is_organizer', $isOrganizer);
        $stmt->execute();
        
        $replyId = $conn->lastInsertId();
        
        return $response->withJson([
            "message" => "Reply added successfully",
            "replyId" => $replyId
        ], 201);
    } catch (Exception $e) {
        return $response->withJson(["error" => "Error adding reply: " . $e->getMessage()], 500);
    }
});

// DELETE /forum/{id} - Delete a forum thread
$app->delete('/forum/{id}', function($request, $response, $args) use($db) {
    try {
        $conn = $db->connect();
        $threadId = $args['id'];
        
        $deleteRepliesSql = "DELETE FROM forum_replies WHERE thread_id = :thread_id";
        $deleteRepliesStmt = $conn->prepare($deleteRepliesSql);
        $deleteRepliesStmt->bindValue(':thread_id', $threadId);
        $deleteRepliesStmt->execute();
        
        $deleteThreadSql = "DELETE FROM forum_threads WHERE id = :thread_id";
        $deleteThreadStmt = $conn->prepare($deleteThreadSql);
        $deleteThreadStmt->bindValue(':thread_id', $threadId);
        $deleteThreadStmt->execute();
        
        if ($deleteThreadStmt->rowCount() === 0) {
            throw new Exception("Thread not found.");
        }
        
        return $response->withJson([
            "message" => "Forum thread deleted successfully"
        ], 200);
    } catch (Exception $e) {
        return $response->withJson(["error" => "Error deleting forum thread: " . $e->getMessage()], 500);
    }
});

// DELETE /forum/{threadId}/reply/{replyId} - Delete a specific reply
$app->delete('/forum/{threadId}/reply/{replyId}', function($request, $response, $args) use($db) {
    try {
        $conn = $db->connect();
        $threadId = $args['threadId'];
        $replyId = $args['replyId'];
        
        $sql = "DELETE FROM forum_replies WHERE id = :reply_id AND thread_id = :thread_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':reply_id', $replyId);
        $stmt->bindValue(':thread_id', $threadId);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("Reply not found.");
        }
        
        return $response->withJson([
            "message" => "Reply deleted successfully"
        ], 200);
    } catch (Exception $e) {
        return $response->withJson(["error" => "Error deleting reply: " . $e->getMessage()], 500);
    }
});

$app->run();


