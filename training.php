<?php
session_start();

// The code for the following CSRF Token Generation has been obtained from
// ChatGPT: Provide PHP code to meet this requirement: "One should expect that all `inputs' to the application, 
// even those that are intended to come from a menu, will be used by malicious users to inject code that 
// causes your application to malfunction or reveal the contents of the database. You should program the 
// application in a way that safeguards against such attacks.
// OpenAI, ChatGPT 13 May 2024 Version.
// https://chat.openai.com/chat [accessed 3 April 2025].
// CSRF Token Generation 
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Session timeout handling (10 minutes)
if (isset($_SESSION['LAST_ACTIVITY'])) {
    // Check if timeout period has been exceeded
    if (time() - $_SESSION['LAST_ACTIVITY'] > 600) {
        // Destroy the session completely
        session_unset();
        session_destroy();
        
        // Delete the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // Prevent immediate session recreation
        exit('<p class="error-message">Session expired. Please start over.</p>');
    }
    else {
        // Update last activity time if still active
        $_SESSION['LAST_ACTIVITY'] = time();
    }
}
else {
    // Initialise if new session
    $_SESSION['LAST_ACTIVITY'] = time();
}

// Regenerate session ID every 10 minutes
if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} elseif (time() - $_SESSION['CREATED'] > 600) {
    session_regenerate_id(true);
    $_SESSION['CREATED'] = time();
}


// Database connection details
$db_hostname = "studdb.csc.liv.ac.uk";
$db_database = "hslcabal";
$db_username = "hslcabal";
$db_password = "Zaradi09!";
$db_charset = "utf8mb4";

$dsn = "mysql:host=$db_hostname;dbname=$db_database;charset=$db_charset";

$opt = array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
);

// Initialise variables
$selected_topic = $_POST['topic'] ?? '';
$selected_session = $_POST['session'] ?? '';
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$booking_message = '';

