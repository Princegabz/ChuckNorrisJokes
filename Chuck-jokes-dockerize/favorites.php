<?php
require_once 'databaseConnection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle AJAX requests for favorite operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $response = ['success' => false, 'message' => ''];
    
    switch ($_POST['action']) {
        case 'add_favorite':
            if (isset($_POST['joke_id'])) {
                $jokeId = (int)$_POST['joke_id'];
                $userId = $_SESSION['user_id'];
                
                // Check if joke belongs to user
                $stmt = $conn->prepare("SELECT id FROM jokes WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ii", $jokeId, $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // Add to favorites
                    $stmt = $conn->prepare("INSERT IGNORE INTO favorites (user_id, joke_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $userId, $jokeId);
                    
                    if ($stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'Added to favorites!';
                    } else {
                        $response['message'] = 'Error adding to favorites.';
                    }
                } else {
                    $response['message'] = 'Joke not found or access denied.';
                }
                $stmt->close();
            }
            break;
            
        case 'remove_favorite':
            if (isset($_POST['joke_id'])) {
                $jokeId = (int)$_POST['joke_id'];
                $userId = $_SESSION['user_id'];
                
                $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND joke_id = ?");
                $stmt->bind_param("ii", $userId, $jokeId);
                
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Removed from favorites!';
                } else {
                    $response['message'] = 'Error removing from favorites.';
                }
                $stmt->close();
            }
            break;
            
        case 'check_favorite':
            if (isset($_POST['joke_id'])) {
                $jokeId = (int)$_POST['joke_id'];
                $userId = $_SESSION['user_id'];
                
                $stmt = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND joke_id = ?");
                $stmt->bind_param("ii", $userId, $jokeId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $response['success'] = true;
                $response['is_favorite'] = $result->num_rows > 0;
                $stmt->close();
            }
            break;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Get favorite jokes for display
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 5;
$offset = ($page - 1) * $perPage;

// Get total count of favorite jokes
$countSql = "SELECT COUNT(*) as total FROM favorites f 
             JOIN jokes j ON f.joke_id = j.id 
             JOIN categories c ON j.category_id = c.id 
             WHERE f.user_id = ?";
$stmt = $conn->prepare($countSql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$totalResult = $stmt->get_result();
$totalFavorites = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalFavorites / $perPage);
$stmt->close();

// Get favorite jokes with pagination
$sql = "SELECT j.*, c.category_name, f.created_at as favorited_at 
        FROM favorites f 
        JOIN jokes j ON f.joke_id = j.id 
        JOIN categories c ON j.category_id = c.id 
        WHERE f.user_id = ? 
        ORDER BY f.created_at DESC 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $_SESSION['user_id'], $perPage, $offset);
$stmt->execute();
$result = $stmt->get_result();

$favoriteJokes = [];
while ($row = $result->fetch_assoc()) {
    $favoriteJokes[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favorite Jokes - Chuck Norris Jokes</title>
    <link rel="stylesheet" href="CSS/style.css">
    <style>
        .back-btn {
            background: #009688;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: #00796B;
            transform: translateY(-1px);
        }

        .stats {
            background: #E0F2F1;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }

        .pagination a,
        .pagination span {
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            background: #009688;
            color: white;
            border-color: #009688;
        }

        .pagination .current {
            background: #009688;
            color: white;
            border-color: #009688;
        }

        .no-favorites {
            text-align: center;
            padding: 50px;
            color: #666;
            font-style: italic;
        }

        .favorite-btn {
            background: #ff6b6b;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            margin-left: 10px;
        }

        .favorite-btn:hover {
            background: #ff5252;
            transform: translateY(-1px);
        }

        .favorite-btn.favorited {
            background: #ff4757;
        }

        .favorite-btn.favorited:hover {
            background: #ff3742;
        }

        .favorited-date {
            font-size: 0.8rem;
            color: #ff6b6b;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="user-nav">
                <span class="user-info">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
            <h1>Favorite Jokes</h1>
            <p>Your personal collection of favorite Chuck Norris jokes</p>
        </div>

        <div class="content">
            <a href="index.php" class="back-btn">
                <span>←</span>
                Back to Joke Generator
            </a>

            <!-- Stats -->
            <div class="stats">
                <strong>Total Favorites:</strong> <?php echo $totalFavorites; ?> |
                <strong>Page:</strong> <?php echo $page; ?> of <?php echo $totalPages; ?>
            </div>

            <!-- Favorite Jokes List -->
            <div id="favoritesList">
                <?php if (empty($favoriteJokes)): ?>
                    <div class="no-favorites">
                        <h3>No favorite jokes yet</h3>
                        <p>
                            You haven't added any jokes to your favorites yet.
                            <a href="index.php">Go back and generate some jokes</a> or
                            <a href="viewJokes.php">browse your saved jokes</a> to add some to favorites!
                        </p>
                    </div>
                <?php else: ?>
                    <?php foreach ($favoriteJokes as $joke): ?>
                        <div class="joke-item" data-joke-id="<?php echo $joke['id']; ?>">
                            <div class="joke-content">
                                <?php echo htmlspecialchars($joke['joke_text']); ?>
                            </div>
                            <div class="joke-meta">
                                <span class="joke-category">
                                    <?php echo htmlspecialchars(ucfirst($joke['category_name'])); ?>
                                </span>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span class="favorited-date">
                                        ❤️ Favorited on <?php echo date('M j, Y', strtotime($joke['favorited_at'])); ?>
                                    </span>
                                    <button class="favorite-btn favorited" onclick="removeFavorite(<?php echo $joke['id']; ?>)">
                                        Remove from Favorites
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>">Previous</a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function removeFavorite(jokeId) {
            fetch('favorites.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=remove_favorite&joke_id=${jokeId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the joke item from the DOM
                    const jokeItem = document.querySelector(`[data-joke-id="${jokeId}"]`);
                    if (jokeItem) {
                        jokeItem.remove();
                        
                        // Update stats
                        const statsElement = document.querySelector('.stats');
                        const totalMatch = statsElement.textContent.match(/Total Favorites:\s*(\d+)/);
                        if (totalMatch) {
                            const currentTotal = parseInt(totalMatch[1]);
                            const newTotal = currentTotal - 1;
                            statsElement.innerHTML = statsElement.innerHTML.replace(
                                /Total Favorites:\s*\d+/,
                                `Total Favorites: ${newTotal}`
                            );
                        }
                        
                        // Show message
                        showMessage(data.message, 'success');
                        
                        // If no more favorites, show empty state
                        const favoritesList = document.getElementById('favoritesList');
                        if (favoritesList.children.length === 0) {
                            favoritesList.innerHTML = `
                                <div class="no-favorites">
                                    <h3>No favorite jokes yet</h3>
                                    <p>
                                        You haven't added any jokes to your favorites yet.
                                        <a href="index.php">Go back and generate some jokes</a> or
                                        <a href="viewJokes.php">browse your saved jokes</a> to add some to favorites!
                                    </p>
                                </div>
                            `;
                        }
                    }
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Error removing from favorites. Please try again.', 'error');
            });
        }

        function showMessage(message, type) {
            // Create message element
            const messageDiv = document.createElement('div');
            messageDiv.className = type === 'success' ? 'success-message' : 'error-message';
            messageDiv.textContent = message;
            
            // Insert at top of content
            const content = document.querySelector('.content');
            content.insertBefore(messageDiv, content.firstChild);
            
            // Remove after 3 seconds
            setTimeout(() => {
                messageDiv.remove();
            }, 3000);
        }
    </script>
</body>
</html> 