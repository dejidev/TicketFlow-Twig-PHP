# TicketFlow - Twig/PHP Implementation

A server-side rendered ticket management application built with PHP 8.0+ and inspired by Twig templating patterns. Features session-based authentication, AJAX interactions, and full CRUD operations.

---

## 🚀 Quick Start

### Prerequisites
- **PHP 8.0 or higher** (for arrow functions and modern syntax)
- Modern web browser

### Check PHP Version
```bash
php -v
# Should show: PHP 8.0.0 or higher
```

### Running the Application

```bash
# Navigate to the twig-php directory
cd twig-php/

# Start PHP built-in server
php -S localhost:8002

# Open browser
# Visit: http://localhost:8002
```

**Alternative Ports** (if 8002 is busy):
```bash
php -S localhost:8080
php -S localhost:3000
php -S localhost:8888
```

---

## 📦 Dependencies

**Server-side**:
- PHP 8.0+ with sessions enabled
- No additional PHP extensions required

**Client-side** (CDN):
- Tailwind CSS 3.x - Styling
- Native JavaScript - Interactivity

```html
<!-- Already included in index.php -->
<script src="https://cdn.tailwindcss.com"></script>
```

---

## 🏗 Architecture

### File Structure

```
index.php (Single file containing):
├── Session Management
├── POST Request Handlers (AJAX API)
│   ├── signup
│   ├── login
│   ├── logout
│   ├── create_ticket
│   ├── update_ticket
│   ├── delete_ticket
│   └── get_tickets
├── Route Protection
├── Statistics Calculation
└── HTML Templates
    ├── Landing Page
    ├── Login/Signup Pages
    ├── Dashboard
    └── Ticket Management
```

### Request Flow

```
Browser Request
    ↓
PHP Session Check
    ↓
Route Protection
    ↓
POST Request? → API Handler → JSON Response
    ↓ No
Page Rendering → HTML Response
```

---

## 🔧 Key PHP Concepts

### 1. Session Management

```php
<?php
session_start();

// Initialize session data
if (!isset($_SESSION['tickets'])) {
    $_SESSION['tickets'] = [];
}
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = null;
}
if (!isset($_SESSION['authenticated'])) {
    $_SESSION['authenticated'] = false;
}
```

**Session Variables**:
- `$_SESSION['user']` - User credentials (name, email, hashed password)
- `$_SESSION['authenticated']` - Boolean authentication status
- `$_SESSION['tickets']` - Array of ticket objects

---

### 2. AJAX Request Handling

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'signup':
            // Handle signup
            echo json_encode(['success' => true, 'message' => 'Account created!']);
            exit;
            
        case 'login':
            // Handle login
            echo json_encode(['success' => true]);
            exit;
            
        // ... more cases
    }
}
```

**Key Points**:
- Check for POST request
- Set JSON header
- Return JSON response
- Always `exit` after response

---

### 3. Password Security

```php
// Signup - Hash password
$_SESSION['user'] = [
    'name' => $name,
    'email' => $email,
    'password' => password_hash($password, PASSWORD_DEFAULT)
];

// Login - Verify password
if (password_verify($password, $_SESSION['user']['password'])) {
    $_SESSION['authenticated'] = true;
}
```

**Security Features**:
- `password_hash()` - Uses bcrypt by default
- `password_verify()` - Constant-time comparison
- Never store plain text passwords

---

### 4. Input Validation

```php
// Server-side validation
$title = $_POST['title'] ?? '';
$status = $_POST['status'] ?? 'open';

if (empty($title)) {
    echo json_encode(['success' => false, 'message' => 'Title is required']);
    exit;
}

