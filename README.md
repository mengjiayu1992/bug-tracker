# 🐛 Bug Tracker System

A web-based bug tracking system developed as a group project for Software Engineering II.

## 📌 Overview
This system allows users to submit bugs, staff to resolve them, and administrators to manage the entire workflow. It supports role-based access control and provides a complete bug lifecycle from submission to resolution.

## 👥 Roles
- **User**
  - Submit bugs
  - View bug status
  - Rate resolved bugs
  - Add comments

- **Staff**
  - View assigned bugs
  - Accept and work on bugs
  - Submit solutions
  - Add comments

- **Admin**
  - Assign bugs to staff
  - Approve or reject solutions
  - Manage staff accounts
  - Edit bug details

## 🔄 Bug Workflow
User → Submit Bug  
Admin → Assign Bug  
Staff → Fix Bug  
Admin → Approve Solution  
User → Rate Solution  

## ⚙️ Features
- Role-based dashboards (Admin / Staff / User)
- Bug submission and tracking
- Ticket number system
- Status management (New, Assigned, In Progress, Pending Approval, Resolved)
- Comment system
- Rating system (1–5 stars)
- Search functionality

## 🛠️ Technologies Used
- Frontend: PHP, HTML, CSS
- Backend: MySQL
- Tools: phpMyAdmin, cPanel

## 🔒 Security
- Password hashing
- Role-based access control
- Prepared SQL statements (prevent SQL injection)

## 🧪 Testing
- Login authentication testing
- Role-based access testing
- Bug workflow testing
- Database validation

## 📉 Limitations
- No multi-factor authentication
- Basic search functionality
- No file upload support
- Limited UI responsiveness

## 👨‍💻 My Contribution
- Designed and implemented role-based home pages
- Developed Admin, Staff, and User dashboards
- Implemented session-based access control
- Contributed to UI design and CSS styling
- Tested login redirection and role routing
