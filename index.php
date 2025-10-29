<?php
/**
 * TicketFlow - Twig/PHP Implementation
 * Single-file application for demonstration purposes
 * In production, this would be split into multiple files
 */

session_start();

// Initialize session data structures if not exists
if (!isset($_SESSION['tickets'])) {
    $_SESSION['tickets'] = [];
}
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = null;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'signup':
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (empty($name) || empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                exit;
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Email is invalid']);
                exit;
            }
            
            if (strlen($password) < 6) {
                echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
                exit;
            }
            
            $_SESSION['user'] = [
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT)
            ];
            $_SESSION['authenticated'] = true;
            
            echo json_encode(['success' => true, 'message' => 'Account created successfully!']);
            exit;
            
        case 'login':
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                exit;
            }
            
            if (!isset($_SESSION['user']) || $_SESSION['user']['email'] !== $email) {
                echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
                exit;
            }
            
            if (!password_verify($password, $_SESSION['user']['password'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
                exit;
            }
            
            $_SESSION['authenticated'] = true;
            echo json_encode(['success' => true, 'message' => 'Login successful!']);
            exit;
            
        case 'logout':
            $_SESSION['authenticated'] = false;
            echo json_encode(['success' => true]);
            exit;
            
        case 'create_ticket':
            if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit;
            }
            
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $status = $_POST['status'] ?? 'open';
            $priority = $_POST['priority'] ?? 'medium';
            
            if (empty($title)) {
                echo json_encode(['success' => false, 'message' => 'Title is required']);
                exit;
            }
            
            if (!in_array($status, ['open', 'in_progress', 'closed'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid status']);
                exit;
            }
            
            $ticket = [
                'id' => time() . rand(1000, 9999),
                'title' => $title,
                'description' => $description,
                'status' => $status,
                'priority' => $priority
            ];
            
            $_SESSION['tickets'][] = $ticket;
            echo json_encode(['success' => true, 'message' => 'Ticket created successfully!', 'ticket' => $ticket]);
            exit;
            
        case 'update_ticket':
            if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit;
            }
            
            $id = $_POST['id'] ?? '';
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $status = $_POST['status'] ?? 'open';
            $priority = $_POST['priority'] ?? 'medium';
            
            if (empty($title)) {
                echo json_encode(['success' => false, 'message' => 'Title is required']);
                exit;
            }
            
            if (!in_array($status, ['open', 'in_progress', 'closed'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid status']);
                exit;
            }
            
            foreach ($_SESSION['tickets'] as $index => $ticket) {
                if ($ticket['id'] === $id) {
                    $_SESSION['tickets'][$index] = [
                        'id' => $id,
                        'title' => $title,
                        'description' => $description,
                        'status' => $status,
                        'priority' => $priority
                    ];
                    echo json_encode(['success' => true, 'message' => 'Ticket updated successfully!']);
                    exit;
                }
            }
            
            echo json_encode(['success' => false, 'message' => 'Ticket not found']);
            exit;
            
        case 'delete_ticket':
            if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit;
            }
            
            $id = $_POST['id'] ?? '';
            
            foreach ($_SESSION['tickets'] as $index => $ticket) {
                if ($ticket['id'] === $id) {
                    array_splice($_SESSION['tickets'], $index, 1);
                    echo json_encode(['success' => true, 'message' => 'Ticket deleted successfully!']);
                    exit;
                }
            }
            
            echo json_encode(['success' => false, 'message' => 'Ticket not found']);
            exit;
            
        case 'get_tickets':
            if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit;
            }
            
            echo json_encode(['success' => true, 'tickets' => $_SESSION['tickets']]);
            exit;
    }
}

// Determine current page
$page = $_GET['page'] ?? 'landing';
$isAuthenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'];

// Protect routes
if (in_array($page, ['dashboard', 'tickets']) && !$isAuthenticated) {
    $page = 'login';
}

