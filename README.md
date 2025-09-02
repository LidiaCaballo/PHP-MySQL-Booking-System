> **Disclaimer:** This project was developed as part of a **University of Liverpool** assignment.  
> It is for **educational purposes only** and **must not be copied, reused, or distributed** without permission, in accordance with the University's academic integrity policies.


# Training Session Booking System

A **secure PHP & MySQL application** for booking training sessions.  
It provides **real-time session availability**, safe booking handling, and protection against common security risks like **CSRF attacks** and **SQL injection**.

## Features
- **Session Management**: View topics, available slots, and book in real time.
- **Booking Confirmation**: Displays session, participant, and schedule details.
- **Security**:
  - CSRF token validation
  - Prepared statements to prevent SQL injection
  - Session timeout and regeneration for added safety
- **Admin View**: Displays all bookings in a table format.

## Tech Stack
- **Backend:** PHP (PDO for database access)
- **Database:** MySQL
- **Frontend:** HTML, CSS (inline styling)
- **Security:** CSRF tokens, session timeout, and input sanitization

## How to Run
1. Clone the repository.
2. Create a MySQL database and import the schema for `training_sessions` and `bookings`.
3. Update database credentials in:
   ```php
   $db_hostname = 'your_host';
   $db_database = 'your_database';
   $db_username = 'your_user';
   $db_password = 'your_password';
   $db_charset  = 'utf8mb4';
