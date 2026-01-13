<?php
session_start();
require_once '../config/dbcon.php';

// Check if user is logged in as admin
function isAdminLoggedIn() {
    return isset($_SESSION['admin_username']) && !empty($_SESSION['admin_username']);
}

// Admin Authentication Check
if (!isAdminLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Function to get setting value
function getSetting($key, $default = '') {
    global $conn;
    $stmt = $conn->prepare("SELECT setting_value FROM homepage_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['setting_value'];
    }
    return $default;
}

// Handle form submission
$message = '';
$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid form submission. Please try again.";
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } else {
        // Regenerate token after use
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $settings = [
            'hero_title', 'hero_subtitle',
            'hero_background', 'hero_background_image',
            'navbar_background', 'navbar_text_color', 'navbar_university_text',
            'footer_university_text', 'footer_copyright'
        ];

        foreach ($settings as $setting) {
            if (isset($_POST[$setting])) {
                $value = trim($_POST[$setting]);
                $stmt = $conn->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->bind_param("sss", $setting, $value, $value);
                $stmt->execute();
            }
        }

        // Handle logo upload
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['logo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                $new_filename = 'logo_' . time() . '.' . $ext;
                $upload_path = '../uploads/' . $new_filename;

                // Create uploads directory if it doesn't exist
                if (!file_exists('../uploads/')) {
                    mkdir('../uploads/', 0777, true);
                }

                if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                    $stmt = $conn->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES ('logo_url', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                    $stmt->bind_param("ss", $new_filename, $new_filename);
                    $stmt->execute();
                }
            }
        }

        // Handle hero background image upload
        if (isset($_FILES['hero_background_image']) && $_FILES['hero_background_image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['hero_background_image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                $new_filename = 'hero_bg_' . time() . '.' . $ext;
                $upload_path = '../uploads/' . $new_filename;

                if (move_uploaded_file($_FILES['hero_background_image']['tmp_name'], $upload_path)) {
                    $stmt = $conn->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES ('hero_background_image', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                    $stmt->bind_param("ss", $new_filename, $new_filename);
                    $stmt->execute();
                }
            }
        }

        $message = "Homepage settings updated successfully!";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Homepage - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f5f7fa;
            color: #333;
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1rem 2rem;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar h1 {
            font-size: 1.5rem;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .navbar a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .form-section {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .file-input {
            padding: 0.5rem;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .message {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .preview-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 2rem;
            margin-top: 2rem;
        }

        .preview-title {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .color-preview {
            display: inline-block;
            width: 50px;
            height: 50px;
            border-radius: 5px;
            border: 2px solid #ddd;
            margin-left: 1rem;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1><i class="fas fa-edit"></i> Edit Homepage</h1>
        <a href="adminpage.php"><i class="fas fa-arrow-left"></i> Back to Admin Panel</a>
    </nav>

    <div class="container">
        <?php if($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <!-- Logo Upload -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-image"></i> Logo</h2>

                <div class="form-group">
                    <label for="logo">Upload Logo</label>
                    <input type="file" id="logo" name="logo" accept="image/*" class="file-input">
                </div>

                <?php $logo_url = getSetting('logo_url'); ?>
                <?php if($logo_url): ?>
                <div class="form-group">
                    <label>Navbar Preview:</label>
                    <div style="background: <?php echo getSetting('navbar_background', 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'); ?>; padding: 1rem 2rem; display: flex; align-items: center; gap: 12px; border-radius: 10px; max-width: 400px;">
                        <img src="../uploads/<?php echo htmlspecialchars($logo_url); ?>" alt="Logo" style="height: 40px; width: auto;">
                        <span id="logo_preview_university_text" style="color: <?php echo getSetting('navbar_text_color', 'white'); ?>; font-weight: 700; font-size: 1.3rem;"><?php echo htmlspecialchars(getSetting('navbar_university_text', 'University')); ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Navbar Settings -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-bars"></i> Navbar Settings</h2>

                <div class="form-group">
                    <label for="navbar_university_text">University Name (in Navbar)</label>
                    <input type="text" id="navbar_university_text" name="navbar_university_text" value="<?php echo htmlspecialchars(getSetting('navbar_university_text', 'University')); ?>" placeholder="Enter the university name">
                    <p style="margin-top: 0.5rem; font-size: 0.9rem; color: #666;">This text appears next to the logo in the navbar</p>
                </div>

                <div class="form-group">
                    <label for="navbar_background">Navbar Background (CSS gradient or color)</label>
                    <input type="text" id="navbar_background" name="navbar_background" value="<?php echo htmlspecialchars(getSetting('navbar_background', 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)')); ?>" placeholder="e.g., linear-gradient(135deg, #667eea 0%, #764ba2 100%) or #667eea">
                    <p style="margin-top: 0.5rem; font-size: 0.9rem; color: #666;">Examples: <code>linear-gradient(135deg, #667eea 0%, #764ba2 100%)</code> or <code>#667eea</code></p>
                </div>

                <div class="form-group">
                    <label for="navbar_text_color">Navbar Text Color</label>
                    <input type="color" id="navbar_text_color" name="navbar_text_color" value="<?php echo htmlspecialchars(getSetting('navbar_text_color', '#ffffff')); ?>">
                    <p style="margin-top: 0.5rem; font-size: 0.9rem; color: #666;">Choose the text color for the navbar</p>
                </div>

                <div class="form-group">
                    <label>Preview:</label>
                    <div id="navbar_preview" style="background: <?php echo htmlspecialchars(getSetting('navbar_background', 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)')); ?>; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; border-radius: 10px; max-width: 400px;">
                        <span id="navbar_university_preview" style="color: <?php echo htmlspecialchars(getSetting('navbar_text_color', '#ffffff')); ?>; font-weight: 700; font-size: 1.3rem;"><?php echo htmlspecialchars(getSetting('navbar_university_text', 'University')); ?></span>
                        <a href="#" style="color: <?php echo htmlspecialchars(getSetting('navbar_text_color', '#ffffff')); ?>; text-decoration: none;">Menu</a>
                    </div>
                </div>
            </div>

            <!-- Hero Section -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-star"></i> Hero Section</h2>

                <div class="form-group">
                    <label for="hero_title">Hero Title</label>
                    <input type="text" id="hero_title" name="hero_title" value="<?php echo htmlspecialchars(getSetting('hero_title')); ?>" >
                </div>

                <div class="form-group">
                    <label for="hero_subtitle">Hero Subtitle</label>
                    <input type="text" id="hero_subtitle" name="hero_subtitle" value="<?php echo htmlspecialchars(getSetting('hero_subtitle')); ?>">
                </div>
            </div>

            <!-- Background Settings -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-image"></i> Background</h2>

                <div class="form-group">
                    <label for="hero_background_image">Hero Background Image</label>
                    <input type="file" id="hero_background_image" name="hero_background_image" accept="image/*" class="file-input">
                    <?php $hero_bg_image = getSetting('hero_background_image'); ?>
                    <?php if($hero_bg_image): ?>
                        <p style="margin-top: 0.5rem;">Current: <img src="../uploads/<?php echo htmlspecialchars($hero_bg_image); ?>" style="height: 60px; border-radius: 5px; vertical-align: middle;"></p>
                    <?php endif; ?>
                </div>

            
            </div>

            <!-- Footer -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-copyright"></i> Footer</h2>

                <div class="form-group">
                    <label for="footer_university_text">University Name (in Footer)</label>
                    <input type="text" id="footer_university_text" name="footer_university_text" value="<?php echo htmlspecialchars(getSetting('footer_university_text', 'University')); ?>" placeholder="Enter the university name">
                    <p style="margin-top: 0.5rem; font-size: 0.9rem; color: #666;">This text appears before the copyright notice in the footer</p>
                </div>

                <div class="form-group">
                    <label for="footer_copyright">Copyright Text</label>
                    <input type="text" id="footer_copyright" name="footer_copyright" value="<?php echo htmlspecialchars(getSetting('footer_copyright')); ?>" >
                </div>
            </div>

            <div class="form-section">
                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>

        <div class="preview-section">
            <h3 class="preview-title"><i class="fas fa-eye"></i> Preview</h3>
            <p><a href="../index.php" target="_blank" class="btn" style="background: #28a745;">View Homepage</a></p>
        </div>
    </div>

    <script>
        // Update navbar background preview
        document.getElementById('navbar_background').addEventListener('input', function() {
            var preview = document.getElementById('navbar_preview');
            if (preview) {
                preview.style.background = this.value;
            }
        });

        // Update navbar text color preview
        document.getElementById('navbar_text_color').addEventListener('input', function() {
            var preview = document.getElementById('navbar_preview');
            if (preview) {
                var spans = preview.querySelectorAll('span, a');
                for (var i = 0; i < spans.length; i++) {
                    spans[i].style.color = this.value;
                }
            }
        });

        // Update navbar university text preview
        document.getElementById('navbar_university_text').addEventListener('input', function() {
            var preview = document.getElementById('navbar_university_preview');
            if (preview) {
                preview.textContent = this.value || 'University';
            }
            // Also update logo preview if exists
            var logoPreview = document.getElementById('logo_preview_university_text');
            if (logoPreview) {
                logoPreview.textContent = this.value || 'University';
            }
        });
    </script>
<?php $conn->close(); ?>
</body>
</html>
