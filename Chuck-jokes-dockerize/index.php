<?php
// Include the database connection script
require_once 'databaseConnection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Include the Chuck Norris API wrapper that handles fetching and storing jokes
require_once 'fetchJoke.php';

// Create an instance of the API class, passing in the database connection
$api = new ChuckNorrisAPI($conn);

// Get all categories stored in the database
$storedCategories = $api->getStoredCategories();

// Get the 5 most recently stored jokes from the database
$storedJokes = $api->getStoredJokes(5);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chuck Norris Jokes</title>
    <link rel="stylesheet" href="CSS/style.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="user-nav">
                <span class="user-info">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
            <h1>Chuck Norris Jokes</h1>
            <p>Get random jokes from the Chuck Norris API and store them in your database</p>
        </div>

        <div class="content">
            <!-- Category Selection Section -->
            <div class="category-section">
                <h2>Select a Joke Category</h2>
                <select id="categorySelect" class="category-select">
                    <option value="">Choose a category...</option>
                    <?php foreach ($storedCategories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['category_name']); ?>">
                            <?php echo htmlspecialchars(ucfirst($category['category_name'])); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button id="getJokeBtn" class="get-joke-btn" disabled>Get Random Joke</button>
            </div>

            <!-- Joke Display Section -->
            <div id="jokeDisplay" class="joke-display hidden">
                <div id="jokeText" class="joke-text"></div>
            </div>

            <!-- View All Stored Jokes Button -->
            <div class="view-all-section">
                <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                    <button id="viewAllBtn" class="view-all-btn">
                        <span class="icon">🔍</span>
                        View All Stored Jokes
                    </button>
                    <a href="favorites.php" class="view-all-btn" style="text-decoration: none; display: inline-flex; align-items: center; gap: 10px;">
                        <span class="icon">❤️</span>
                        View Favorite Jokes
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 
    JavaScript/AJAX enables dynamic updates without reloading:
    - Fetches jokes & categories in real-time
    - Improves UX with instant interactions
    - Enables smooth pagination & live updates
    - Avoids slow, full page reloads
    -->

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const categorySelect = document.getElementById('categorySelect');
            const getJokeBtn = document.getElementById('getJokeBtn');
            const jokeDisplay = document.getElementById('jokeDisplay');
            const jokeText = document.getElementById('jokeText');
            const viewAllBtn = document.getElementById('viewAllBtn');

            // Enable/disable get joke button based on category selection
            categorySelect.addEventListener('change', function() {
                getJokeBtn.disabled = !this.value;
            });

            // Get random joke
            getJokeBtn.addEventListener('click', function() {
                const selectedCategory = categorySelect.value;
                if (!selectedCategory) return;

                // Show loading state
                jokeDisplay.classList.remove('hidden');
                jokeText.innerHTML = '<span class="loading">Loading joke...</span>';
                getJokeBtn.disabled = true;

                // Make AJAX request
                fetch('fetchJoke.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=get_random_joke&category=${encodeURIComponent(selectedCategory)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            jokeText.innerHTML = `<span class="error">Error: ${data.error}</span>`;
                        } else {
                            jokeText.textContent = data.value;
                        }
                    })
                    .catch(error => {
                        jokeText.innerHTML = '<span class="error">Error fetching joke. Please try again.</span>';
                        console.error('Error:', error);
                    })
                    .finally(() => {
                        getJokeBtn.disabled = false;
                    });
            });

            // View all stored jokes
            viewAllBtn.addEventListener('click', function() {
                window.location.href = 'viewJokes.php';
            });

            // Helper function to escape HTML
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // Load categories if none exist
            if (categorySelect.options.length <= 1) {
                fetch('fetchJoke.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=get_categories'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.length > 0) {
                            data.forEach(category => {
                                const option = document.createElement('option');
                                option.value = category;
                                option.textContent = category.charAt(0).toUpperCase() + category.slice(1);
                                categorySelect.appendChild(option);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error loading categories:', error);
                    });
            }
        });
    </script>
</body>

</html>