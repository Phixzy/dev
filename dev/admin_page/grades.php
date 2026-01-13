<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dev";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all approved students
$all_students = [];
$students_sql = "SELECT DISTINCT s.username, s.first_name, s.last_name, s.college_course, s.college_year, s.status
                 FROM students s 
                 WHERE s.status = 'APPROVED'
                 ORDER BY s.last_name ASC, s.first_name ASC";
$students_result = $conn->query($students_sql);

if ($students_result && $students_result->num_rows > 0) {
    while ($row = $students_result->fetch_assoc()) {
        $all_students[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <title>Admin Dashboard - Grades Management</title>
</head>
<body>
    <nav>
        <div class="logo-name">
            <span class="logo_name"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
        </div>
        <div class="menu-items">
            <ul class="nav-links">
                <li><a href="adminpage.php"><i class="fas fa-calendar-alt"></i><span class="link-name">Appointments</span></a></li>
                <li><a href="student_status.php"><i class="fas fa-user-check"></i><span class="link-name">Student Status</span></a></li>
                <li><a href="grades.php" class="active"><i class="fas fa-chart-bar"></i><span class="link-name">Grades</span></a></li>
                <li><a href="master_list.php"><i class="fas fa-list-alt"></i><span class="link-name">Master List</span></a></li>
                <li><a href="emails.php"><i class="fas fa-envelope"></i><span class="link-name">Emails</span></a></li>
                <li><a href="edit_homepage.php"><i class="fas fa-edit"></i><span class="link-name">Edit Homepage</span></a></li>
                <li><a href="admin_user.php"><i class="fas fa-user-shield"></i><span class="link-name">Admin User Management</span></a></li>
            </ul>
            <ul class="logout-mode">
                <li><a href="#"><i class="fas fa-sign-out-alt"></i><span class="link-name">Logout</span></a></li>
                <li class="mode"></li>
            </ul>
        </div>
    </nav>
    <section class="dashboard">
      <div class="top">
        <span class="sidebar-toggle"><i class="fas fa-bars"></i></span>
      </div>
        <div class="dash-content">
            <div class="messages-container">
              <?php
              if (isset($_SESSION['message'])) {
                  echo '<div class="global-message message">';
                  echo '<i class="fas fa-check-circle"></i>';
                  echo '<span>' . htmlspecialchars($_SESSION['message']) . '</span>';
                  echo '</div>';
                  unset($_SESSION['message']);
              }
              if (isset($_SESSION['error'])) {
                  echo '<div class="global-message error-message">';
                  echo '<i class="fas fa-times-circle"></i>';
                  echo '<span>' . htmlspecialchars($_SESSION['error']) . '</span>';
                  echo '</div>';
                  unset($_SESSION['error']);
              }
              ?>
            </div>
        <br>
            <div class="activity">
                <div class="title">
                    <i class="fas fa-chart-bar"></i>
                    <span class="text">Grades Management</span>
                </div>
                <div class="table-container">
                    <table class="compact-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-user"></i> Student Name</th>
                                <th><i class="fas fa-book"></i> Course</th>
                                <th><i class="fas fa-calendar"></i> Year</th>
                                <th><i class="fas fa-cog"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($all_students)): ?>
                                <tr><td colspan="4" class="no-data"><i class="fas fa-clipboard-list"></i><p>No students found.</p><p>Approved students will appear here.</p></td></tr>
                            <?php else: ?>
                                <?php foreach ($all_students as $student): ?>
                                    <tr>
                                        <td class="student-name"><div><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div><div class="username"><?php echo htmlspecialchars($student['username']); ?></div></td>
                                        <td><?php echo htmlspecialchars($student['college_course'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($student['college_year'] ?? '-'); ?></td>
                                        <td class="actions-cell">
                                            <button class="btn-action btn-view" title="View/Edit Grades" onclick="openGradesModal('<?php echo urlencode($student['username']); ?>', '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>', 'edit')"><i class="fas fa-eye"></i> View</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
        </div>
    </section>

    <!-- Success Popup -->
    <div id="successPopup" class="success-popup">
        <div class="success-popup-content">
            <div class="success-popup-header">
                <i class="fas fa-check-circle"></i>
                <h3>Success</h3>
            </div>
            <div class="success-popup-body">
                <p id="successPopupMessage"></p>
            </div>
            <div class="success-popup-footer">
                <button class="btn-action btn-close" onclick="closeSuccessPopup()">OK</button>
            </div>
        </div>
    </div>

    <!-- Error Popup -->
    <div id="errorPopup" class="error-popup">
        <div class="error-popup-content">
            <div class="error-popup-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Error</h3>
            </div>
            <div class="error-popup-body">
                <p id="errorPopupMessage"></p>
            </div>
            <div class="error-popup-footer">
                <button class="btn-action btn-close" onclick="closeErrorPopup()">OK</button>
            </div>
        </div>
    </div>

    <!-- Grades Modal -->
    <div id="gradesModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <div class="modal-header-title">
                    <i class="fas fa-chart-bar"></i>
                    <h2 id="modalStudentName">Student Grades</h2>
                </div>
                <div class="modal-header-actions">
                    <button class="modal-close-btn" onclick="closeGradesModal()" title="Close"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="modal-body">
                <div class="student-info-card" id="studentInfoCard">
                    <!-- Student info will be populated here -->
                </div>
                <input type="hidden" id="currentStudentUsername" value="">
                <div class="grades-table-container">
                    <table class="compact-table" id="gradesTable">
                        <thead>
                            <tr>
                                <th><i class="fas fa-book"></i> Subject Name</th>
                                <th><i class="fas fa-code"></i> Subject Code</th>
                                <th><i class="fas fa-chalkboard-teacher"></i> Instructor</th>
                                <th><i class="fas fa-calendar"></i> Semester</th>
                                <th><i class="fas fa-edit"></i> Prelim</th>
                                <th><i class="fas fa-edit"></i> Midterm</th>
                                <th><i class="fas fa-edit"></i> Final</th>
                                <th><i class="fas fa-calculator"></i> Average</th>
                                <th><i class="fas fa-check-circle"></i> Remarks</th>
                                <th><i class="fas fa-cog"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody id="gradesTableBody">
                            <tr><td colspan="10" class="no-data"><i class="fas fa-spinner fa-spin"></i><p>Loading grades...</p></td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button class="btn-action btn-close" onclick="closeGradesModal()"><i class="fas fa-times"></i> Close</button>
                </div>
            </div>
        </div>
    </div>

<script>
    // Modal functions
    async function openGradesModal(username, studentName, mode = 'view') {
        document.getElementById('gradesModal').style.display = 'block';
        document.getElementById('modalStudentName').textContent = mode === 'edit' ? 'Edit Grades: ' + studentName : 'Grades: ' + studentName;
        document.getElementById('gradesTableBody').innerHTML = '<tr><td colspan="10" class="no-data"><i class="fas fa-spinner fa-spin"></i><p>Loading grades...</p></td></tr>';
        document.getElementById('studentInfoCard').style.display = 'none';
        document.getElementById('currentStudentUsername').value = decodeURIComponent(username);

        // Store the mode for later use
        document.getElementById('currentStudentUsername').setAttribute('data-mode', mode);

        // Fetch grades (which now also fetches enrolled subjects internally)
        fetchGrades(username, mode);
    }

    function closeGradesModal() {
        document.getElementById('gradesModal').style.display = 'none';
    }

    function fetchGrades(username, mode = 'view') {
        const studentUsername = decodeURIComponent(username);
        console.log('Fetching grades for username:', studentUsername);
        
        // Add cache-busting timestamp
        const timestamp = new Date().getTime();
        
        // Fetch grades from the database
        fetch('getGradeData.php?username=' + encodeURIComponent(username) + '&_=' + timestamp)
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(gradesData => {
                console.log('Grades Data:', JSON.stringify(gradesData, null, 2));
                
                if (gradesData.error) {
                    console.log('Error from API:', gradesData.error);
                    document.getElementById('gradesTableBody').innerHTML = '<tr><td colspan="10" class="no-data"><i class="fas fa-exclamation-circle"></i><p>' + gradesData.error + '</p></td></tr>';
                    return;
                }
                
                const existingGrades = gradesData.grades || [];
                console.log('Number of grades found:', existingGrades.length);
                console.log('Student data:', gradesData.student);
                
                // Now fetch enrolled subjects to show all subjects (not just ones with grades)
                fetch('getEnrolledSubjects.php?student_username=' + encodeURIComponent(studentUsername))
                    .then(response => response.json())
                    .then(subjectsData => {
                        console.log('Enrolled Subjects Data:', JSON.stringify(subjectsData, null, 2));
                        
                        const tbody = document.getElementById('gradesTableBody');
                        const enrolledSubjects = subjectsData.subjects || [];
                        
                        // Merge existing grades with enrolled subjects
                        // Create a map of existing grades by subject_code + semester
                        const existingGradeMap = new Map();
                        existingGrades.forEach(grade => {
                            const key = grade.subject_code + '_' + grade.semester;
                            existingGradeMap.set(key, grade);
                        });
                        
                        // Build list of all rows: existing grades + enrolled subjects without grades
                        let allRows = [...existingGrades];
                        
                        // Add enrolled subjects that don't have grades yet
                        enrolledSubjects.forEach(subject => {
                            // Check if this subject already has a grade for any semester
                            const hasGrade = existingGrades.some(g => g.subject_code === subject.subject_code);
                            
                            if (!hasGrade) {
                                // Create a placeholder row for this subject
                                allRows.push({
                                    subject_code: subject.subject_code,
                                    subject_name: subject.subject_name,
                                    instructor_name: subject.instructor_name || '',
                                    semester: '1st Sem',
                                    prelim_grade: 0,
                                    midterm_grade: 0,
                                    final_grade: 0,
                                    average: 0,
                                    remarks: '',
                                    isEnrolledSubject: true // Flag to indicate this is an enrolled subject without grades
                                });
                            }
                        });
                        
                        console.log('Total rows to display:', allRows.length);
                        
                        if (allRows.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="10" class="no-data"><i class="fas fa-clipboard-list"></i><p>No subjects found for this student.</p><p>Subjects based on course/year will appear here.</p></td></tr>';
                        } else {
                            let html = '';
                            allRows.forEach(function(grade) {
                                const isNew = grade.isNew || !grade.id || grade.id.toString().startsWith('new_');
                                const isEnrolledSubject = grade.isEnrolledSubject || false;
                                
                                const prelimVal = parseFloat(grade.prelim_grade) > 0 ? parseFloat(grade.prelim_grade).toFixed(2) : '';
                                const midtermVal = parseFloat(grade.midterm_grade) > 0 ? parseFloat(grade.midterm_grade).toFixed(2) : '';
                                const finalVal = parseFloat(grade.final_grade) > 0 ? parseFloat(grade.final_grade).toFixed(2) : '';
                                const avgVal = parseFloat(grade.average) > 0 ? parseFloat(grade.average).toFixed(2) : (isNew ? '-' : '');
                                const remarksVal = grade.remarks || (isNew ? '' : '');

                                const isViewMode = mode === 'view';
                                const readonlyAttr = isViewMode ? ' readonly' : '';
                                const disabledAttr = isViewMode ? ' disabled' : '';
                                
                                // Generate unique grade ID
                                let gradeId;
                                if (isEnrolledSubject) {
                                    // For enrolled subjects without grades, create a unique ID
                                    gradeId = 'enrolled_' + grade.subject_code;
                                } else {
                                    gradeId = grade.id || ('new_' + grade.subject_code + '_' + grade.semester.replace(' ', ''));
                                }
                                
                                const rowClass = isEnrolledSubject ? 'enrolled-subject-row' : (isNew ? 'new-grade-row' : '');
                                
                                html += '<tr data-grade-id="' + gradeId + '"' + (rowClass ? ' class="' + rowClass + '"' : '') + '>';
                                html += '<td class="subject-cell">' + grade.subject_name + '</td>';
                                html += '<td><input type="text" class="grade-input" id="subjectCode-' + gradeId + '" value="' + grade.subject_code + '"' + readonlyAttr + '></td>';

                                // Instructor name
                                const instructorName = grade.instructor_name || '';
                                html += '<td><input type="text" class="grade-input instructor-input" id="instructor-' + gradeId + '" value="' + instructorName + '" placeholder="-" readonly></td>';

                                // Semester dropdown
                                html += '<td>';
                                html += '<select class="grade-input semester-select" id="semester-' + gradeId + '"' + disabledAttr + ' onchange="calculateAverage(\'' + gradeId + '\')">';
                                html += '<option value="1st Sem"' + (grade.semester === '1st Sem' ? ' selected' : '') + '>1st Sem</option>';
                                html += '<option value="2nd Sem"' + (grade.semester === '2nd Sem' ? ' selected' : '') + '>2nd Sem</option>';
                                html += '<option value="Summer"' + (grade.semester === 'Summer' ? ' selected' : '') + '>Summer</option>';
                                html += '</select>';
                                html += '</td>';

                                // Editable grade inputs
                                html += '<td><input type="number" class="grade-input prelim-input" min="0" max="100" step="0.01" value="' + prelimVal + '" placeholder="-"' + readonlyAttr + ' onchange="calculateAverage(\'' + gradeId + '\')" oninput="calculateAverage(\'' + gradeId + '\')"></td>';
                                html += '<td><input type="number" class="grade-input midterm-input" min="0" max="100" step="0.01" value="' + midtermVal + '" placeholder="-"' + readonlyAttr + ' onchange="calculateAverage(\'' + gradeId + '\')" oninput="calculateAverage(\'' + gradeId + '\')"></td>';
                                html += '<td><input type="number" class="grade-input final-input" min="0" max="100" step="0.01" value="' + finalVal + '" placeholder="-"' + readonlyAttr + ' onchange="calculateAverage(\'' + gradeId + '\')" oninput="calculateAverage(\'' + gradeId + '\')"></td>';

                                // Read-only average
                                html += '<td><strong class="average-display" id="avg-' + gradeId + '">' + avgVal + '</strong></td>';

                                // Remarks
                                html += '<td><input type="text" class="grade-input remarks-input" id="remarks-' + gradeId + '" value="' + remarksVal + '" placeholder="" readonly></td>';

                                // Action buttons
                                html += '<td class="actions-cell">';
                                if (mode === 'edit') {
                                    if (isEnrolledSubject) {
                                        // For enrolled subjects without grades, use addNewGrade
                                        html += '<button class="btn-action btn-add" id="save-' + gradeId + '" onclick="addGradeForEnrolledSubject(\'' + gradeId + '\')" title="Add Grade"><i class="fas fa-plus"></i> Add</button>';
                                    } else {
                                        html += '<button class="btn-action btn-save" id="save-' + gradeId + '" onclick="saveGrade(\'' + gradeId + '\')" title="Save Changes"><i class="fas fa-save"></i></button>';
                                    }
                                    html += '<span class="status-indicator status-' + gradeId + '"></span>';
                                }
                                html += '</td>';
                                html += '</tr>';
                            });
                            tbody.innerHTML = html;
                        }
                        
                        // Show student info
                        if (gradesData.student) {
                            const infoCard = document.getElementById('studentInfoCard');
                            infoCard.innerHTML = '<div class="student-info-content"><div class="student-avatar"><i class="fas fa-user"></i></div><div class="student-details"><h3>' + gradesData.student.first_name + ' ' + gradesData.student.last_name + '</h3><p>' + gradesData.student.username + '</p></div><div class="student-meta"><div class="meta-item"><span class="meta-label">Course</span><span class="meta-value">' + (gradesData.student.college_course || '-') + '</span></div><div class="meta-item"><span class="meta-label">Year Level</span><span class="meta-value">' + (gradesData.student.college_year || '-') + '</span></div></div></div>';
                            infoCard.style.display = 'block';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching enrolled subjects:', error);
                        // Still show grades even if enrolled subjects fetch fails
                        document.getElementById('gradesTableBody').innerHTML = '<tr><td colspan="10" class="no-data"><i class="fas fa-exclamation-circle"></i><p>Error loading subjects: ' + error.message + '</p><p>Existing grades shown below.</p></td></tr>';
                    });
            })
            .catch(error => {
                console.error('Error fetching data:', error);
                document.getElementById('gradesTableBody').innerHTML = '<tr><td colspan="10" class="no-data"><i class="fas fa-exclamation-circle"></i><p>Error loading grades: ' + error.message + '</p><p>Check browser console for details.</p></td></tr>';
            });
    }
    
    // Add grade for enrolled subject (without existing grade record)
    function addGradeForEnrolledSubject(gradeId) {
        const row = document.querySelector('tr[data-grade-id="' + gradeId + '"]');
        if (!row) return;
        
        const studentUsername = document.getElementById('currentStudentUsername').value;
        const subjectCode = document.getElementById('subjectCode-' + gradeId).value;
        const subjectName = row.querySelector('.subject-cell').textContent;
        const semester = row.querySelector('.semester-select').value;
        const prelim = parseFloat(row.querySelector('.prelim-input').value) || 0;
        const midterm = parseFloat(row.querySelector('.midterm-input').value) || 0;
        const final = parseFloat(row.querySelector('.final-input').value) || 0;
        const remarks = row.querySelector('.remarks-input').value;
        
        // Validate grades
        if (prelim < 0 || prelim > 100 || midterm < 0 || midterm > 100 || final < 0 || final > 100) {
            showStatusMessage(gradeId, 'Grades must be between 0 and 100', 'error');
            return;
        }
        
        if (!subjectCode) {
            showStatusMessage(gradeId, 'Subject code is required', 'error');
            return;
        }
        
        const saveBtn = document.getElementById('save-' + gradeId);
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
        }
        
        const formData = new FormData();
        formData.append('student_username', studentUsername);
        formData.append('subject_code', subjectCode);
        formData.append('subject_name', subjectName);
        formData.append('semester', semester);
        formData.append('prelim_grade', prelim);
        formData.append('midterm_grade', midterm);
        formData.append('final_grade', final);
        formData.append('remarks', remarks);
        
        fetch('addNewGrade.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showStatusMessage(gradeId, 'Grade added successfully!', 'success');
                // Refresh the grades table
                const username = document.getElementById('currentStudentUsername').value;
                fetchGrades(encodeURIComponent(username), 'edit');
            } else {
                showStatusMessage(gradeId, data.message || 'Error adding grade', 'error');
            }
        })
        .catch(error => {
            console.error('Error adding grade:', error);
            showStatusMessage(gradeId, 'Error adding grade: ' + error.message, 'error');
        })
        .finally(() => {
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-plus"></i> Add';
            }
        });
    }

    // Calculate average and determine status
    function calculateAverage(gradeId) {
        const row = document.querySelector('tr[data-grade-id="' + gradeId + '"]');
        if (!row) return;
        
        const prelim = parseFloat(row.querySelector('.prelim-input').value) || 0;
        const midterm = parseFloat(row.querySelector('.midterm-input').value) || 0;
        const final = parseFloat(row.querySelector('.final-input').value) || 0;
        const remarksInput = row.querySelector('.remarks-input');
        
        let gradesCount = 0;
        let gradesSum = 0;
        
        // Check if grades are entered (treat 0 as empty/not yet taken)
        // Only count grades that are explicitly entered and > 0
        const prelimVal = row.querySelector('.prelim-input').value.trim();
        const midtermVal = row.querySelector('.midterm-input').value.trim();
        const finalVal = row.querySelector('.final-input').value.trim();
        
        if (prelimVal !== '' && prelimVal !== '-' && prelimVal !== '0' && prelim !== 0) {
            gradesCount++;
            gradesSum += prelim;
        }
        if (midtermVal !== '' && midtermVal !== '-' && midtermVal !== '0' && midterm !== 0) {
            gradesCount++;
            gradesSum += midterm;
        }
        if (finalVal !== '' && finalVal !== '-' && finalVal !== '0' && final !== 0) {
            gradesCount++;
            gradesSum += final;
        }
        
        const average = gradesCount > 0 ? (gradesSum / gradesCount) : 0;
        
        // Update average display
        const avgDisplay = document.getElementById('avg-' + gradeId);
        if (avgDisplay) {
            avgDisplay.textContent = average > 0 ? average.toFixed(2) : '-';
        }
        
        // Auto-update remarks based on grades entered
        if (remarksInput) {
            if (gradesCount === 0) {
                remarksInput.value = '';
            } else if (gradesCount < 3) {
                // Not all grades entered - show as Incomplete
                remarksInput.value = 'Incomplete';
            } else if (average >= 75) {
                // All grades entered and average >= 75
                remarksInput.value = 'Passed';
            } else {
                // All grades entered but average < 75
                remarksInput.value = 'Failed';
            }
        }
    }

    // Save new grade via AJAX
    function saveNewGrade(gradeId) {
        // Ensure gradeId is a string for consistent handling
        const gradeIdStr = String(gradeId);
        
        const row = document.querySelector('tr[data-grade-id="' + gradeIdStr + '"]');
        if (!row) return;
        
        const studentUsername = document.getElementById('currentStudentUsername').value;
        const subjectCode = document.getElementById('subjectCode-' + gradeIdStr).value;
        const semester = row.querySelector('.semester-select').value;
        const prelim = parseFloat(row.querySelector('.prelim-input').value) || 0;
        const midterm = parseFloat(row.querySelector('.midterm-input').value) || 0;
        const final = parseFloat(row.querySelector('.final-input').value) || 0;
        const remarks = row.querySelector('.remarks-input').value;
        const subjectName = row.querySelector('.subject-cell').textContent;
        
        // Validate grades
        if (prelim < 0 || prelim > 100 || midterm < 0 || midterm > 100 || final < 0 || final > 100) {
            showStatusMessage(gradeIdStr, 'Grades must be between 0 and 100', 'error');
            return;
        }
        
        if (empty(subjectCode)) {
            showStatusMessage(gradeIdStr, 'Subject code is required', 'error');
            return;
        }
        
        const saveBtn = document.getElementById('save-' + gradeIdStr);
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        
        const formData = new FormData();
        formData.append('student_username', studentUsername);
        formData.append('subject_code', subjectCode);
        formData.append('subject_name', subjectName);
        formData.append('semester', semester);
        formData.append('prelim_grade', prelim);
        formData.append('midterm_grade', midterm);
        formData.append('final_grade', final);
        formData.append('remarks', remarks);
        
        fetch('addNewGrade.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Add grade response status:', response.status);
            console.log('Add grade response headers:', response.headers.get('content-type'));
            return response.text();
        })
        .then(text => {
            console.log('Raw add grade response:', text);
            // Try to parse as JSON
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Failed to parse JSON:', e);
                // Show the actual response in the error message
                const displayText = text.length > 200 ? text.substring(0, 200) + '...' : text;
                showStatusMessage(gradeIdStr, 'Server error: ' + displayText, 'error');
                return;
            }
            if (data.success) {
                showStatusMessage(gradeIdStr, 'New grade added successfully!', 'success');
                // Refresh the grades table to show the saved grade with proper ID
                const username = document.getElementById('currentStudentUsername').value;
                fetchGrades(encodeURIComponent(username), 'edit');
            } else {
                showStatusMessage(gradeIdStr, data.message || 'Error adding new grade', 'error');
            }
        })
        .catch(error => {
            console.error('Error adding new grade:', error);
            showStatusMessage(gradeIdStr, 'Error adding grade: ' + error.message, 'error');
        })
        .finally(() => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save"></i> Save';
        });
    }

    // Helper function to check if a value is empty
    function empty(value) {
        return value === null || value === undefined || value === '';
    }

    // Save grade via AJAX
    function saveGrade(gradeId) {
        // Ensure gradeId is a string for string operations
        const gradeIdStr = String(gradeId);
        
        console.log('saveGrade called with gradeId:', gradeId, 'gradeIdStr:', gradeIdStr);
        
        // Check if this is a new grade (from "Add New Grade" button)
        if (gradeIdStr.startsWith('new_')) {
            console.log('Routing to saveNewGrade');
            saveNewGrade(gradeIdStr);  // Pass the string version
            return;
        }
        
        console.log('Routing to saveGrade.php with grade_id:', gradeIdStr);
        
        const row = document.querySelector('tr[data-grade-id="' + gradeIdStr + '"]');
        if (!row) return;
        
        const subjectCode = document.getElementById('subjectCode-' + gradeIdStr).value;
        const semester = row.querySelector('.semester-select').value;
        const prelim = parseFloat(row.querySelector('.prelim-input').value) || 0;
        const midterm = parseFloat(row.querySelector('.midterm-input').value) || 0;
        const final = parseFloat(row.querySelector('.final-input').value) || 0;
        const remarks = row.querySelector('.remarks-input').value;
        
        // Validate grades
        if (prelim < 0 || prelim > 100 || midterm < 0 || midterm > 100 || final < 0 || final > 100) {
            showStatusMessage(gradeIdStr, 'Grades must be between 0 and 100', 'error');
            return;
        }
        
        const saveBtn = document.getElementById('save-' + gradeIdStr);
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        
        const formData = new FormData();
        formData.append('grade_id', gradeId);
        formData.append('subject_code', subjectCode);
        formData.append('semester', semester);
        formData.append('prelim_grade', prelim);
        formData.append('midterm_grade', midterm);
        formData.append('final_grade', final);
        formData.append('remarks', remarks);
        
        fetch('saveGrade.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Save response status:', response.status);
            console.log('Save response headers:', response.headers.get('content-type'));
            return response.text();
        })
        .then(text => {
            console.log('Raw response:', text);
            // Try to parse as JSON
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Failed to parse JSON:', e);
                // Show the actual response in the error message
                const displayText = text.length > 200 ? text.substring(0, 200) + '...' : text;
                showStatusMessage(gradeIdStr, 'Server error: ' + displayText, 'error');
                return;
            }
            console.log('Save response data:', JSON.stringify(data, null, 2));
            if (data.success) {
                showStatusMessage(gradeIdStr, 'Saved!', 'success');
                // Update the average and status display
                if (data.data) {
                    const avgDisplay = document.getElementById('avg-' + gradeIdStr);
                    if (avgDisplay && data.data.average) {
                        avgDisplay.textContent = data.data.average;
                    }
                }
            } else {
                showStatusMessage(gradeIdStr, data.message || 'Error saving', 'error');
            }
        })
        .catch(error => {
            console.error('Error saving grade:', error);
            showStatusMessage(gradeIdStr, 'Error saving grade: ' + error.message, 'error');
        })
        .finally(() => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save"></i> Save';
        });
    }

    // Show status message for a grade row
    function showStatusMessage(gradeId, message, type) {
        if (type === 'error') {
            showErrorPopup(message);
        } else if (type === 'success') {
            showSuccessPopup(message);
        }
    }

    // Show error popup
    function showErrorPopup(message) {
        document.getElementById('errorPopupMessage').textContent = message;
        document.getElementById('errorPopup').style.display = 'block';
    }

    // Close error popup
    function closeErrorPopup() {
        document.getElementById('errorPopup').style.display = 'none';
    }

    // Show success popup
    function showSuccessPopup(message) {
        document.getElementById('successPopupMessage').textContent = message;
        document.getElementById('successPopup').style.display = 'block';
    }

    // Close success popup
    function closeSuccessPopup() {
        document.getElementById('successPopup').style.display = 'none';
    }

    // Fetch enrolled subjects for a student (all subjects based on course/year)
    let cachedEnrolledSubjects = [];
    
    function fetchEnrolledSubjects(studentUsername, callback = null) {
        return fetch('getEnrolledSubjects.php?student_username=' + encodeURIComponent(studentUsername))
            .then(response => response.json())
            .then(data => {
                if (data.success && data.subjects) {
                    cachedEnrolledSubjects = data.subjects;
                    // Execute callback if provided
                    if (callback && typeof callback === 'function') {
                        callback(data.subjects);
                    }
                } else {
                    cachedEnrolledSubjects = [];
                    // Still execute callback with empty array so UI can be updated
                    if (callback && typeof callback === 'function') {
                        callback([]);
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching enrolled subjects:', error);
                cachedEnrolledSubjects = [];
                // Execute callback even on error to update UI
                if (callback && typeof callback === 'function') {
                    callback([]);
                }
            });
    }

    // Update subject name when subject code is manually entered for existing grades
    function updateSubjectCode(gradeId) {
        const subjectCode = document.getElementById('subjectCode-' + gradeId).value.trim();
        const subjectNameCell = document.querySelector('tr[data-grade-id="' + gradeId + '"] .subject-cell');
        
        if (subjectCode && cachedEnrolledSubjects.length > 0) {
            const subject = cachedEnrolledSubjects.find(s => s.subject_code.toLowerCase() === subjectCode.toLowerCase());
            if (subject) {
                subjectNameCell.textContent = subject.subject_name;
            }
        }
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('gradesModal');
        if (event.target === modal) {
            closeGradesModal();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const body = document.querySelector("body");
        const sidebar = body.querySelector("nav");
        const sidebarToggle = body.querySelector(".sidebar-toggle");
        localStorage.getItem("mode") === "dark" && body.classList.toggle("dark");
        localStorage.getItem("status") === "close" && sidebar.classList.add("close");
        sidebarToggle && sidebarToggle.addEventListener("click", function() {
            sidebar.classList.toggle("close");
            localStorage.setItem("status", sidebar.classList.contains("close") ? "close" : "open");
        });
        document.querySelectorAll('.global-message').forEach(function(m) {
            setTimeout(function() { m.style.animation = 'fadeOut 0.5s'; setTimeout(function() { m.parentNode && m.parentNode.removeChild(m); }, 500); }, 5000);
        });
    });
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600&display=swap');
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif}
nav .nav-links li a.active{background:rgba(255,255,255,0.2);box-shadow:0 4px 10px rgba(0,0,0,0.1)}
nav .nav-links li a.active i,nav .nav-links li a.active .link-name{color:white}
.table-container{overflow-x:auto;margin-bottom:30px;border-radius:12px;box-shadow:0 2px 15px rgba(0,0,0,0.08)}
.compact-table{width:100%;border-collapse:collapse;font-size:0.9rem;background:white;border-radius:12px;overflow:hidden}
.compact-table thead{position:sticky;top:0;z-index:10}
.compact-table th{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:14px 10px;font-weight:600;text-align:center;font-size:0.85rem;border:none;white-space:nowrap}
.compact-table th:first-child{border-top-left-radius:12px}
.compact-table th:last-child{border-top-right-radius:12px}
.compact-table td{padding:12px 10px;border-bottom:1px solid #eee;vertical-align:middle;font-size:0.85rem;background:#fff}
.compact-table tbody tr{transition:all 0.2s ease}
.compact-table tbody tr:hover{background-color:#f0f4ff}
.compact-table tbody tr:last-child td:first-child{border-bottom-left-radius:12px}
.compact-table tbody tr:last-child td:last-child{border-bottom-right-radius:12px}
.no-data{text-align:center;color:#6c757d;font-style:italic;padding:30px 20px;background:#f8f9fa}
.no-data i{font-size:2.5rem;color:#dee2e6;margin-bottom:10px;display:block}
.no-data p:last-child{font-size:0.85rem;color:#6c757d}
.status-badge{padding:6px 12px;border-radius:20px;font-size:0.75rem;font-weight:600;text-transform:uppercase}
.status-approved{background:rgba(40,167,69,0.1);color:#28a745;border:1px solid rgba(40,167,69,0.3)}
.status-rejected{background:rgba(220,53,69,0.1);color:#dc3545;border:1px solid rgba(220,53,69,0.3)}
.status-pending{background:rgba(255,193,7,0.1);color:#ffc107;border:1px solid rgba(255,193,7,0.3)}
.status-incomplete{background:rgba(23,162,184,0.1);color:#17a2b8;border:1px solid rgba(23,162,184,0.3)}
.btn-action{transition:all 0.3s ease;border:none;cursor:pointer;text-decoration:none;padding:8px 14px;border-radius:8px;background:#17a2b8;color:white;font-size:0.85rem;display:inline-flex;align-items:center;gap:6px;font-weight:500}
.btn-action:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,0.2)}
.btn-action:disabled{opacity:0.6;cursor:not-allowed;transform:none}
.btn-view{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%)}
.btn-view:hover{background:linear-gradient(135deg,#5a6fd1 0%,#6a4190 100%)}
.btn-save{background:linear-gradient(135deg,#28a745 0%,#20c997 100%)}
.btn-save:hover{background:linear-gradient(135deg,#218838 0%,#1db586 100%)}
.btn-add:hover{background:#218838}
.btn-close{background:linear-gradient(135deg,#6c757d 0%,#5a6268 100%);border-radius:8px;padding:10px 24px;font-size:0.9rem}
.btn-close:hover{background:linear-gradient(135deg,#5a6268 0%,#4e555b 100%);transform:translateY(-2px)}
.student-name div:first-child{font-weight:600}
.student-name .username{font-size:0.75rem;color:#6c757d;font-family:monospace;margin-top:2px}
.actions-cell{text-align:center;white-space:nowrap}
body{min-height:100vh;background-color:#0E4BF1}
nav{position:fixed;top:0;left:0;height:100%;width:250px;padding:10px 14px;background:linear-gradient(180deg,#667eea 0%,#764ba2 100%);box-shadow:4px 0 15px rgba(102,126,234,0.3);z-index:100}
nav.close{width:73px}
nav .logo-name{display:flex;align-items:center;padding:15px 10px;border-bottom:1px solid rgba(255,255,255,0.2);margin-bottom:15px}
nav .logo-name .logo_name{font-size:18px;font-weight:600;color:#fff;margin-left:12px;white-space:nowrap}
nav .menu-items{height:calc(100% - 70px);display:flex;flex-direction:column;justify-content:space-between;overflow-y:auto}
.menu-items li{list-style:none}
.nav-links li{position:relative;margin:5px 0}
.menu-items li a{display:flex;align-items:center;padding:12px 15px;text-decoration:none;position:relative;border-radius:10px;transition:all 0.3s ease}
.menu-items li a i{font-size:24px;min-width:45px;height:100%;display:flex;align-items:center;justify-content:center;color:#ffffff}
.menu-items li a .link-name{font-size:18px;font-weight:400;color:#ffffff}
.menu-items .logout-mode{border-top:1px solid rgba(255,255,255,0.2);padding-top:15px}
.dashboard{position:relative;left:250px;background-color:#FFF;min-height:100vh;width:calc(100% - 250px);padding:10px 14px;transition:all 0.5s ease}
nav.close~.dashboard{left:73px;width:calc(100% - 73px)}
.dashboard .top{position:fixed;top:0;left:250px;display:flex;width:calc(100% - 250px);justify-content:space-between;align-items:center;padding:10px 14px;background-color:#FFF;z-index:10}
nav.close~.dashboard .top{left:73px;width:calc(100% - 73px)}
.dashboard .top .sidebar-toggle{font-size:26px;color:#000;cursor:pointer}
.dash-content .title{display:flex;align-items:center;margin:30px 0 20px 0}
.dash-content .title i{position:relative;height:30px;width:30px;background-color:#0E4BF1;border-radius:6px;color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px}
.dash-content .title .text{font-size:18px;font-weight:600;color:#000;margin-left:8px}
@keyframes fadeOut{from{opacity:1}to{opacity:0;transform:translateY(-100%)}}
.global-message{position:relative;z-index:1000;margin:80px 20px 20px 20px;padding:16px 24px;border-radius:8px;display:flex;align-items:center;gap:12px;box-shadow:0 4px 12px rgba(0,0,0,0.15);border-left:5px solid}
.global-message.message{background:linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);border-left-color:#28a745}
.global-message.error-message{background:linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);border-left-color:#dc3545}
.global-message i{font-size:24px;flex-shrink:0}
.global-message.message i{color:#28a745}
.global-message.error-message i{color:#dc3545}
.global-message span{font-weight:500}
.global-message.message span{color:#155724}
.global-message.error-message span{color:#721c24}
.messages-container{position:relative;z-index:1000}
/* Modal Styles */
.modal{display:none;position:fixed;z-index:2000;left:0;top:0;width:100%;height:100%;overflow:auto;backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);background-color:rgba(0,0,0,0.6);animation:modalFadeIn 0.4s ease}
@keyframes modalFadeIn{from{opacity:0;transform:scale(0.95)}to{opacity:1;transform:scale(1)}}
.modal-content{background-color:#fff;margin:2% auto;padding:0;border:none;width:95%;max-width:1200px;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,0.3);overflow:hidden;animation:slideIn 0.4s ease}
@keyframes slideIn{from{opacity:0;transform:translateY(-30px)}to{opacity:1;transform:translateY(0)}}
.modal-content.modal-large{max-width:1300px}
.modal-header{background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);padding:20px 25px;display:flex;justify-content:space-between;align-items:center;margin-bottom:0}
.modal-header-title{display:flex;align-items:center;gap:15px}
.modal-header-title i{color:rgba(255,255,255,0.9);font-size:28px}
.modal-header-title h2{margin:0;color:#fff;font-size:1.5rem;font-weight:600}
.modal-header-actions{display:flex;align-items:center;gap:10px}
.modal-close-btn{background:rgba(255,255,255,0.2);border:none;color:#fff;font-size:24px;width:40px;height:40px;border-radius:50%;cursor:pointer;transition:all 0.3s ease;display:flex;align-items:center;justify-content:center}
.modal-close-btn:hover{background:rgba(255,255,255,0.3);transform:rotate(90deg)}
.modal-body{padding:20px 25px 25px}
.student-info-card{background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);border-radius:12px;padding:18px 22px;margin-bottom:20px;color:white;display:none;box-shadow:0 4px 15px rgba(102,126,234,0.3)}
.student-info-content{display:flex;align-items:center;gap:18px;flex-wrap:wrap}
.student-avatar{width:55px;height:55px;background:rgba(255,255,255,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:24px}
.student-details h3{margin:0;font-size:1.25rem;font-weight:600}
.student-details p{margin:4px 0 0 0;opacity:0.9;font-size:0.9rem}
.student-meta{margin-left:auto;display:flex;gap:30px;flex-wrap:wrap}
.meta-item{text-align:center;padding:8px 15px;background:rgba(255,255,255,0.15);border-radius:8px}
.meta-label{display:block;font-size:0.75rem;opacity:0.9;text-transform:uppercase;letter-spacing:0.5px}
.meta-value{display:block;font-weight:600;margin-top:4px;font-size:1rem}
.grades-table-container{max-height:450px;overflow-y:auto;border-radius:12px;border:1px solid #e0e0e0}
.grades-table-container::-webkit-scrollbar{width:10px}
.grades-table-container::-webkit-scrollbar-track{background:#f1f1f1;border-radius:5px}
.grades-table-container::-webkit-scrollbar-thumb{background:linear-gradient(180deg,#667eea 0%,#764ba2 100%);border-radius:5px}
.grades-table-container::-webkit-scrollbar-thumb:hover{background:linear-gradient(180deg,#5a6fd1 0%,#6a4190 100%)}
.summary-section{margin-top:20px;padding:20px;background:#fff;border-radius:12px;border:1px solid #e0e0e0;box-shadow:0 2px 8px rgba(0,0,0,0.05);display:none}
.summary-section h4{margin:0 0 16px 0;color:#333;font-size:1.1rem;display:flex;align-items:center;gap:10px}
.summary-section h4 i{color:#667eea}
.summary-stats{display:flex;gap:30px;flex-wrap:wrap}
.stat-item{text-align:center;padding:12px 20px;background:#f8f9fa;border-radius:10px;min-width:100px;transition:transform 0.3s ease, box-shadow 0.3s ease}
.stat-item:hover{transform:translateY(-3px);box-shadow:0 4px 12px rgba(0,0,0,0.1)}
.stat-label{display:block;font-size:0.8rem;color:#6c757d;text-transform:uppercase;letter-spacing:0.5px}
.stat-value{display:block;font-size:1.5rem;font-weight:700;color:#333;margin-top:6px}
.stat-item.passed{background:rgba(40,167,69,0.08);border:1px solid rgba(40,167,69,0.2)}
.stat-item.passed .stat-label{color:#28a745}
.stat-item.passed .stat-value{color:#28a745}
.stat-item.failed{background:rgba(220,53,69,0.08);border:1px solid rgba(220,53,69,0.2)}
.stat-item.failed .stat-label{color:#dc3545}
.stat-item.failed .stat-value{color:#dc3545}
.stat-item.progress{background:255,193,7,0.08;border:1px solid rgba(255,193,7,0.2)}
.stat-item.progress .stat-label{color:#ffc107}
.stat-item.progress .stat-value{color:#ffc107}
.modal-footer{margin-top:25px;padding-top:20px;border-top:1px solid #eee;display:flex;justify-content:flex-end;gap:12px}
.btn-close{background:#6c757d;border-radius:8px;padding:10px 24px;font-size:0.9rem}
.btn-close:hover{background:#5a6268}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
/* Grade input styles */
.grade-input{width:100%;padding:6px 10px;border:1px solid #ddd;border-radius:4px;font-size:0.85rem;transition:border-color 0.3s, box-shadow 0.3s}
.grade-input:focus{outline:none;border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,0.2)}
.grade-input[readonly]{background-color:#f8f9fa;color:#6c757d;cursor:not-allowed}
.grade-input[type="number"]{text-align:center}
.grade-input[type="text"]{text-align:left}
.semester-select{cursor:pointer}
.remarks-input{width:120px}
.subject-cell{font-weight:500}
.subject-name-input{font-weight:500}
.subject-select{min-width:180px}
.average-display{color:#667eea;font-size:1rem}
.actions-cell .btn-action{font-size:0.9rem;padding:8px 12px}
.status-indicator{display:block;margin-top:5px;min-height:20px}
.status-msg{font-size:0.75rem;padding:3px 8px;border-radius:4px;display:inline-block}
.status-msg.success{background:rgba(40,167,69,0.1);color:#28a745}
.status-msg.error{background:rgba(220,53,69,0.1);color:#dc3545}
/* Enrolled subjects without grades row style */
.enrolled-subject-row{background-color:#f0f8ff !important}
.enrolled-subject-row:hover{background-color:#e0efff !important}
.enrolled-subject-row .subject-cell{color:#667eea;font-weight:600}
.btn-add{background:linear-gradient(135deg,#17a2b8 0%,#138496 100%)}
.btn-add:hover{background:linear-gradient(135deg,#138496 0%,#117a8b 100%)}
/* Success Popup Styles */
.success-popup{display:none;position:fixed;z-index:3000;left:0;top:0;width:100%;height:100%;background-color:rgba(0,0,0,0.5);backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);animation:fadeIn 0.3s ease}
.success-popup-content{background:#fff;margin:15% auto;padding:0;border-radius:12px;width:90%;max-width:400px;box-shadow:0 10px 30px rgba(0,0,0,0.3);animation:slideIn 0.3s ease}
.success-popup-header{background:linear-gradient(135deg,#28a745 0%,#20c997 100%);color:#fff;padding:20px;text-align:center;border-radius:12px 12px 0 0}
.success-popup-header i{font-size:24px;margin-bottom:8px;display:block}
.success-popup-header h3{margin:0;font-size:1.2rem;font-weight:600}
.success-popup-body{padding:25px;text-align:center}
.success-popup-body p{margin:0;font-size:1rem;color:#333;line-height:1.5}
.success-popup-footer{padding:15px 25px;border-top:1px solid #eee;text-align:center;background:#f8f9fa;border-radius:0 0 12px 12px}
.success-popup-footer .btn-action{background:#28a745;border-radius:6px;padding:8px 20px;font-size:0.9rem}
.success-popup-footer .btn-action:hover{background:#218838;transform:translateY(-1px)}

/* Error Popup Styles */
.error-popup{display:none;position:fixed;z-index:3000;left:0;top:0;width:100%;height:100%;background-color:rgba(0,0,0,0.5);backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);animation:fadeIn 0.3s ease}
.error-popup-content{background:#fff;margin:15% auto;padding:0;border-radius:12px;width:90%;max-width:400px;box-shadow:0 10px 30px rgba(0,0,0,0.3);animation:slideIn 0.3s ease}
.error-popup-header{background:linear-gradient(135deg,#dc3545 0%,#c82333 100%);color:#fff;padding:20px;text-align:center;border-radius:12px 12px 0 0}
.error-popup-header i{font-size:24px;margin-bottom:8px;display:block}
.error-popup-header h3{margin:0;font-size:1.2rem;font-weight:600}
.error-popup-body{padding:25px;text-align:center}
.error-popup-body p{margin:0;font-size:1rem;color:#333;line-height:1.5}
.error-popup-footer{padding:15px 25px;border-top:1px solid #eee;text-align:center;background:#f8f9fa;border-radius:0 0 12px 12px}
.error-popup-footer .btn-action{background:#dc3545;border-radius:6px;padding:8px 20px;font-size:0.9rem}
.error-popup-footer .btn-action:hover{background:#c82333;transform:translateY(-1px)}
@media(max-width:1000px){nav{width:73px}nav.close{width:250px}nav~.dashboard{left:73px;width:calc(100% - 73px)}nav.close~.dashboard{left:250px;width:calc(100% - 250px)}nav~.dashboard .top{left:73px;width:calc(100% - 73px)}nav.close~.dashboard .top{left:250px;width:calc(100% - 250px)}}
@media(max-width:768px){.global-message{margin-top:70px;margin-left:10px;margin-right:10px}.compact-table{font-size:0.75rem}.compact-table th,.compact-table td{padding:6px 4px}.grade-input{padding:4px 6px;font-size:0.75rem}.remarks-input{width:80px}.modal-content{width:95%;margin:10% auto}}
</style>

</body>
</html>

