<?php
require_once '../includes/config.php';
requireCustomer();

$user_id = $_SESSION['user_id'];

// Get current profile
$stmt = $pdo->prepare("
    SELECT u.*, up.* 
    FROM Users u 
    JOIN UserProfile up ON u.user_id = up.user_id 
    WHERE u.user_id = ?
");
$stmt->execute([$user_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    $errors = [];
    
    // Validation
    if (empty($first_name)) $errors[] = "First name is required.";
    if (empty($last_name)) $errors[] = "Last name is required.";
    if (empty($email)) $errors[] = "Email is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
    
    // Check if email already exists (excluding current user)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM UserProfile WHERE email = ? AND user_id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = "Email already exists.";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE UserProfile 
                SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ? 
                WHERE user_id = ?
            ");
            $stmt->execute([$first_name, $last_name, $email, $phone, $address, $user_id]);
            $success = "Profile updated successfully!";
            
            // Refresh profile data
            $stmt->execute([$user_id]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $error = "Error updating profile: " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // Verify current password
    if ($current_password !== $profile['password']) {
        $errors[] = "Current password is incorrect.";
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = "New passwords do not match.";
    }
    
    if (strlen($new_password) < 6) {
        $errors[] = "New password must be at least 6 characters.";
    }
    
    if (empty($errors)) {
        try {
            // Update password (plain text for demo)
            $stmt = $pdo->prepare("UPDATE Users SET password = ? WHERE user_id = ?");
            $stmt->execute([$new_password, $user_id]);
            $password_success = "Password changed successfully!";
            
            // Refresh profile data
            $stmt = $pdo->prepare("SELECT * FROM Users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $password_error = "Error changing password: " . $e->getMessage();
        }
    } else {
        $password_error = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Online Bookstore</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .profile-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 30px;
        }
        
        @media (max-width: 992px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
        }
        
        .profile-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            border: 1px solid #e0e6ed;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .profile-section h3 {
            margin-top: 0;
            color: #2c3e50;
            padding-bottom: 15px;
            border-bottom: 2px solid #ecf0f1;
            margin-bottom: 25px;
        }
        
        .account-info-card {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            border: 1px solid #e0e6ed;
            margin-top: 30px;
        }
        
        .info-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .info-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .info-label {
            display: block;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .info-value {
            color: #555;
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 12px;
            padding: 3px 8px;
            border-radius: 3px;
            display: inline-block;
        }
        
        .strength-weak { background: #f8d7da; color: #721c24; }
        .strength-medium { background: #fff3cd; color: #856404; }
        .strength-strong { background: #d4edda; color: #155724; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Customer Dashboard</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="search.php">Search Books</a>
                <a href="cart.php">Cart (<?php echo getCartCount($pdo, $user_id); ?>)</a>
                <a href="orders.php">My Orders</a>
                <a href="profile.php">My Profile</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </header>
        
        <main>
            <h2>My Profile</h2>
            <p class="page-description">
                Update your personal information and manage your account settings.
            </p>
            
            <?php if (isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="profile-container">
                <!-- Personal Information Form -->
                <div class="profile-section">
                    <h3>Personal Information</h3>
                    <form method="POST" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name *</label>
                                <input type="text" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($profile['first_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="last_name">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($profile['last_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($profile['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($profile['phone']); ?>"
                                   placeholder="+1 (555) 123-4567">
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Shipping Address</label>
                            <textarea id="address" name="address" rows="4"><?php echo htmlspecialchars($profile['address']); ?></textarea>
                            <small class="form-text">This address will be used for order delivery.</small>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Password Change Form -->
                <div class="profile-section">
                    <h3>Change Password</h3>
                    
                    <?php if (isset($password_success)): ?>
                        <div class="success"><?php echo $password_success; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($password_error)): ?>
                        <div class="error"><?php echo $password_error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="current_password">Current Password *</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password *</label>
                            <input type="password" id="new_password" name="new_password" required
                                   oninput="checkPasswordStrength(this.value)">
                            <div id="password-strength" class="password-strength"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="change_password" class="btn btn-primary">
                                Change Password
                            </button>
                        </div>
                        
                        <div class="password-requirements" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ecf0f1;">
                            <p><strong>Password Requirements:</strong></p>
                            <ul style="margin: 10px 0 0 20px; color: #7f8c8d;">
                                <li>At least 6 characters long</li>
                                <li>Should not be too common</li>
                            </ul>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Account Information -->
            <div class="account-info-card">
                <h3>Account Information</h3>
                <div class="info-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    <div class="info-item">
                        <span class="info-label">Username:</span>
                        <span class="info-value"><?php echo htmlspecialchars($profile['username']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Account Type:</span>
                        <span class="info-value"><?php echo $profile['role']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Member Since:</span>
                        <span class="info-value"><?php echo date('F j, Y', strtotime($profile['created_at'] ?? 'now')); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Account Status:</span>
                        <span class="info-value" style="color: #27ae60; font-weight: bold;">Active</span>
                    </div>
                </div>
            </div>
            
            <!-- Danger Zone -->
            <div class="danger-zone" style="margin-top: 30px; padding: 25px; background: #f8d7da; border-radius: 10px; border: 1px solid #f5c6cb;">
                <h3 style="color: #721c24;">Danger Zone</h3>
                <p style="color: #721c24; margin-bottom: 15px;">These actions are irreversible. Please proceed with caution.</p>
                <div style="display: flex; gap: 15px; align-items: center;">
                    <form method="POST" action="delete_account.php" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
                        <button type="submit" class="btn btn-danger">Delete Account</button>
                    </form>
                    <span style="color: #721c24; font-size: 14px;">Permanently delete your account and all associated data.</span>
                </div>
            </div>
        </main>
    </div>
    
    <script>
    function checkPasswordStrength(password) {
        const strengthDiv = document.getElementById('password-strength');
        
        if (password.length === 0) {
            strengthDiv.textContent = '';
            strengthDiv.className = 'password-strength';
            return;
        }
        
        let strength = 0;
        let strengthText = '';
        let strengthClass = '';
        
        // Length check
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        
        // Complexity checks
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^a-zA-Z0-9]/.test(password)) strength++;
        
        if (strength <= 2) {
            strengthText = 'Weak';
            strengthClass = 'strength-weak';
        } else if (strength <= 4) {
            strengthText = 'Medium';
            strengthClass = 'strength-medium';
        } else {
            strengthText = 'Strong';
            strengthClass = 'strength-strong';
        }
        
        strengthDiv.textContent = `Password strength: ${strengthText}`;
        strengthDiv.className = `password-strength ${strengthClass}`;
    }
    
    // Confirm password match
    document.getElementById('confirm_password').addEventListener('input', function() {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = this.value;
        
        if (newPassword !== confirmPassword && confirmPassword.length > 0) {
            this.style.borderColor = '#e74c3c';
            this.style.boxShadow = '0 0 0 3px rgba(231, 76, 60, 0.1)';
        } else {
            this.style.borderColor = '#e0e6ed';
            this.style.boxShadow = 'none';
        }
    });
    
    document.getElementById('new_password').addEventListener('input', function() {
        const newPassword = this.value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (newPassword !== confirmPassword && confirmPassword.length > 0) {
            document.getElementById('confirm_password').style.borderColor = '#e74c3c';
            document.getElementById('confirm_password').style.boxShadow = '0 0 0 3px rgba(231, 76, 60, 0.1)';
        } else {
            document.getElementById('confirm_password').style.borderColor = '#e0e6ed';
            document.getElementById('confirm_password').style.boxShadow = 'none';
        }
    });
    </script>
</body>
</html>