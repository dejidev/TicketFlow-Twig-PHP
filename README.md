# 🎟️ TicketFlow — PHP + Twig-Inspired Ticket Manager

A lightweight, server-side rendered ticket management app built with **PHP 8.0+**, inspired by **Twig templating**.  
Includes session-based authentication, AJAX-powered CRUD, and Tailwind styling.

---

## 🚀 Quick Start

### Requirements
- PHP **8.0+**
- Modern web browser

### Run Locally
```bash
cd twig-php
php -S localhost:8002
```
Then open [http://localhost:8002](http://localhost:8002)

> Alternate ports: 8080 / 3000 / 8888

---

## 🧩 Features

- 🧑‍💻 Session-based authentication  
- ⚙️ Create / Read / Update / Delete tickets  
- 📈 Dashboard statistics  
- 🔐 Password hashing & validation  
- 🧾 JSON-based AJAX responses  
- 🎨 Tailwind CSS for UI styling  

---

## 🗂️ Project Structure

```
index.php
├── Session Management
├── AJAX Handlers (signup, login, logout, CRUD)
├── Route Protection
└── HTML Templates (Landing, Auth, Dashboard, Tickets)
```

---

## ⚙️ Core Concepts

### Sessions
```php
session_start();
$_SESSION['authenticated'] = $_SESSION['authenticated'] ?? false;
$_SESSION['tickets'] = $_SESSION['tickets'] ?? [];
```

### Secure Passwords
```php
password_hash($password, PASSWORD_DEFAULT);
password_verify($password, $_SESSION['user']['password']);
```

### AJAX Endpoint Example
```php
if ($_POST['action'] === 'create_ticket') {
  echo json_encode(['success' => true, 'message' => 'Ticket created!']);
  exit;
}
```

---

## 🎯 CRUD Overview

| Action | Method | Description |
|--------|---------|-------------|
| `signup` | POST | Create account |
| `login` | POST | Authenticate user |
| `logout` | POST | End session |
| `create_ticket` | POST | Add ticket |
| `update_ticket` | POST | Modify ticket |
| `delete_ticket` | POST | Remove ticket |
| `get_tickets` | POST | Fetch all tickets |

---

## 🧠 Key PHP Topics

- Sessions & state management  
- Form validation  
- JSON responses  
- Authentication & authorization  
- XSS protection with `htmlspecialchars()`  

---

## 🔒 Security Highlights

✅ Implemented:
- Password hashing (`bcrypt`)
- Input validation & escaping  
- Session-based auth  

⚠️ Recommended:
- CSRF protection  
- HTTPS enforcement  
- Rate limiting  
- Secure cookie settings  

---

## 📦 Dependencies

- **PHP 8.0+**
- **Tailwind CSS (CDN)**  
  ```html
  <script src="https://cdn.tailwindcss.com"></script>
  ```

---

## 🧰 Development Tips

- Enable errors for debugging:
  ```php
  error_reporting(E_ALL);
  ini_set('display_errors', 1);
  ```
- Print session contents:
  ```php
  print_r($_SESSION);
  ```

---

## 🧪 Manual Testing

- [ ] Signup & Login work  
- [ ] Session persists  
- [ ] CRUD operations successful  
- [ ] Validation messages display  
- [ ] Stats update dynamically  

---

## 🌍 Deployment

Free PHP hosting options:
- [000WebHost](https://www.000webhost.com)
- [InfinityFree](https://www.infinityfree.net)
- [AwardSpace](https://www.awardspace.com)

> Render and Railway require Docker for PHP — use the above for instant hosting.

---

## 📚 Learn More

- [PHP Docs](https://www.php.net/docs.php)
- [Twig Docs](https://twig.symfony.com/doc/3.x/)
- [Tailwind CSS](https://tailwindcss.com)
- [PHP The Right Way](https://phptherightway.com/)

---

**Built with PHP 8 • Styled with Tailwind • Inspired by Twig**

MIT © 2025 TicketFlow
