<?php
// Include the database connection script
require_once 'databaseConnection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo json_encode(['error' => 'Authentication required']);
        exit();
    } else {
        header('Location: login.php');
        exit();
    }
}

class ChuckNorrisAPI {
    private $baseUrl = 'https://api.chucknorris.io/';
    private $conn;
    private $userId;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->userId = $_SESSION['user_id'];
    }
    
    // Fetch all categories from the API
    public function getCategories() {
        $url = $this->baseUrl . 'jokes/categories';
        $response = $this->makeRequest($url);
        
        if ($response) {
            // Save categories to database
            $this->saveCategories($response);
            return $response;
        }
        return false;
    }
    
    // Fetch a random joke from a specific category
    public function getRandomJoke($category) {
        $url = $this->baseUrl . 'jokes/random?category=' . urlencode($category);
        $response = $this->makeRequest($url);
        
        if ($response) {
            // Save joke to database with user ID
            $this->saveJoke($response, $category);
            return $response;
        }
        return false;
    }
    
    // Make HTTP request to the API
    private function makeRequest($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            return json_decode($response, true);
        }
        return false;
    }
    
    // Save categories to database
    private function saveCategories($categories) {
        foreach ($categories as $category) {
            $stmt = $this->conn->prepare("INSERT IGNORE INTO categories (category_name) VALUES (?)");
            $stmt->bind_param("s", $category);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    // Save joke to database with user ID
    private function saveJoke($jokeData, $category) {
        // Get category ID
        $stmt = $this->conn->prepare("SELECT id FROM categories WHERE category_name = ?");
        $stmt->bind_param("s", $category);
        $stmt->execute();
        $result = $stmt->get_result();
        $categoryRow = $result->fetch_assoc();
        $stmt->close();
        
        if ($categoryRow) {
            $categoryId = $categoryRow['id'];
            $jokeText = $jokeData['value'];
            $apiId = $jokeData['id'];
            
            // Check if joke already exists for this user
            $stmt = $this->conn->prepare("SELECT id FROM jokes WHERE api_id = ? AND user_id = ?");
            $stmt->bind_param("si", $apiId, $this->userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                // Insert new joke with user ID
                $stmt = $this->conn->prepare("INSERT INTO jokes (joke_text, category_id, api_id, user_id) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sisi", $jokeText, $categoryId, $apiId, $this->userId);
                $stmt->execute();
            }
            $stmt->close();
        }
    }
    
    // Get stored categories from database
    public function getStoredCategories() {
        $sql = "SELECT * FROM categories ORDER BY category_name";
        $result = $this->conn->query($sql);
        
        $categories = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        return $categories;
    }
    
    // Get stored jokes from database for current user
    public function getStoredJokes($limit = 10) {
        $sql = "SELECT j.*, c.category_name 
                FROM jokes j 
                JOIN categories c ON j.category_id = c.id 
                WHERE j.user_id = ?
                ORDER BY j.created_at DESC 
                LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $this->userId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $jokes = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $jokes[] = $row;
            }
        }
        $stmt->close();
        return $jokes;
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $api = new ChuckNorrisAPI($conn);
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'get_categories':
                $categories = $api->getCategories();
                echo json_encode($categories);
                break;
                
            case 'get_random_joke':
                if (isset($_POST['category'])) {
                    $joke = $api->getRandomJoke($_POST['category']);
                    echo json_encode($joke);
                } else {
                    echo json_encode(['error' => 'Category not provided']);
                }
                break;
                
            case 'get_stored_jokes':
                $jokes = $api->getStoredJokes();
                echo json_encode($jokes);
                break;
        }
    }
}
?>
