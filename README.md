# Chuck Norris Jokes Web App 🤠😂  

A fun and interactive **PHP web application** that fetches jokes from the [Chuck Norris Jokes API](https://api.chucknorris.io/), stores them in a **MySQL database**, and presents them in a **clean, user-friendly interface**. The project is fully **Dockerized** for portability and adheres to **PSR-12 coding standards**.  

---

## 🚀 Features  

- 🔗 **Public API Interaction**  
  - Fetch all joke categories from `/jokes/categories`.  
  - Select a category from a dropdown list.  
  - Retrieve a random joke from the selected category using `/jokes/random?category={category}`.  

- 💾 **Database Integration (MySQL)**  
  - Store jokes and categories in relational tables.  
  - Prevent duplicate entries by handling joke IDs.  

- 🎨 **User Interface**  
  - Simple, intuitive UI with a dropdown for categories.  
  - Display fetched jokes in real time.  

- 🐳 **Dockerized Setup**  
  - Separate containers for PHP, MySQL, and phpMyAdmin.  
  - Easy to run anywhere with Docker.  

- ✅ **Code Quality & Standards**  
  - Follows **PSR-12 coding style**.  
  - MVC structure for cleaner separation of logic.  

- 🔒 **Security & Reliability**  
  - Input validation to prevent SQL injection.  
  - Graceful error handling with user-friendly messages.  

- ⭐ **Bonus Features (Optional)**  
  - User profiles for personalized experience.  
  - Save jokes as **favorites** for quick access later.  

---

## 🗄️ Database Schema  

- **Categories Table**  
  - `id` (Primary Key, Auto Increment)  
  - `name` (VARCHAR, Unique)  

- **Jokes Table**  
  - `id` (Primary Key, Auto Increment)  
  - `category_id` (Foreign Key → Categories)  
  - `joke_text` (TEXT)  
  - `created_at` (Timestamp)  

---

## 🛠️ Tech Stack  

- **Backend:** PHP 8+  
- **Frontend:** HTML5 / CSS3 / Bootstrap (or any chosen framework)  
- **Database:** MySQL 8  
- **Containerization:** Docker & Docker Compose  

---

## 📦 Getting Started  

### 1️⃣ Clone the Repository  
```bash
git clone https://github.com/your-username/chuck-norris-jokes.git
cd chuck-norris-jokes
