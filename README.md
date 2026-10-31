# 🎓 Student Clearance System

A web-based student clearance system built for **Debre Tabor University**.
Students can request and track their clearance online, and the responsible
offices can review and approve requests, instead of visiting each office in person.

## 📌 About the Project

Traditionally, students must visit many offices to collect signatures before
they can complete clearance. This system moves that process online, which saves
time, reduces paperwork, and makes the status of each request easy to track.

## ✨ Features

- 🔐 User registration, login, logout, and email verification
- 🔑 Forgot password / password reset
- 📝 Students can submit clearance requests and upload photos/documents
- 📊 Students can track their clearance status
- 🔔 Notifications for students about request updates
- 🚪 Gate pass for students who completed clearance
- 👥 Role-based access (student, dormitory office, administrator)
- 🛠️ Admin panel to view and manage users



## 🔒 Security

- Passwords are hashed (bcrypt)
- Secure session cookies (`HttpOnly`, `SameSite`, strict mode)
- Session hijacking protection using a browser/IP fingerprint

## 🛠️ Built With

- **Frontend:** HTML, CSS
- **Backend:** PHP
- **Database:** MySQL

## 🚀 How to Run Locally

1. Install [XAMPP](https://www.apachefriends.org/)
2. Clone this repository into the `htdocs` folder:
   ```
   git clone https://github.com/seada-moh/StudentClearanceSystem.git
   ```
3. Start **Apache** and **MySQL** in XAMPP
4. Open phpMyAdmin and create a database named `dtuscms`
5. Import the `db.sql` file into that database
6. Open `http://localhost/StudentClearanceSystem` in your browser

### Admin account

`db.sql` includes a demo admin account for testing. Generate your own
password hash with `password_hash()` in PHP and replace it in `db.sql`
before using the system.

## 📸 Screenshots



## 🔮 Future Improvements

- Rebuild with the MERN stack
- Email notifications
- Improve session fingerprinting for users who switch networks
- Use a dedicated database user instead of `root`

## 👩‍💻 Author

**Seada Muhamed**
4th Year Computer Science Student, Debre Tabor University
GitHub: [@seada-moh](https://github.com/seada-moh)