try {
    $pdo = new PDO($dsn, $db_username, $db_password, $opt);
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'])) {

        // The code for the following if condition has been obtained from
        // ChatGPT: Provide PHP code to meet this requirement: "One should expect that all `inputs' to the application, 
        // even those that are intended to come from a menu, will be used by malicious users to inject code that 
        // causes your application to malfunction or reveal the contents of the database. You should program the 
        // application in a way that safeguards against such attacks.
        // OpenAI, ChatGPT 13 May 2024 Version.
        // https://chat.openai.com/chat [accessed 3 April 2025].
        // CSRF Validation 
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            $booking_message = "<p class='error-message'>Security validation failed. Please try again.</p>";
        }
        elseif (!empty($selected_session) && !empty($name) && !empty($email)) {
            $pdo->beginTransaction();
            
            try {
                // Check availability with lock 
                $stmt = $pdo->prepare("
                    SELECT session_id, topic_name, day_of_week, start_time, available, capacity
                    FROM training_sessions
                    WHERE session_id = ? 
                    AND available > 0 
                    FOR UPDATE
                ");
                $stmt->execute([$selected_session]);
                $session = $stmt->fetch();
                
                if ($session) {
                    // Create booking record
                    $stmt = $pdo->prepare("
                        INSERT INTO bookings 
                        (session_id, name, email) 
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$selected_session, $name, $email]);
                    
                    // Update available slots
                    $stmt = $pdo->prepare("
                        UPDATE training_sessions 
                        SET available = available - 1 
                        WHERE session_id = ?
                    ");
                    $stmt->execute([$selected_session]);
                    
                    $pdo->commit();
                    
                    // Use actual database values
                    $display_capacity = $session['capacity'];
                    $display_available = $session['available'];
                    
                    // The code for the following .htmlspecialchars has been obtained from
                    // ChatGPT: Provide PHP code to meet this requirement: "One should expect that all `inputs' to the application, 
                    // even those that are intended to come from a menu, will be used by malicious users to inject code that 
                    // causes your application to malfunction or reveal the contents of the database. You should program the 
                    // application in a way that safeguards against such attacks.
                    // OpenAI, ChatGPT 13 May 2024 Version.
                    // https://chat.openai.com/chat [accessed 3 April 2025].

                    // Replace the existing $booking_message assignment with:
                    $booking_message = "<div class='booking-confirmation'>
                    <h3>Booking Confirmed!</h3>
                    <p><strong>Topic:</strong> ".htmlspecialchars($session['topic_name'])."</p>
                    <p><strong>Time:</strong> ".htmlspecialchars($session['day_of_week'])." at ".htmlspecialchars($session['start_time'])."</p>
                    <p><strong>Student:</strong> ".htmlspecialchars($name)."</p>
                    <p><strong>Email:</strong> ".htmlspecialchars($email)."</p>
                    </div>";
                    
                    // Reset form values after successful booking
                    $selected_topic = '';
                    $selected_session = '';
                    $name = '';
                    $email = '';
                } else {
                    $pdo->rollBack();
                    $booking_message = "<p class='error-message'>Sorry, the selected session is no longer available. Please choose another session.</p>";
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                $booking_message = "<p class='error-message'>Booking error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            $booking_message = "<p class='error-message'>Please fill all required fields.</p>";
        }
    }

    // Get all topics with available sessions
    $topics = $pdo->query("
        SELECT DISTINCT topic_name, 
               SUM(available) as total_available,
               MAX(capacity) as capacity
        FROM training_sessions
        GROUP BY topic_name
        HAVING total_available > 0
        ORDER BY topic_name
    ")->fetchAll();

    // Get sessions for selected topic 
    $sessions = [];
    if (!empty($selected_topic)) {
        $stmt = $pdo->prepare("
            SELECT session_id, day_of_week, start_time, 
                   available as display_available,
                   capacity
            FROM training_sessions
            WHERE topic_name = ? AND available > 0
            ORDER BY 
                FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'),
                start_time
        ");
        $stmt->execute([$selected_topic]);
        $sessions = $stmt->fetchAll();
    }

    // Display the form
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Training Session Booking System</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                max-width: 60em;
                margin: 0 auto;
                padding: 1.25em;
            }
            h1, h2 {
                color: #2c3e50;
                margin-bottom: 0.75em;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 1em 0;
                box-shadow: 0 0.125em 0.25em rgba(0,0,0,0.1);
            }
            th, td {
                padding: 0.75em;
                text-align: left;
                border-bottom: 0.0625em solid #ddd;
            }
            th {
                background-color: #3498db;
                color: white;
            }
            tr:nth-child(even) {
                background-color: #f2f2f2;
            }
            form {
                background-color: #f9f9f9;
                padding: 1.25em;
                border-radius: 0.3125em;
                margin-bottom: 1.25em;
                box-shadow: 0 0.125em 0.25em rgba(0,0,0,0.1);
            }
            form section {
                margin-bottom: 0.9375em;
            }
            label {
                display: inline-block;
                width: 9.375em;
                font-weight: bold;
            }
            input[type="text"], 
            input[type="email"], 
            select {
                padding: 0.5em;
                width: 100%;
                max-width: 25em;
                border: 0.0625em solid #ddd;
                border-radius: 0.25em;
            }
            button {
                padding: 0.625em 1.25em;
                background-color: #3498db;
                color: white;
                border: none;
                border-radius: 0.25em;
                cursor: pointer;
                font-size: 1em;
                transition: background-color 0.3s;
            }
            button:hover {
                background-color: #2980b9;
            }
            .booking-confirmation {
                border: 0.0625em solid #2ecc71;
                padding: 1em;
                margin: 1em 0;
                background-color: #e8f8f0;
                border-radius: 0.25em;
            }
            .error-message {
                color: #e74c3c;
                padding: 0.625em;
                border: 0.0625em solid #e74c3c;
                background-color: #fdecea;
                border-radius: 0.25em;
            }
            .availability {
                margin-left: 0.5em;
                color: #27ae60;
                font-weight: bold;
            }
            select option:disabled {
                color: #999;
            }
        </style>
    </head>
    <body>
        <h1>Training Session Booking System</h1>';
    
    // Display booking message if exists
    if (!empty($booking_message)) {
        echo $booking_message;
    }

    echo '<form method="post">';

    // The code for the following input type="hidden" has been obtained from
    // ChatGPT: Provide PHP code to meet this requirement:"One should expect that 
    // all `inputs' to the application, even those that are intended to come from a menu, 
    // will be used by malicious users to inject code that causes your application to malfunction 
    // or reveal the contents of the database. You should program the application in a way that safeguards against such attacks.
    // OpenAI, ChatGPT 13 May 2024 Version.
    // https://chat.openai.com/chat [accessed 3 April 2025]

    echo '<input type="hidden" name="csrf_token" value="'.htmlspecialchars($_SESSION['csrf_token']).'">
        <section>
            <label for="name">Full Name:</label>
            <input type="text" id="name" name="name" value="'.htmlspecialchars($name).'" required>
        </section>
        <section>
            <label for="email">Email Address:</label>
            <input type="email" id="email" name="email" value="'.htmlspecialchars($email).'" required>
        </section>
        <section>
            <label for="topic">Select Topic:</label>
            <select id="topic" name="topic" required onchange="this.form.submit()">
                <option value="">-- Select a Topic --</option>';

    
    foreach ($topics as $topic) {
        $selected = ($topic['topic_name'] == $selected_topic) ? ' selected' : '';
        $disabled = ($topic['total_available'] <= 0) ? ' disabled' : '';
        echo '<option value="'.htmlspecialchars($topic['topic_name']).'"'.$selected.$disabled.'>
                '.htmlspecialchars($topic['topic_name']).' (Capacity: '.$topic['capacity'].', Available: '.$topic['total_available'].')
              </option>';
    }
    
    echo '</select>
        </section>';
    
    if (!empty($selected_topic)) {
        echo '<section>
            <label for="session">Select Time Slot:</label>
            <select id="session" name="session" required>
                <option value="">-- Select a Time Slot --</option>';
        
        foreach ($sessions as $session) {
            $selected = ($session['session_id'] == $selected_session) ? ' selected' : '';
            echo '<option value="'.$session['session_id'].'"'.$selected.'>
                    '.$session['day_of_week'].' at '.$session['start_time'].' ('.$session['display_available'].' available)
                  </option>';
        }
        
        echo '</select>';
        
        if (!empty($selected_session)) {
            foreach ($sessions as $session) {
                if ($session['session_id'] == $selected_session) {
                    echo '<span class="availability">'.$session['display_available'].' spots available</span>';
                    break;
                }
            }
        }
        
        echo '</section>';
    }
    
    echo '<button type="submit" name="book">Book Session</button>
    </form>';

    // Display All Bookings in a table
    echo '<h2>All Bookings</h2>';
    $stmt = $pdo->query("
        SELECT b.booking_id, s.topic_name, s.day_of_week, s.start_time,
               b.name as student_name, b.email as student_email, b.booking_date
        FROM bookings b
        JOIN training_sessions s ON b.session_id = s.session_id
        ORDER BY b.booking_date DESC
    ");
    
    echo '<table>
        <tr>
            <th>Topic</th>
            <th>Day</th>
            <th>Time</th>
            <th>Student Name</th>
            <th>Email</th>
            <th>Booking Date</th>
        </tr>';
    
    foreach ($stmt as $row) {
        echo '<tr>
            <td>'.htmlspecialchars($row['topic_name']).'</td>
            <td>'.htmlspecialchars($row['day_of_week']).'</td>
            <td>'.htmlspecialchars($row['start_time']).'</td>
            <td>'.htmlspecialchars($row['student_name']).'</td>
            <td>'.htmlspecialchars($row['student_email']).'</td>
            <td>'.htmlspecialchars($row['booking_date']).'</td>
        </tr>';
    }
    echo '</table>
    </body>
    </html>';

    // Global database error handler - prevents uncaught exceptions
} catch (PDOException $e) {
    echo '<div class="error-message">
        <h3>Database Error</h3>
        <p>'.htmlspecialchars($e->getMessage()).'</p>
    </div>';
}
?>