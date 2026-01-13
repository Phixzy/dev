<!DOCTYPE html>
<html lang="en">
<?php session_start(); ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Enrollment</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    <link rel="stylesheet" href="enroll_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Username input wrapper styles */
        .username-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .username-input-wrapper input {
            padding-right: 80px;
        }
        
        .username-suffix {
            position: absolute;
            right: 12px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.9rem;
            pointer-events: none;
        }
        
        .username-feedback {
            margin-top: 6px;
            min-height: 20px;
            font-size: 0.85rem;
        }
        
        .username-feedback.available {
            color: #51cf66;
        }
        
        .username-feedback.taken {
            color: #ff6b6b;
        }
        
        .username-feedback.checking {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .username-feedback i {
            margin-right: 5px;
        }
        
        .username-feedback.available i {
            color: #51cf66;
        }
        
        .username-feedback.taken i {
            color: #ff6b6b;
        }
        
        .username-feedback.checking i {
            color: #ffd43b;
        }
        
        /* Password Toggle Styles */
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .password-wrapper input {
            padding-right: 45px;
            width: 100%;
        }
        
        .password-wrapper .fas,
        .password-wrapper .fa-eye,
        .password-wrapper .fa-eye-slash {
            position: absolute;
            right: 12px;
            color: rgba(255, 255, 255, 0.6);
            cursor: pointer;
            font-size: 1.1rem;
            transition: color 0.3s ease;
            z-index: 10;
        }
        
        .password-wrapper .fas:hover,
        .password-wrapper .fa-eye:hover,
        .password-wrapper .fa-eye-slash:hover {
            color: rgba(255, 255, 255, 0.9);
        }
    </style>
</head>
<body>
    <header class="glass-header">
        <div class="header-content">
            <div class="logo">
                <i class="uil uil-graduation-cap"></i>
                <h1>University</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="../index.php"><i class="uil uil-estate"></i>Home</a></li>
                    <li><a href="enroll.php" class="active"><i class="uil uil-user-plus"></i>Enroll Now</a></li>
                    <li><a href="../public_contact.php"><i class="uil uil-phone"></i>Contact</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="hero">
        <div class="hero-content">
            <?php
            // Display messages
            if (isset($_SESSION['enrollment_message'])) {
                $type = $_SESSION['enrollment_message_type'] ?? 'error';
                $class = $type === 'success' ? 'success-message' : 'error-message';
                echo '<div class="global-message ' . $class . '" style="margin-bottom: 1rem;">';
                echo '<i class="fas fa-' . ($type === 'success' ? 'check-circle' : 'exclamation-circle') . '"></i> ';
                echo htmlspecialchars($_SESSION['enrollment_message']);
                echo '</div>';
                unset($_SESSION['enrollment_message']);
                unset($_SESSION['enrollment_message_type']);
            }
            ?>
            <div class="glass-container main-form">
                <form action="addStudent.php" method="post" enctype="multipart/form-data">
                    <!-- Hidden input for selected appointment -->
                    <input type="hidden" name="selected_appointment" id="selected_appointment">
                    <input type="hidden" name="appointment_display" id="appointment_display">
            
                    <!-- Appointment Schedule Section -->
                    <div class="form-section glass-card">
                        <h2><i class="uil uil-calendar-alt"></i>Select Your Appointment Schedule</h2>
                        <p style="color: rgba(255,255,255,0.8); margin-bottom: 1rem; font-size: 0.9rem;">Choose your preferred appointment time for enrollment consultation</p>
                        <div class="appointment-slots" id="appointmentSlots">
                            <!-- Dynamic appointment slots will be loaded here -->
                            <div class="loading-message" style="text-align: center; color: rgba(255,255,255,0.7); padding: 2rem;">
                                <i class="uil uil-spinner-alt" style="font-size: 2rem; animation: spin 1s linear infinite;"></i>
                                <p>Loading available appointments...</p>
                            </div>
                        </div>
                    </div>

                    <div class="form-grid">
                        <!-- Student Portal Creation -->
                        <div class="form-section glass-card">
                            <h2><i class="uil uil-user-plus"></i>Create Your Student Portal</h2>
                            <div class="input-group">
                                <label for="username">Username</label>
                                <div class="username-input-wrapper">
                                    <input type="text" name="username" id="username" placeholder="Enter username" required minlength="3" maxlength="50" oninput="checkUsernameAvailability()" onblur="checkUsernameAvailability()">
                                    <span class="username-suffix">@student</span>
                                </div>
                                <div id="username-feedback" class="username-feedback"></div>
                                <small style="color: rgba(255,255,255,0.6); font-size: 0.75rem;">Username will be: <span id="username-preview">yourname@student</span></small>
                            </div>
                            <div class="input-group">
                                <label for="password">Password</label>
                                <div class="password-wrapper">
                                    <input type="password" name="password" id="enrollPassword" placeholder="Enter password" required autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly');">
                                    <i class="fas fa-eye" id="toggleEnrollPassword" onclick="togglePassword('enrollPassword', 'toggleEnrollPassword')"></i>
                                </div>
                            </div>
                            <div class="input-group">
                                <label for="email">Email</label>
                                <input type="email" name="email" id="email" placeholder="Enter email address" required>
                            </div>
                            <div class="input-group">
                                <label for="image">Profile Image</label>
                                <input type="file" name="image" id="image" accept="image/*" required>
                            </div>
                        </div>

                        <!-- Personal Information -->
                        <div class="form-section glass-card">
                            <h2><i class="uil uil-user"></i>Personal Information</h2>
                            <div class="input-row">
                                <div class="input-group">
                                    <label for="first_name">First Name</label>
                                    <input type="text" name="first_name" id="first_name" placeholder="Enter first name" required>
                                </div>
                                <div class="input-group">
                                    <label for="last_name">Last Name</label>
                                    <input type="text" name="last_name" id="last_name" placeholder="Enter last name" required>
                                </div>
                    
                            </div>
                            <div class="input-row">
                                <div class="input-group">
                                    <label for="date_of_birth">Date of Birth</label>
                                    <input type="date" name="date_of_birth" id="date_of_birth" required>
                                </div>
                                <div class="input-group">
                                    <label for="gender">Gender</label>
                                    <select name="gender" id="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="input-group">
                                <label for="student_phone">Student Phone (9XXXXXXXXXX)</label>
                                <input type="tel" name="student_phone" id="student_phone" maxlength="11" placeholder="0912345678"  title="Please enter a valid 11-digit phone number starting with 9">
                            </div>
                            <div class="input-group">
                                <label for="address">Student Address</label>
                                <input type="text" name="address" id="address" placeholder="Enter complete address" required>
                            </div>
                            <div class="input-group">
                                <label for="guardian_name">Guardian Name</label>
                                <input type="text" name="guardian_name" id="guardian_name" placeholder="Enter guardian name" required>
                            </div>
                            <div class="input-group">
                                <label for="guardian_phone">Guardian Phone (9XXXXXXXXXX)</label>
                                <input type="tel" name="guardian_phone" id="guardian_phone" maxlength="11" placeholder="9123456789" title="Please enter a valid 11-digit phone number starting with 9">
                            </div>
                            <div class="input-group">
                                <label for="guardian_address">Guardian Address</label>
                                <input type="text" name="guardian_address" id="guardian_address" placeholder="Enter guardian address" required>
                            </div>
                        </div>
                    </div>

                    <!-- Educational Attainment -->
                    <div class="form-section glass-card">
                        <h2><i class="uil uil-book-open"></i>Educational Attainment</h2>
                        <div class="education-grid">
                            <div class="education-level">
                                <h3>Elementary</h3>
                                <div class="input-group">
                                    <label for="elem_name">School Name</label>
                                    <input type="text" name="elem_name" id="elem_name" placeholder="Enter elementary school name" required>
                                </div>
                                <div class="input-group">
                                    <label for="elem_year">Year Graduated</label>
                                    <input type="text" name="elem_year" id="elem_year" maxlength="4"placeholder="e.g., 2016" required>
                                </div>
                            </div>

                            <div class="education-level">
                                <h3>Junior High</h3>
                                <div class="input-group">
                                    <label for="junior_name">School Name</label>
                                    <input type="text" name="junior_name" id="junior_name" placeholder="Enter junior high school name" required>
                                </div>
                                <div class="input-group">
                                    <label for="junior_year">Year Graduated</label>
                                    <input type="text" name="junior_year" id="junior_year" maxlength="4" placeholder="e.g., 2018" required>
                                </div>
                            </div>

                            <div class="education-level">
                                <h3>Senior High</h3>
                                <div class="input-group">
                                    <label for="senior_name">School Name</label>
                                    <input type="text" name="senior_name" id="senior_name" placeholder="Enter senior high school name" required>
                                </div>
                                <div class="input-group">
                                    <label for="senior_year">Year Graduated</label>
                                    <input type="text" name="senior_year" id="senior_year" maxlength="4" placeholder="e.g., 2023" required>
                                </div>
                                <div class="input-group">
                                    <label for="strand">Strand</label>
                                    <select name="strand" id="strand" required>
                                        <option value="">Select Strand</option>
                                        <option value="STEM">STEM</option>
                                        <option value="ABM">ABM</option>
                                        <option value="HUMSS">HUMSS</option>
                                        <option value="GAS">GAS</option>
                                        <option value="TVL">TVL</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Student's Enrollment -->
                    <div class="form-section glass-card">
                        <h2><i class="uil uil-book-open"></i>Student's Enrollment</h2>
                        <div class="form-grid">
                            <div class="input-group">
                                <label for="college_course">Choose Your Course</label>
                                <select name="college_course" id="college_course" required>
                                    <option value="">Select Course</option>
                                    <option value="BS Computer Science">BS Computer Science</option>
                                    <option value="BS Information Technology">BS Information Technology</option>
                                    <option value="BS Computer Engineering">BS Computer Engineering</option>
                                </select>
                            </div>
                            <div class="input-group">
                                <label for="college_year">Year Level</label>
                                <select name="college_year" id="college_year" required>
                                    <option value="">Select Year Level</option>
                                    <option value="1st Year">1st Year</option>
                                    <option value="2nd Year">2nd Year</option>
                                    <option value="3rd Year">3rd Year</option>
                                    <option value="4th Year">4th Year</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Section -->
                    <div class="submit-section glass-card">
                        <button type="submit" name="enroll" class="submit-btn">
                            <i class="uil uil-check-circle"></i>
                            Submit Enrollment Application
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <script>
        // Load appointment slots when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadAppointmentSlots();
        });

        function loadAppointmentSlots() {
            const slotsContainer = document.getElementById('appointmentSlots');
            
            // Show loading state
            slotsContainer.innerHTML = `
                <div class="loading-message" style="text-align: center; color: rgba(255,255,255,0.7); padding: 2rem;">
                    <i class="uil uil-spinner-alt" style="font-size: 2rem; animation: spin 1s linear infinite;"></i>
                    <p>Loading available appointments...</p>
                </div>
            `;

            // Fetch appointment data from server
            fetch('getAppointments.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.appointments.length > 0) {
                        displayAppointmentSlots(data.appointments);
                    } else {
                        displayNoAppointments();
                    }
                })
                .catch(error => {
                    console.error('Error loading appointments:', error);
                    displayErrorMessage();
                });
        }

        function displayAppointmentSlots(appointments) {
            const slotsContainer = document.getElementById('appointmentSlots');
            
            const slotsHTML = appointments.map(appointment => {
                const date = new Date(appointment.appointment_date);
                const startTime = appointment.start_time;
                const endTime = appointment.end_time;
                const availableSlots = appointment.available_slots;
                const totalSlots = appointment.total_slots;
                
                const formattedDate = date.toLocaleDateString('en-US', { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
                
                const formattedTime = `${startTime} - ${endTime}`;
                
                // Determine availability status
                let availabilityClass = 'available';
                let availabilityText = `${availableSlots} slots available`;
                let availabilityIcon = 'uil-check-circle';
                
                if (availableSlots === 0) {
                    availabilityClass = 'full';
                    availabilityText = 'Fully Booked';
                    availabilityIcon = 'uil-times-circle';
                } else if (availableSlots <= 3) {
                    availabilityClass = 'low';
                    availabilityText = `Only ${availableSlots} slots left!`;
                    availabilityIcon = 'uil-exclamation-triangle';
                }
                
                return `
                    <div class="slot-card" data-appointment-id="${appointment.id}">
                        <div class="slot-date">${formattedDate}</div>
                        <div class="slot-time">${formattedTime}</div>
                        <div class="slot-availability ${availabilityClass}">
                            <i class="uil ${availabilityIcon}"></i>
                            ${availabilityText}
                        </div>
                        ${availableSlots > 0 ? '<button type="button" class="select-slot-btn" onclick="selectSlot(this)">Select This Slot</button>' : '<button type="button" class="select-slot-btn" disabled>Fully Booked</button>'}
                    </div>
                `;
            }).join('');
            
            slotsContainer.innerHTML = slotsHTML;
            
            // Add animation to slots
            const slots = slotsContainer.querySelectorAll('.slot-card');
            slots.forEach((slot, index) => {
                slot.style.opacity = '0';
                slot.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    slot.style.transition = 'all 0.5s ease';
                    slot.style.opacity = '1';
                    slot.style.transform = 'translateY(0)';
                }, index * 100);
            });
        }

        function displayNoAppointments() {
            const slotsContainer = document.getElementById('appointmentSlots');
            slotsContainer.innerHTML = `
                <div class="no-appointments-message" style="text-align: center; color: rgba(255,255,255,0.7); padding: 3rem; background: rgba(255,255,255,0.05); border-radius: 12px; border: 1px solid rgba(255,255,255,0.1);">
                    <i class="uil uil-calendar-times" style="font-size: 3rem; color: rgba(255,255,255,0.5); margin-bottom: 1rem;"></i>
                    <h3 style="color: white; margin-bottom: 0.5rem;">No Appointments Available</h3>
                    <p>There are currently no appointment slots available. Please check back later or contact our office for assistance.</p>
                    <button type="button" onclick="loadAppointmentSlots()" class="retry-btn" style="margin-top: 1rem; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; transition: all 0.3s ease;">
                        <i class="uil uil-refresh"></i> Refresh
                    </button>
                </div>
            `;
        }

        function displayErrorMessage() {
            const slotsContainer = document.getElementById('appointmentSlots');
            slotsContainer.innerHTML = `
                <div class="error-message" style="text-align: center; color: rgba(255,107,107,0.8); padding: 3rem; background: rgba(255,107,107,0.1); border-radius: 12px; border: 1px solid rgba(255,107,107,0.3);">
                    <i class="uil uil-exclamation-triangle" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                    <h3 style="color: #ff6b6b; margin-bottom: 0.5rem;">Error Loading Appointments</h3>
                    <p>Unable to load appointment slots. Please try again later.</p>
                    <button type="button" onclick="loadAppointmentSlots()" class="retry-btn" style="margin-top: 1rem; background: rgba(255,107,107,0.2); border: 1px solid rgba(255,107,107,0.4); color: #ff6b6b; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; transition: all 0.3s ease;">
                        <i class="uil uil-refresh"></i> Try Again
                    </button>
                </div>
            `;
        }

        function selectSlot(button) {
            // Remove selection from all slots
            const allSlots = document.querySelectorAll('.slot-card');
            allSlots.forEach(slot => {
                slot.classList.remove('selected');
            });
            
            // Select the clicked slot
            const slotCard = button.closest('.slot-card');
            slotCard.classList.add('selected');
            
            // Store the appointment ID and formatted text
            const appointmentId = slotCard.getAttribute('data-appointment-id');
            const timeText = slotCard.querySelector('.slot-time')?.textContent || '';
            const dateText = slotCard.querySelector('.slot-date')?.textContent || '';
            
            // Update hidden inputs
            document.getElementById('selected_appointment').value = appointmentId;
            document.getElementById('appointment_display').value = dateText + ' - ' + timeText;
            
            // Add visual feedback
            button.innerHTML = '<i class="uil uil-check"></i> Selected';
            button.style.background = 'rgba(81, 207, 102, 0.3)';
            button.style.borderColor = '#51cf66';
            
            // Reset other buttons
            setTimeout(() => {
                const allButtons = document.querySelectorAll('.select-slot-btn');
                allButtons.forEach(btn => {
                    if (btn !== button) {
                        btn.innerHTML = 'Select This Slot';
                        btn.style.background = '';
                        btn.style.borderColor = '';
                    }
                });
            }, 100);
        }

        // Form validation with enhanced UX
        document.querySelector('form').addEventListener('submit', function(event) {
            const selectedAppointment = document.getElementById('selected_appointment').value;
            const submitBtn = document.querySelector('.submit-btn');
            
            if (!selectedAppointment) {
                event.preventDefault();
                
                // Scroll to appointment section
                document.getElementById('appointmentSlots').scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
                
                // Show warning
                showNotification('Please select an appointment schedule before submitting.', 'warning');
                return false;
            }
            
            // Add loading state to submit button
            submitBtn.innerHTML = '<i class="uil uil-spinner-alt" style="animation: spin 1s linear infinite;"></i> Submitting...';
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.7';
        });

        // Enhanced notification system
        function showNotification(message, type = 'info') {
            // Remove existing notifications
            const existingNotifications = document.querySelectorAll('.notification');
            existingNotifications.forEach(notif => notif.remove());
            
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <i class="uil ${type === 'warning' ? 'uil-exclamation-triangle' : 'uil-info-circle'}"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.remove()" style="background: none; border: none; color: inherit; cursor: pointer; margin-left: 1rem;">
                    <i class="uil uil-times"></i>
                </button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }

        // Real-time form validation
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input[required], select[required]');
            
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    validateField(this);
                });
                
                input.addEventListener('input', function() {
                    if (this.classList.contains('error')) {
                        validateField(this);
                    }
                });
            });
        });

        function validateField(field) {
            const value = field.value.trim();
            let isValid = true;
            let message = '';
            
            // Remove existing error styling
            field.classList.remove('error', 'success');
            
            if (field.hasAttribute('required') && !value) {
                isValid = false;
                message = 'This field is required';
            } else if (field.type === 'email' && value && !isValidEmail(value)) {
                isValid = false;
                message = 'Please enter a valid email address';
            } else if (field.name === 'student_phone' && value && !isValidPhone(value)) {
                isValid = false;
                message = 'Please enter a valid 11-digit phone number starting with 9';
            }
            
            if (isValid && value) {
                field.classList.add('success');
            } else if (!isValid) {
                field.classList.add('error');
                showFieldError(field, message);
            }
        }

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        function isValidPhone(phone) {
            const phoneRegex = /^9[0-9]{9}$/;
            return phoneRegex.test(phone);
        }

        function showFieldError(field, message) {
            // Remove existing error message
            const existingError = field.parentElement.querySelector('.field-error');
            if (existingError) {
                existingError.remove();
            }
            
            // Add new error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'field-error';
            errorDiv.textContent = message;
            errorDiv.style.cssText = 'color: #ff6b6b; font-size: 0.8rem; margin-top: 0.25rem;';
            field.parentElement.appendChild(errorDiv);
        }
        
        // Username availability check with debounce
        let usernameCheckTimeout = null;
        
        function checkUsernameAvailability() {
            const usernameInput = document.getElementById('username');
            const feedbackDiv = document.getElementById('username-feedback');
            const previewSpan = document.getElementById('username-preview');
            
            const username = usernameInput.value.trim();
            
            // Update preview
            if (username) {
                previewSpan.textContent = username + '@student';
            } else {
                previewSpan.textContent = 'yourname@student';
            }
            
            // Clear previous timeout
            if (usernameCheckTimeout) {
                clearTimeout(usernameCheckTimeout);
            }
            
            // If username is empty or too short, just update preview
            if (username.length < 3) {
                feedbackDiv.className = 'username-feedback';
                feedbackDiv.innerHTML = '';
                return;
            }
            
            // Debounce: wait 500ms after user stops typing
            usernameCheckTimeout = setTimeout(() => {
                // Show checking state
                feedbackDiv.className = 'username-feedback checking';
                feedbackDiv.innerHTML = '<i class="uil uil-spinner animate-spin"></i> Checking availability...';
                
                // Make AJAX request
                const formData = new FormData();
                formData.append('username', username);
                
                fetch('check_username.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.available) {
                        feedbackDiv.className = 'username-feedback available';
                        feedbackDiv.innerHTML = '<i class="uil uil-check-circle"></i> ' + data.message;
                        usernameInput.style.borderColor = '#51cf66';
                    } else {
                        feedbackDiv.className = 'username-feedback taken';
                        feedbackDiv.innerHTML = '<i class="uil uil-times-circle"></i> ' + data.message;
                        usernameInput.style.borderColor = '#ff6b6b';
                    }
                })
                .catch(error => {
                    console.error('Error checking username:', error);
                    feedbackDiv.className = 'username-feedback';
                    feedbackDiv.innerHTML = '';
                });
            }, 500);
        }
        
        // Add animation style for spinner
        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            .animate-spin {
                animation: spin 1s linear infinite;
            }
        `;
        document.head.appendChild(style);
        
        // Password Toggle Function
        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