if (!in_array($status, ['open', 'in_progress', 'closed'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}
```

---

### 5. Route Protection

```php
$page = $_GET['page'] ?? 'landing';
$isAuthenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'];

// Protect routes
if (in_array($page, ['dashboard', 'tickets']) && !$isAuthenticated) {
    $page = 'login';
}
```

---

## 🎯 CRUD Operations

### Create Ticket

```php
case 'create_ticket':
    if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $status = $_POST['status'] ?? 'open';
    $priority = $_POST['priority'] ?? 'medium';
    
    // Validation
    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Title is required']);
        exit;
    }
    
    // Create ticket
    $ticket = [
        'id' => time() . rand(1000, 9999),
        'title' => $title,
        'description' => $description,
        'status' => $status,
        'priority' => $priority
    ];
    
    $_SESSION['tickets'][] = $ticket;
    echo json_encode([
        'success' => true, 
        'message' => 'Ticket created!',
        'ticket' => $ticket
    ]);
    exit;
```

**Client-side AJAX**:
```javascript
const formData = new FormData();
formData.append('action', 'create_ticket');
formData.append('title', 'Fix bug');
formData.append('status', 'open');

const response = await fetch('', {
    method: 'POST',
    body: formData
});

const data = await response.json();
if (data.success) {
    showToast(data.message, 'success');
}
```

---

### Read Tickets

**Server-side (Page Load)**:
```php
<?php foreach ($_SESSION['tickets'] as $ticket): ?>
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3><?php echo htmlspecialchars($ticket['title']); ?></h3>
        <p><?php echo htmlspecialchars($ticket['description']); ?></p>
        <span class="<?php echo getStatusClass($ticket['status']); ?>">
            <?php echo strtoupper(str_replace('_', ' ', $ticket['status'])); ?>
        </span>
    </div>
<?php endforeach; ?>
```

**AJAX Endpoint**:
```php
case 'get_tickets':
    if (!$_SESSION['authenticated']) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    echo json_encode(['success' => true, 'tickets' => $_SESSION['tickets']]);
    exit;
```

---

### Update Ticket

```php
case 'update_ticket':
    $id = $_POST['id'] ?? '';
    $title = $_POST['title'] ?? '';
    
    // Find and update
    foreach ($_SESSION['tickets'] as $index => $ticket) {
        if ($ticket['id'] === $id) {
            $_SESSION['tickets'][$index] = [
                'id' => $id,
                'title' => $title,
                'description' => $_POST['description'] ?? '',
                'status' => $_POST['status'] ?? 'open',
                'priority' => $_POST['priority'] ?? 'medium'
            ];
            echo json_encode(['success' => true, 'message' => 'Updated!']);
            exit;
        }
    }
    
    echo json_encode(['success' => false, 'message' => 'Not found']);
    exit;
```

---

### Delete Ticket

```php
case 'delete_ticket':
    $id = $_POST['id'] ?? '';
    
    foreach ($_SESSION['tickets'] as $index => $ticket) {
        if ($ticket['id'] === $id) {
            array_splice($_SESSION['tickets'], $index, 1);
            echo json_encode(['success' => true, 'message' => 'Deleted!']);
            exit;
        }
    }
    
    echo json_encode(['success' => false, 'message' => 'Not found']);
    exit;
```

---

## 📊 Statistics Calculation

```php
// Calculate stats (runs on dashboard page load)
$totalTickets = count($_SESSION['tickets']);
$openTickets = count(array_filter($_SESSION['tickets'], fn($t) => $t['status'] === 'open'));
$inProgressTickets = count(array_filter($_SESSION['tickets'], fn($t) => $t['status'] === 'in_progress'));
$closedTickets = count(array_filter($_SESSION['tickets'], fn($t) => $t['status'] === 'closed'));
```

**In Template**:
```php
<p class="text-3xl font-bold"><?php echo $totalTickets; ?></p>
<p class="text-3xl font-bold"><?php echo $openTickets; ?></p>
```

---

## 🎨 Template Rendering

### Conditional Rendering

```php
<?php if ($page === 'landing'): ?>
    <!-- Landing page content -->
<?php elseif ($page === 'login' || $page === 'signup'): ?>
    <!-- Auth page content -->
<?php elseif ($page === 'dashboard'): ?>
    <!-- Dashboard content -->
<?php elseif ($page === 'tickets'): ?>
    <!-- Tickets content -->
<?php endif; ?>
```

### Loops

```php
<?php foreach ($_SESSION['tickets'] as $ticket): ?>
    <div>
        <h3><?php echo $ticket['title']; ?></h3>
    </div>
<?php endforeach; ?>
```

### XSS Protection

```php
<!-- Always escape user input -->
<p><?php echo htmlspecialchars($ticket['title']); ?></p>
<p><?php echo htmlspecialchars($ticket['description'] ?? ''); ?></p>
```

### Dynamic Classes

```php
<span class="<?php 
    echo $ticket['status'] === 'open' ? 'bg-green-100 text-green-800' : 
        ($ticket['status'] === 'in_progress' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-800'); 
?>">
    <?php echo strtoupper(str_replace('_', ' ', $ticket['status'])); ?>
</span>
```

---

## 🔐 Authentication System

### Signup Flow

```php
case 'signup':
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All fields required']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email']);
        exit;
    }
    
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password too short']);
        exit;
    }
    
    // Create user
    $_SESSION['user'] = [
        'name' => $name,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT)
    ];
    $_SESSION['authenticated'] = true;
    
    echo json_encode(['success' => true, 'message' => 'Account created!']);
    exit;
