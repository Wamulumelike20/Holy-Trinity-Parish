# Holy Trinity Parish - Church Management System

A modern, secure, and responsive Church Management System and Website for Holy Trinity Parish built with PHP, MySQL, HTML, CSS, and JavaScript.

## Features

### Public Website
- **Home Page** - Welcome message, mass schedule, events, announcements, featured sermon
- **About Us** - Parish history, clergy profiles, mission & vision, leadership structure
- **Events & Announcements** - Calendar view, event registration, announcements
- **Sermons & Reflections** - Weekly sermons and spiritual content
- **Ministries** - Browse and join parish ministries
- **Departments** - View parish department information
- **Contact** - Contact form with parish information
- **Online Giving** - Secure donation portal with multiple categories

### Appointment Booking System
- Book appointments with clergy and department heads
- Real-time availability selection
- Document upload support
- Appointment status tracking (pending, approved, declined, completed)
- Admin approval workflow with private notes

### Parishioner Portal
- Personal dashboard with activity overview
- Profile management with password change
- Appointment history and management
- Donation history and tracking
- Sacramental records viewing
- Ministry membership management

### Admin Dashboard
- Comprehensive analytics and statistics
- **Appointment Management** - Approve, decline, complete appointments
- **Sacramental Records** - Baptism, Confirmation, Marriage, Funeral records
- **Certificate Generation** - Printable sacramental certificates
- **Donation Management** - Track and report on all donations
- **Event Management** - Create and manage parish events
- **Announcement Management** - Publish parish announcements
- **Department Management** - Manage departments and members
- **User Management** - Role-based access control
- **Mass Schedule** - Manage weekly service times
- **System Settings** - Configure parish settings
- **Audit Log** - Track all system activities
- **Reports & Analytics** - Comprehensive parish reports

### Security
- CSRF token protection on all forms
- Password hashing with bcrypt
- Role-based access control (Parishioner, Priest, Department Head, Admin, Super Admin)
- SQL injection prevention via prepared statements
- XSS prevention via output sanitization
- Secure session management
- Audit logging for all actions
- Upload directory protection

## Requirements

- **PHP** 7.4 or higher
- **MySQL** 5.7 or higher
- **Apache** with mod_rewrite enabled (or Nginx)
- **XAMPP/WAMP/MAMP** (for local development)

## Installation

### 1. Clone or Download
```bash
git clone <repository-url>
```
Or download and extract to your web server directory.

### 2. Set Up the Database
1. Open phpMyAdmin or MySQL command line
2. Import the database schema:
```bash
mysql -u root -p < database/schema.sql
```
Or paste the contents of `database/schema.sql` into phpMyAdmin's SQL tab.

### 3. Configure the Application
1. Open `config/database.php`
2. Update the database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'holy_trinity_parish');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 4. Set Up the Web Server
**For XAMPP/WAMP:**
1. Copy the project folder to `htdocs/holy-trinity`
2. Access via `http://localhost/holy-trinity`

**For custom Apache:**
1. Create a virtual host or alias pointing to the project directory
2. Ensure `mod_rewrite` is enabled

### 5. Set Directory Permissions
```bash
chmod -R 755 uploads/
```

### 6. Default Login
- **Email:** admin@holytrinityparish.org
- **Password:** Admin@123

> **Important:** Change the default admin password immediately after first login.

## Project Structure

```
holy-trinity/
├── admin/                  # Admin dashboard pages
│   ├── dashboard.php
│   ├── appointments.php
│   ├── appointment-detail.php
│   ├── sacraments.php
│   ├── sacrament-detail.php
│   ├── sacrament-certificate.php
│   ├── donations.php
│   ├── events.php
│   ├── announcements.php
│   ├── departments.php
│   ├── users.php
│   ├── mass-schedule.php
│   ├── settings.php
│   ├── audit-log.php
│   └── reports.php
├── api/                    # API endpoints
│   └── newsletter.php
├── appointments/           # Appointment booking
│   └── book.php
├── assets/                 # Static assets
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── main.js
├── auth/                   # Authentication
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   └── forgot-password.php
├── config/                 # Configuration
│   ├── app.php
│   └── database.php
├── database/               # Database files
│   └── schema.sql
├── donations/              # Donation pages
│   └── donate.php
├── includes/               # Shared components
│   ├── header.php
│   └── footer.php
├── pages/                  # Public pages
│   ├── about.php
│   ├── events.php
│   ├── event-detail.php
│   ├── sermons.php
│   ├── ministries.php
│   ├── departments.php
│   └── contact.php
├── portal/                 # Parishioner portal
│   ├── dashboard.php
│   ├── profile.php
│   ├── appointments.php
│   ├── donations.php
│   ├── sacraments.php
│   └── ministries.php
├── uploads/                # File uploads
│   ├── appointments/
│   ├── clergy/
│   └── events/
├── .htaccess               # Apache configuration
├── index.php               # Home page
└── README.md
```

## User Roles

| Role | Access Level |
|------|-------------|
| **Super Admin** | Full system access, settings, audit logs |
| **Admin** | Full management access |
| **Priest** | Appointments, sacraments, events, announcements |
| **Department Head** | Department-specific management |
| **Parishioner** | Portal access, bookings, donations |

## Technologies Used

- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **Fonts:** Google Fonts (Cinzel, Inter)
- **Icons:** Font Awesome 6.5
- **Design:** Custom CSS with CSS Variables, Flexbox, Grid

## License

This project is developed for Holy Trinity Parish. All rights reserved.

---

*Built with love for the Glory of God*
