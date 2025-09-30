# Chuck Norris Jokes Web Application

A PHP web application that interacts with the Chuck Norris Jokes API, stores data in a MySQL database, and displays jokes in a user-friendly interface with user authentication.

## Features

- **User Authentication**: Secure login and registration system
- **User-Specific Jokes**: Each user has their own collection of saved jokes
- **Favorite Jokes**: Mark and manage your favorite jokes with easy access
- **API Integration**: Fetches joke categories and random jokes from the Chuck Norris API
- **Database Storage**: Automatically stores jokes and categories in MySQL database
- **User Interface**: Modern, responsive design with dropdown category selection
- **Real-time Updates**: AJAX-powered interface for seamless user experience
- **Joke History**: View recently stored jokes with pagination
- **Mobile Responsive**: Works on desktop and mobile devices

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- cURL extension for PHP
- MySQL extension for PHP

## Installation

1. **Clone or download the project** to your web server directory

2. **Configure Database**:
   - Create a MySQL database named `chuck_norris_jokes`
   - Update database credentials in `databaseConnection.php` if needed:
     ```php
     $servername = "localhost";
     $username = "your_username";
     $password = "your_password";
     $dbname = "chuck_norris_jokes";
     ```

3. **Set up Web Server**:
   - Ensure your web server can execute PHP files
   - Make sure the project directory is accessible via web browser

4. **Access the Application**:
   - Navigate to `http://your-domain/login.php` in your web browser
   - Register a new account or login with existing credentials
   - The application will automatically create the required database tables on first run

## Database Schema

The application creates three main tables:

### Users Table
```sql
CREATE TABLE users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Categories Table
```sql
CREATE TABLE categories (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Jokes Table
```sql
CREATE TABLE jokes (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    joke_text TEXT NOT NULL,
    category_id INT(11),
    api_id VARCHAR(255),
    user_id INT(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Favorites Table
```sql
CREATE TABLE favorites (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    joke_id INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_joke (user_id, joke_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (joke_id) REFERENCES jokes(id) ON DELETE CASCADE
);
```

## API Endpoints Used

- `GET /jokes/categories` - Fetch all available joke categories
- `GET /jokes/random?category={category}` - Get a random joke from a specific category

## File Structure

```
Chuck-jokes/
├── index.html              # Redirect to login page
├── login.php               # User login page
├── register.php            # User registration page
├── logout.php              # Logout functionality
├── index.php               # Main application interface (requires login)
├── viewJokes.php           # All stored jokes page with filtering and pagination
├── favorites.php           # Favorite jokes management page
├── fetchJoke.php           # API interaction and database operations
├── databaseConnection.php  # Database connection and setup
├── CSS/
│   └── style.css          # Application styling
└── README.md              # This file
```

## Usage

1. **Registration/Login**: Create a new account or login with existing credentials
2. **Select a Category**: Choose a joke category from the dropdown menu
3. **Get Random Joke**: Click "Get Random Joke" to fetch a joke from the selected category
4. **View All Stored Jokes**: Click "View All Stored Jokes" button to browse your personal joke collection
5. **Manage Favorites**: Add jokes to favorites or view your favorite jokes collection
6. **Filter and Browse**: Use the dedicated view page to filter jokes by category and navigate through pages
7. **Logout**: Use the logout button to securely end your session

## Features in Detail

### User Authentication
- Secure user registration with email validation
- Password hashing using PHP's built-in password_hash()
- Session-based authentication
- User-specific joke collections
- Automatic redirect to login for unauthenticated users

### Favorite Jokes Management
- Add jokes to favorites with one-click functionality
- Remove jokes from favorites easily
- Dedicated favorites page with pagination
- Visual indicators for favorite status (heart icons)
- Real-time updates without page refresh
- User-specific favorite collections
- Quick access from main navigation

### Category Management
- Automatically fetches and stores all available categories from the API
- Categories are stored in the database for faster loading
- Dropdown menu shows all available categories

### Joke Fetching
- Fetches random jokes from selected categories
- Automatically stores new jokes in the database with user association
- Prevents duplicate jokes per user using API ID tracking
- Shows loading states during API calls

### Joke Browsing
- Dedicated page for viewing user's personal joke collection
- Category-based filtering system
- Pagination for large joke collections (5 jokes per page)
- Statistics display showing total jokes and current page
- Easy navigation between main page and joke browser

### User Interface
- Modern gradient design with smooth animations
- Clean main interface focused on joke generation
- User navigation showing logged-in username
- "View All Stored Jokes" button for organized joke browsing
- Dedicated view page with filtering and pagination
- Responsive layout that works on all devices
- Real-time updates without page refresh
- Error handling with user-friendly messages

### Database Features
- Automatic table creation on first run
- Foreign key relationships for data integrity
- Timestamp tracking for all records
- Efficient querying with proper indexing
- User-specific data isolation

## Troubleshooting

### Common Issues

1. **Database Connection Error**:
   - Verify MySQL is running
   - Check database credentials in `databaseConnection.php`
   - Ensure MySQL user has proper permissions

2. **API Not Working**:
   - Check internet connection
   - Verify cURL extension is enabled in PHP
   - Check if the Chuck Norris API is accessible

3. **Page Not Loading**:
   - Ensure PHP is properly configured on your web server
   - Check file permissions
   - Verify all files are in the correct directory

4. **Login Issues**:
   - Ensure sessions are enabled in PHP
   - Check if cookies are enabled in browser
   - Verify database tables are created properly

### Error Messages

- **"Connection failed"**: Database connection issue
- **"Error fetching joke"**: API or network issue
- **"Category not provided"**: Form validation error
- **"Authentication required"**: User not logged in

## Security Features

- SQL injection prevention using prepared statements
- XSS protection with HTML escaping
- Input validation and sanitization
- Secure database connections
- Password hashing with bcrypt
- Session-based authentication
- User data isolation
- CSRF protection through session validation

## Performance Optimizations

- Database indexing on frequently queried columns
- Efficient AJAX requests with minimal data transfer
- Caching of categories in database
- Pagination for large joke collections
- Separated main page and joke browser for better performance
- Optimized database queries with proper JOIN statements
- User-specific queries for better performance

## Future Enhancements

- Password reset functionality
- Email verification for new accounts
- Favorite jokes functionality
- Joke rating system
- Advanced search functionality with text search
- Export jokes to different formats (CSV, JSON, PDF)
- Admin panel for joke management
- Joke sharing functionality
- Dark mode toggle
- Joke bookmarking system
- Social login integration (Google, Facebook)

## License

This project is open source and available under the MIT License.

## Support

For issues or questions, please check the troubleshooting section above or create an issue in the project repository. 

---

## 🚀 How to Run the Dockerized Application

1. **Install Docker & Docker Compose**  
   - [Docker Desktop](https://www.docker.com/products/docker-desktop/) (includes Compose)

2. **Clone this repository and open a terminal in the project root.**

3. **Build and start the containers:**
   ```sh
   docker-compose up --build
   ```

4. **Access the application:**
   - App: [http://localhost:8080](http://localhost:8080) (redirects to login)
   - phpMyAdmin: [http://localhost:8081](http://localhost:8081)  
     (Login with user: `root`, password: `root`)

5. **Stop the containers:**
   ```sh
   docker-compose down
   ```

--- 