```

### Login Flow

```php
case 'login':
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Check user exists
    if (!isset($_SESSION['user']) || $_SESSION['user']['email'] !== $email) {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        exit;
    }
    
    // Verify password
    if (!password_verify($password, $_SESSION['user']['password'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        exit;
    }
    
    $_SESSION['authenticated'] = true;
    echo json_encode(['success' => true, 'message' => 'Login successful!']);
    exit;
```

### Logout Flow

```php
case 'logout':
    $_SESSION['authenticated'] = false;
    echo json_encode(['success' => true]);
    exit;
```

**Client-side**:
```javascript
async function handleLogout() {
    const formData = new FormData();
    formData.append('action', 'logout');
    
    const response = await fetch('', {
        method: 'POST',
        body: formData
    });
    
    const data = await response.json();
    if (data.success) {
        window.location.href = '?page=landing';
    }
}
```

---

## 🌐 Client-Side JavaScript

### Toast Notifications

```javascript
function showToast(message, type) {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    
    toast.className = `fixed top-4 right-4 z-50 flex items-center gap-2 px-4 py-3 rounded-lg shadow-lg text-white animate-slide-in ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    }`;
    
    const icon = type === 'success' 
        ? '<svg>...</svg>' // Success icon
        : '<svg>...</svg>'; // Error icon
    
    toast.innerHTML = `
        ${icon}
        <span>${message}</span>
        <button onclick="this.parentElement.remove()">×</button>
    `;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}
```

### Form Handling

```javascript
document.getElementById('ticket-form-element').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const ticketId = document.getElementById('ticket-id').value;
    
    if (ticketId) {
        formData.append('action', 'update_ticket');
        formData.append('id', ticketId);
    } else {
        formData.append('action', 'create_ticket');
    }
    
    try {
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('An error occurred. Please try again.', 'error');
    }
});
```

### Edit Ticket

```javascript
function editTicket(ticket) {
    document.getElementById('ticket-id').value = ticket.id;
    document.getElementById('ticket-title').value = ticket.title;
    document.getElementById('ticket-description').value = ticket.description || '';
    document.getElementById('ticket-status').value = ticket.status;
    document.getElementById('ticket-priority').value = ticket.priority;
    
    document.getElementById('form-title').textContent = 'Edit Ticket';
    document.getElementById('submit-btn-text').textContent = 'Update Ticket';
    document.getElementById('ticket-form').classList.remove('hidden');
    
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
```

### Delete Ticket

```javascript
async function deleteTicket(id) {
    if (!confirm('Are you sure you want to delete this ticket?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_ticket');
    formData.append('id', id);
    
    try {
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('An error occurred. Please try again.', 'error');
    }
}
```

---

## 🧪 Testing

### Manual Testing Checklist

**Session Management**:
- [ ] Session starts on first visit
- [ ] Session persists across page loads
- [ ] Session cleared on logout
- [ ] Protected routes redirect when not authenticated

**Authentication**:
- [ ] Signup creates user account
- [ ] Password is hashed
- [ ] Login verifies credentials
- [ ] Invalid credentials show error
- [ ] Logout works correctly

**CRUD Operations**:
- [ ] Create ticket adds to session
- [ ] Tickets display on page load
- [ ] Edit loads form with data
- [ ] Update modifies ticket
- [ ] Delete removes ticket
- [ ] All operations show toast

**Data Persistence**:
- [ ] Tickets persist across page loads
- [ ] User data persists in session
- [ ] Data cleared on server restart (expected)

---

## 🐛 Debugging

### Check PHP Errors

```php
// Add to top of index.php for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Debug Session Data

```php
// Add anywhere in PHP code
echo '<pre>';
print_r($_SESSION);
echo '</pre>';
exit;
```

### Debug POST Data

```php
echo '<pre>';
print_r($_POST);
echo '</pre>';
exit;
```

### Check PHP Version

```bash
php -v
# Should be 8.0 or higher
```

### Common Issues

**Issue**: "Parse error: syntax error"
```php
// Cause: PHP version < 8.0 (arrow functions not supported)
fn($t) => $t['status'] === 'open'

// Solution: Upgrade PHP or use traditional function
function($t) { return $t['status'] === 'open'; }
```

**Issue**: "Headers already sent"
```php
// Cause: Output before header()
echo "Something";
header('Content-Type: application/json'); // Error!

// Solution: Don't output anything before headers
header('Content-Type: application/json');
echo json_encode(['success' => true]);
```

**Issue**: Session not persisting
```php
// Cause: session_start() not called
// Solution: Add at very top of file
<?php
session_start();
```

---

## 🔒 Security Considerations

### Current Implementation

✅ **Implemented**:
- Password hashing (bcrypt)
- Session-based authentication
- Input validation
- XSS prevention (htmlspecialchars)
- Email validation (filter_var)

⚠️ **Missing** (Add for production):
- CSRF token protection
- Rate limiting
- SQL injection prevention (if using database)
- HTTPS enforcement
- Secure session configuration
- Input sanitization
- Content Security Policy

### Production Security Setup

```php
// Secure session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // HTTPS only
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);

// CSRF Token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// In forms
<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

// Verify on submit
if (!verifyCSRFToken($_POST['csrf_token'])) {
    die('CSRF token validation failed');
}
```

### Input Sanitization

```php
// Sanitize string input
$title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);

// Validate email
$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);

// Escape output
echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
```

---

## 📊 PHP vs JavaScript Frameworks

| Feature | PHP (Server-side) | React/Vue (Client-side) |
|---------|------------------|------------------------|
| **Rendering** | Server | Client Browser |
| **SEO** | Excellent (native) | Requires SSR |
| **Initial Load** | Full HTML | Skeleton + JS |
| **State Management** | Sessions | useState/ref() |
| **Persistence** | Server storage | localStorage/API |
| **Security** | Server-side | Client + Server |
| **Reactivity** | Page reload | Automatic |
| **Scalability** | Vertical | Horizontal |

---

## 🚀 Performance Optimization

### Current Optimizations

1. **Single File**: Reduced HTTP requests
2. **CDN Assets**: Fast CSS delivery
3. **Minimal JS**: Only essential scripts

### Future Optimizations

```php
// 1. Output buffering
ob_start();
// ... page content
ob_end_flush();

// 2. Caching headers
header('Cache-Control: public, max-age=3600');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

// 3. Compression
if (extension_loaded('zlib')) {
    ob_start('ob_gzhandler');
}

// 4. Asset optimization
// Minify CSS/JS
// Optimize images
// Use HTTP/2
```

---

## 🔄 Migration to Production

### 1. Split into Multiple Files

```
project/
├── index.php               # Entry point
├── config/
│   └── database.php       # DB config
├── controllers/
│   ├── AuthController.php
│   └── TicketController.php
├── models/
│   ├── User.php
│   └── Ticket.php
├── views/
│   ├── landing.php
│   ├── auth.php
│   ├── dashboard.php
│   └── tickets.php
├── public/
│   ├── css/
│   └── js/
└── vendor/                # Composer dependencies
```

### 2. Add Database

```php
// Use PDO for database operations
$pdo = new PDO('mysql:host=localhost;dbname=ticketflow', 'user', 'pass');

// Create tickets table
$pdo->exec("
    CREATE TABLE tickets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        status ENUM('open', 'in_progress', 'closed') DEFAULT 'open',
        priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )
");

// Insert ticket
$stmt = $pdo->prepare("
    INSERT INTO tickets (user_id, title, description, status, priority)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([$userId, $title, $description, $status, $priority]);
```

### 3. Add Composer Dependencies

```bash
composer init
composer require vlucas/phpdotenv  # Environment variables
composer require twig/twig          # Real Twig templating
```

**Use Real Twig**:
```php
require_once 'vendor/autoload.php';

$loader = new \Twig\Loader\FilesystemLoader('views');
$twig = new \Twig\Environment($loader, [
    'cache' => 'cache',
    'debug' => true
]);

echo $twig->render('tickets.html.twig', [
    'tickets' => $tickets
]);
```

### 4. Environment Configuration

```php
// .env file
DB_HOST=localhost
DB_NAME=ticketflow
DB_USER=root
DB_PASS=secret
APP_ENV=production

// Load in PHP
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$dbHost = $_ENV['DB_HOST'];
```

### 5. Add Router

```php
// Simple router
$routes = [
    '/' => 'LandingController@index',
    '/login' => 'AuthController@login',
    '/dashboard' => 'DashboardController@index',
    '/tickets' => 'TicketController@index'
];

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (isset($routes[$uri])) {
    list($controller, $method) = explode('@', $routes[$uri]);
    $controller = new $controller();
    $controller->$method();
}
```

---

## 📱 Browser Support

- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+
- ✅ Works on all PHP 8.0+ servers

---

## 🧰 Development Tools

### Recommended PHP Tools

```bash
# Code Sniffer (Style checker)
composer require --dev squizlabs/php_codesniffer

# PHPUnit (Testing)
composer require --dev phpunit/phpunit

# PHP Stan (Static analysis)
composer require --dev phpstan/phpstan

# Xdebug (Debugging)
# Install via PECL or package manager
```

### IDE Recommendations

- **PhpStorm** - Full-featured PHP IDE
- **VS Code** - With PHP extensions
- **Sublime Text** - With PHP plugins

---

## 📚 Learn More

### PHP Resources

- [Official PHP Documentation](https://www.php.net/docs.php)
- [PHP The Right Way](https://phptherightway.com/)
- [Laravel (Popular PHP Framework)](https://laravel.com)
- [Symfony (Enterprise PHP Framework)](https://symfony.com)
- [Twig Documentation](https://twig.symfony.com/doc/3.x/)

### Key PHP Concepts

1. Sessions and cookies
2. Form handling and validation
3. Password hashing and security
4. Database operations (PDO)
5. Error handling and exceptions
6. Object-oriented PHP
7. Composer and autoloading
8. MVC architecture

---

## 🆚 When to Use PHP vs JavaScript Frameworks

### Use PHP/Server-side when:
- ✅ SEO is critical
- ✅ Building content-heavy sites
- ✅ Need server-side processing
- ✅ Working with existing PHP infrastructure
- ✅ Building traditional web apps

### Use React/Vue when:
- ✅ Building SPAs (Single Page Apps)
- ✅ Need real-time updates
- ✅ Complex client-side interactions
- ✅ Mobile app development (React Native)
- ✅ Modern progressive web apps

### Best of Both Worlds:
- Use PHP API + React/Vue frontend
- Server-side rendering with Next.js/Nuxt.js
- Progressive enhancement approach

---

## 🤝 Contributing

Contributions welcome! Please:
1. Follow PSR-12 coding standards
2. Add proper documentation
3. Include error handling
4. Test all endpoints
5. Sanitize all inputs
6. Use prepared statements for DB

---

## 📄 License

MIT License - See LICENSE file for details

---

## 🙏 Acknowledgments

- PHP community for excellent documentation
- Tailwind CSS for rapid styling
- Modern web standards

---

**Built with PHP 8.0+ • Styled with Tailwind CSS • Inspired by Twig Templating**

---

## 📞 Troubleshooting

### Server Won't Start

```bash
# Check if port is in use
lsof -i :8002
# or on Windows
netstat -ano | findstr :8002

# Kill process and restart
kill -9 <PID>
php -S localhost:8002
```

### Session Not Working

```bash
# Check session save path
php -i | grep session.save_path

# Make sure directory is writable
chmod 777 /tmp  # or your session directory
```

### AJAX Requests Failing

```javascript
// Check console for errors
// Make sure fetch URL is correct
// Verify FormData is being sent
console.log([...formData.entries()]);
```

---

**Need help? Check the main README or open an issue on GitHub!**#   T i c k e t F l o w - T w i g - P H P  
 