// Calculate stats
$totalTickets = count($_SESSION['tickets']);
$openTickets = count(array_filter($_SESSION['tickets'], fn($t) => $t['status'] === 'open'));
$inProgressTickets = count(array_filter($_SESSION['tickets'], fn($t) => $t['status'] === 'in_progress'));
$closedTickets = count(array_filter($_SESSION['tickets'], fn($t) => $t['status'] === 'closed'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TicketFlow - Twig/PHP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes slide-in {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        .animate-slide-in {
            animation: slide-in 0.3s ease-out;
        }
    </style>
</head>
<body>
    <!-- Toast Container -->
    <div id="toast-container"></div>

    <?php if ($page === 'landing'): ?>
        <!-- Landing Page -->
        <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
            <!-- Header -->
            <header class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6" style="max-width: 1440px;">
                <nav class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-indigo-600">TicketFlow</h1>
                    <div class="hidden md:flex gap-4">
                        <a href="?page=login" class="px-6 py-2 text-indigo-600 hover:text-indigo-700 font-medium">Login</a>
                        <a href="?page=signup" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">Get Started</a>
                    </div>
                    <button onclick="toggleMobileMenu()" class="md:hidden">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </nav>
                <div id="mobile-menu" class="hidden md:hidden mt-4 flex flex-col gap-2">
                    <a href="?page=login" class="px-6 py-2 text-indigo-600 hover:text-indigo-700 font-medium text-left">Login</a>
                    <a href="?page=signup" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">Get Started</a>
                </div>
            </header>

            <!-- Hero Section -->
            <section class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 overflow-hidden" style="max-width: 1440px;">
                <div class="absolute top-10 right-10 w-64 h-64 bg-indigo-200 rounded-full opacity-50 blur-3xl"></div>
                <div class="absolute bottom-20 left-10 w-48 h-48 bg-purple-200 rounded-full opacity-40"></div>
                
                <div class="relative z-10 text-center max-w-3xl mx-auto">
                    <h2 class="text-5xl md:text-6xl font-bold text-gray-900 mb-6">
                        Manage Your Tickets <span class="text-indigo-600">Effortlessly</span>
                    </h2>
                    <p class="text-xl text-gray-600 mb-8">
                        Streamline your workflow with our powerful ticket management system. 
                        Track, organize, and resolve issues faster than ever before.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="?page=signup" class="px-8 py-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold text-lg shadow-lg">
                            Get Started Free
                        </a>
                        <a href="?page=login" class="px-8 py-4 bg-white text-indigo-600 rounded-lg hover:bg-gray-50 font-semibold text-lg shadow-lg">
                            Login to Your Account
                        </a>
                    </div>
                </div>

                <!-- Wave SVG -->
                <div class="absolute bottom-0 left-0 w-full">
                    <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0 120L60 105C120 90 240 60 360 45C480 30 600 30 720 37.5C840 45 960 60 1080 67.5C1200 75 1320 75 1380 75L1440 75V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0Z" fill="#4F46E5" fill-opacity="0.1"/>
                    </svg>
                </div>
            </section>

            <!-- Features Section -->
            <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20" style="max-width: 1440px;">
                <h3 class="text-3xl font-bold text-center mb-12">Why Choose TicketFlow?</h3>
                <div class="grid md:grid-cols-3 gap-8">
                    <div class="bg-white rounded-xl shadow-lg p-8 hover:shadow-xl transition-shadow">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <h4 class="text-xl font-semibold mb-3">Easy Tracking</h4>
                        <p class="text-gray-600">Keep track of all your tickets in one centralized dashboard with real-time updates.</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg p-8 hover:shadow-xl transition-shadow">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <h4 class="text-xl font-semibold mb-3">Smart Organization</h4>
                        <p class="text-gray-600">Categorize and prioritize tickets with customizable status labels and filters.</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg p-8 hover:shadow-xl transition-shadow">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <h4 class="text-xl font-semibold mb-3">Team Collaboration</h4>
                        <p class="text-gray-600">Work together seamlessly with your team to resolve issues faster.</p>
                    </div>
                </div>
            </section>

            <!-- Footer -->
            <footer class="bg-gray-900 text-white py-8 mt-20">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center" style="max-width: 1440px;">
                    <p>&copy; 2025 TicketFlow. All rights reserved.</p>
                </div>
            </footer>
        </div>

    <?php elseif ($page === 'login' || $page === 'signup'): ?>
        <!-- Auth Page -->
        <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center px-4">
            <div class="max-w-md w-full bg-white rounded-xl shadow-2xl p-8">
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">
                        <?php echo $page === 'login' ? 'Welcome Back' : 'Create Account'; ?>
                    </h2>
                    <p class="text-gray-600">
                        <?php echo $page === 'login' ? 'Login to access your tickets' : 'Sign up to get started'; ?>
                    </p>
                </div>

                <form id="auth-form" class="space-y-6">
                    <?php if ($page === 'signup'): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                            <input
                                type="text"
                                name="name"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                placeholder="John Doe"
                            />
                            <p class="text-red-500 text-sm mt-1 hidden" id="name-error"></p>
                        </div>
                    <?php endif; ?>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input
                            type="email"
                            name="email"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            placeholder="you@example.com"
                        />
                        <p class="text-red-500 text-sm mt-1 hidden" id="email-error"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <input
                            type="password"
                            name="password"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            placeholder="••••••••"
                        />
                        <p class="text-red-500 text-sm mt-1 hidden" id="password-error"></p>
                    </div>

                    <button
                        type="submit"
                        class="w-full py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold"
                    >
                        <?php echo $page === 'login' ? 'Login' : 'Sign Up'; ?>
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <a
                        href="?page=<?php echo $page === 'login' ? 'signup' : 'login'; ?>"
                        class="text-indigo-600 hover:text-indigo-700 font-medium"
                    >
                        <?php echo $page === 'login' ? "Don't have an account? Sign up" : 'Already have an account? Login'; ?>
                    </a>
                </div>

                <div class="mt-4 text-center">
                    <a href="?page=landing" class="text-gray-600 hover:text-gray-700">Back to Home</a>
                </div>
            </div>
        </div>

        <script>
            document.getElementById('auth-form').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('action', '<?php echo $page; ?>');
                
                // Clear errors
                document.querySelectorAll('[id$="-error"]').forEach(el => {
                    el.textContent = '';
                    el.classList.add('hidden');
                });
                
                try {
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => {
                            window.location.href = '?page=dashboard';
                        }, 1000);
                    } else {
                        showToast(data.message, 'error');
                    }
                } catch (error) {
                    showToast('An error occurred. Please try again.', 'error');
                }
            });
        </script>

    <?php elseif ($page === 'dashboard'): ?>
        <!-- Dashboard Page -->
        <div class="min-h-screen bg-gray-50">
            <!-- Header -->
            <header class="bg-white shadow-sm">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4" style="max-width: 1440px;">
                    <div class="flex items-center justify-between">
                        <h1 class="text-2xl font-bold text-indigo-600">TicketFlow</h1>
                        <button onclick="handleLogout()" class="flex items-center gap-2 px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            Logout
                        </button>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" style="max-width: 1440px;">
                <div class="mb-8">
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Dashboard</h2>
                    <p class="text-gray-600">Overview of your ticket management system</p>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                        <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <p class="text-gray-600 text-sm mb-1">Total Tickets</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $totalTickets; ?></p>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                        <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <p class="text-gray-600 text-sm mb-1">Open</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $openTickets; ?></p>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                        <div class="w-12 h-12 bg-amber-500 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <p class="text-gray-600 text-sm mb-1">In Progress</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $inProgressTickets; ?></p>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                        <div class="w-12 h-12 bg-gray-500 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <p class="text-gray-600 text-sm mb-1">Closed</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $closedTickets; ?></p>
                    </div>
                </div>

                <!-- Action Button -->
                <div class="bg-white rounded-xl shadow-lg p-8">
                    <h3 class="text-xl font-semibold mb-4">Quick Actions</h3>
                    <a href="?page=tickets" class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        Manage Tickets
                    </a>
                </div>
            </main>

            <!-- Footer -->
            <footer class="bg-gray-900 text-white py-8 mt-20">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center" style="max-width: 1440px;">
                    <p>&copy; 2025 TicketFlow. All rights reserved.</p>
                </div>
            </footer>
        </div>

    <?php elseif ($page === 'tickets'): ?>
        <!-- Ticket Management Page -->
        <div class="min-h-screen bg-gray-50">
            <!-- Header -->
            <header class="bg-white shadow-sm">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4" style="max-width: 1440px;">
                    <div class="flex items-center justify-between">
                        <h1 class="text-2xl font-bold text-indigo-600">TicketFlow</h1>
                        <div class="flex items-center gap-4">
                            <a href="?page=dashboard" class="flex items-center gap-2 px-4 py-2 text-indigo-600 hover:bg-indigo-50 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                Dashboard
                            </a>
                            <button onclick="handleLogout()" class="flex items-center gap-2 px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                Logout
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" style="max-width: 1440px;">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900 mb-2">Ticket Management</h2>
                        <p class="text-gray-600">Create, view, edit, and delete your tickets</p>
                    </div>
                    <button onclick="toggleForm()" class="flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        New Ticket
                    </button>
                </div>

                <!-- Form -->
                <div id="ticket-form" class="bg-white rounded-xl shadow-lg p-8 mb-8 hidden">
                    <h3 class="text-xl font-semibold mb-6" id="form-title">Create New Ticket</h3>
                    <form id="ticket-form-element" class="space-y-6">
                        <input type="hidden" name="ticket-id" id="ticket-id">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Title <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                name="title"
                                id="ticket-title"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                placeholder="Enter ticket title"
                            />
                            <p class="text-red-500 text-sm mt-1 hidden" id="title-error"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea
                                name="description"
                                id="ticket-description"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                rows="4"
                                placeholder="Enter ticket description"
                            ></textarea>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Status <span class="text-red-500">*</span>
                                </label>
                                <select
                                    name="status"
                                    id="ticket-status"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                >
                                    <option value="open">Open</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="closed">Closed</option>
                                </select>
                                <p class="text-red-500 text-sm mt-1 hidden" id="status-error"></p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                                <select
                                    name="priority"
                                    id="ticket-priority"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                >
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <button
                                type="submit"
                                class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold"
                            >
                                <span id="submit-btn-text">Create Ticket</span>
                            </button>
                            <button
                                type="button"
                                onclick="resetForm()"
                                class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-semibold"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tickets List -->
                <div id="tickets-list" class="grid gap-6">
                    <?php if (empty($_SESSION['tickets'])): ?>
                        <div class="bg-white rounded-xl shadow-lg p-12 text-center">
                            <svg class="mx-auto mb-4 text-gray-400 w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">No tickets yet</h3>
                            <p class="text-gray-600">Create your first ticket to get started</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($_SESSION['tickets'] as $ticket): ?>
                            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow" data-ticket-id="<?php echo $ticket['id']; ?>">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1">
                                        <h3 class="text-xl font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($ticket['title']); ?></h3>
                                        <p class="text-gray-600 mb-4"><?php echo !empty($ticket['description']) ? htmlspecialchars($ticket['description']) : 'No description provided'; ?></p>
                                        <div class="flex flex-wrap gap-2">
                                            <span class="px-3 py-1 rounded-full text-sm font-medium <?php 
                                                echo $ticket['status'] === 'open' ? 'bg-green-100 text-green-800' : 
                                                    ($ticket['status'] === 'in_progress' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-800'); 
                                            ?>">
                                                <?php echo strtoupper(str_replace('_', ' ', $ticket['status'])); ?>
                                            </span>
                                            <span class="px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                                <?php echo strtoupper($ticket['priority']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex gap-2 ml-4">
                                        <button
                                            onclick='editTicket(<?php echo json_encode($ticket); ?>)'
                                            class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg"
                                            title="Edit"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        <button
                                            onclick="deleteTicket('<?php echo $ticket['id']; ?>')"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg"
                                            title="Delete"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>

            <!-- Footer -->
            <footer class="bg-gray-900 text-white py-8 mt-20">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center" style="max-width: 1440px;">
                    <p>&copy; 2025 TicketFlow. All rights reserved.</p>
                </div>
            </footer>
        </div>

        <script>
            let editingTicketId = null;

            function toggleForm() {
                const form = document.getElementById('ticket-form');
                form.classList.toggle('hidden');
            }

            function resetForm() {
                document.getElementById('ticket-form-element').reset();
                document.getElementById('ticket-id').value = '';
                document.getElementById('form-title').textContent = 'Create New Ticket';
                document.getElementById('submit-btn-text').textContent = 'Create Ticket';
                document.getElementById('ticket-form').classList.add('hidden');
                editingTicketId = null;
                
                document.querySelectorAll('[id$="-error"]').forEach(el => {
                    el.textContent = '';
                    el.classList.add('hidden');
                });
            }

            function editTicket(ticket) {
                editingTicketId = ticket.id;
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

                document.querySelectorAll('[id$="-error"]').forEach(el => {
                    el.textContent = '';
                    el.classList.add('hidden');
                });

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
        </script>
    <?php endif; ?>

    <!-- Common JavaScript -->
    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            if (menu) {
                menu.classList.toggle('hidden');
            }
        }

        async function handleLogout() {
            const formData = new FormData();
            formData.append('action', 'logout');

            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = '?page=landing';
                }
            } catch (error) {
                console.error('Logout error:', error);
            }
        }

        function showToast(message, type) {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 z-50 flex items-center gap-2 px-4 py-3 rounded-lg shadow-lg text-white animate-slide-in ${
                type === 'success' ? 'bg-green-500' : 'bg-red-500'
            }`;
            
            const icon = type === 'success' 
                ? '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>'
                : '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
            
            toast.innerHTML = `
                ${icon}
                <span>${message}</span>
                <button onclick="this.parentElement.remove()" class="ml-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
            
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    </script>
</body>
</html>