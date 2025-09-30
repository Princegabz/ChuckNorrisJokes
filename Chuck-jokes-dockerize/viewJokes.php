<?php
require_once 'databaseConnection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'fetchJoke.php';

$api = new ChuckNorrisAPI($conn);

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 5;
$offset = ($page - 1) * $perPage;

// Filter by category
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';

// Get total count for current user
$countSql = "SELECT COUNT(*) as total FROM jokes j JOIN categories c ON j.category_id = c.id WHERE j.user_id = ?";
if ($categoryFilter) {
    $countSql .= " AND c.category_name = ?";
    $stmt = $conn->prepare($countSql);
    $stmt->bind_param("is", $_SESSION['user_id'], $categoryFilter);
    $stmt->execute();
    $totalResult = $stmt->get_result();
} else {
    $stmt = $conn->prepare($countSql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $totalResult = $stmt->get_result();
}
$totalJokes = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalJokes / $perPage);

// Get jokes with pagination for current user
$sql = "SELECT j.*, c.category_name 
        FROM jokes j 
        JOIN categories c ON j.category_id = c.id
        WHERE j.user_id = ?";
if ($categoryFilter) {
    $sql .= " AND c.category_name = ?";
    $sql .= " ORDER BY j.created_at DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isii", $_SESSION['user_id'], $categoryFilter, $perPage, $offset);
} else {
    $sql .= " ORDER BY j.created_at DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $_SESSION['user_id'], $perPage, $offset);
}
$stmt->execute();
$result = $stmt->get_result();

$jokes = [];
while ($row = $result->fetch_assoc()) {
    $jokes[] = $row;
}

// Get all categories for filter
$categories = $api->getStoredCategories();

// Get favorite status for each joke
$favoriteStatus = [];
if (!empty($jokes)) {
    $jokeIds = array_column($jokes, 'id');
    $placeholders = str_repeat('?,', count($jokeIds) - 1) . '?';
    $favoriteSql = "SELECT joke_id FROM favorites WHERE user_id = ? AND joke_id IN ($placeholders)";
    $stmt = $conn->prepare($favoriteSql);
    $params = array_merge([$_SESSION['user_id']], $jokeIds);
    $stmt->bind_param(str_repeat('i', count($params)), ...$params);
    $stmt->execute();
    $favoriteResult = $stmt->get_result();
    
    while ($row = $favoriteResult->fetch_assoc()) {
        $favoriteStatus[$row['joke_id']] = true;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Stored Jokes - Chuck Norris Jokes</title>
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

        .filter-section {
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background: white;
        }

        .clear-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .clear-btn:hover {
            background: #5a6268;
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

        .no-jokes {
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

        .nav-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .nav-btn {
            background: #009688;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .nav-btn:hover {
            background: #00796B;
            transform: translateY(-1px);
        }

        .nav-btn.secondary {
            background: #6c757d;
        }

        .nav-btn.secondary:hover {
            background: #5a6268;
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
            <h1>All Stored Jokes</h1>
            <p>Browse through all the Chuck Norris jokes stored in your database</p>
        </div>

        <div class="content">
            <div class="nav-buttons">
                <a href="index.php" class="nav-btn">
                    <span>←</span>
                    Back to Joke Generator
                </a>
                <a href="favorites.php" class="nav-btn secondary">
                    <span>❤️</span>
                    View Favorites
                </a>
            </div>

            <!-- Stats -->
            <div class="stats">
                <strong>Total Jokes:</strong> <?php echo $totalJokes; ?> |
                <strong>Page:</strong> <?php echo $page; ?> of <?php echo $totalPages; ?>
                <?php if ($categoryFilter): ?>
                    | <strong>Filtered by:</strong> <?php echo htmlspecialchars(ucfirst($categoryFilter)); ?>
                <?php endif; ?>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <label for="categoryFilter">Filter by Category:</label>
                <select id="categoryFilter" class="filter-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['category_name']); ?>"
                            <?php echo ($categoryFilter === $category['category_name']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(ucfirst($category['category_name'])); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($categoryFilter): ?>
                    <a href="viewJokes.php" class="clear-btn">Clear Filter</a>
                <?php endif; ?>
            </div>

            <!-- Jokes List -->
            <div id="jokesList">
                <?php if (empty($jokes)): ?>
                    <div class="no-jokes">
                        <h3>No jokes found</h3>
                        <p>
                            <?php if ($categoryFilter): ?>
                                No jokes found for the selected category.
                                <a href="viewJokes.php">View all jokes</a> or
                                <a href="index.php">get some new jokes</a>.
                            <?php else: ?>
                                No jokes stored yet.
                                <a href="index.php">Go back and get your first joke!</a>
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <?php foreach ($jokes as $joke): ?>
                        <div class="joke-item" data-joke-id="<?php echo $joke['id']; ?>">
                            <div class="joke-content">
                                <?php echo htmlspecialchars($joke['joke_text']); ?>
                            </div>
                            <div class="joke-meta">
                                <span class="joke-category">
                                    <?php echo htmlspecialchars(ucfirst($joke['category_name'])); ?>
                                </span>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span class="joke-date">
                                        <?php echo date('M j, Y g:i A', strtotime($joke['created_at'])); ?>
                                    </span>
                                    <button class="favorite-btn <?php echo isset($favoriteStatus[$joke['id']]) ? 'favorited' : ''; ?>" 
                                            onclick="toggleFavorite(<?php echo $joke['id']; ?>)">
                                        <?php echo isset($favoriteStatus[$joke['id']]) ? '❤️ Remove from Favorites' : '🤍 Add to Favorites'; ?>
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
                        <a href="?page=<?php echo $page - 1; ?><?php echo $categoryFilter ? '&category=' . urlencode($categoryFilter) : ''; ?>">Previous</a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?><?php echo $categoryFilter ? '&category=' . urlencode($categoryFilter) : ''; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $categoryFilter ? '&category=' . urlencode($categoryFilter) : ''; ?>">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function applyFilter() {
            const category = document.getElementById('categoryFilter').value;
            const url = new URL(window.location);

            if (category) {
                url.searchParams.set('category', category);
            } else {
                url.searchParams.delete('category');
            }

            url.searchParams.delete('page'); // Reset to first page
            window.location.href = url.toString();
        }

        function toggleFavorite(jokeId) {
            const button = event.target;
            const isFavorited = button.classList.contains('favorited');
            const action = isFavorited ? 'remove_favorite' : 'add_favorite';

            fetch('favorites.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=${action}&joke_id=${jokeId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (isFavorited) {
                        // Remove from favorites
                        button.classList.remove('favorited');
                        button.innerHTML = '🤍 Add to Favorites';
                    } else {
                        // Add to favorites
                        button.classList.add('favorited');
                        button.innerHTML = '❤️ Remove from Favorites';
                    }
                    showMessage(data.message, 'success');
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Error updating favorite status. Please try again.', 'error');
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

        // Auto-submit on category change
        document.getElementById('categoryFilter').addEventListener('change', function() {
            applyFilter();
        });
    </script>
</body>

</html>