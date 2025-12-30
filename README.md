💊 Medical Prescription Upload System

A secure web application that allows patients to upload prescription images and receive quotations from pharmacies efficiently and safely.

🎯 Project Overview

Patients can upload prescriptions (images + notes + delivery details)

Pharmacies can create detailed quotations and send email notifications

Track prescription and quotation status in real-time

✅ Features
User System

User registration (Name, Email, Address, Contact, DOB)

User login

Upload prescription (Max 5 images, notes, delivery info)

Prescription status tracking

Pharmacy System

Pharmacy login

View uploaded prescriptions

Create detailed quotations with medicine list

Email notifications to users

Accept/Reject quotation handling

File Upload

Drag & drop interface

Image preview

File type validation (JPG, PNG)

Max 5MB per image, max 5 images per prescription

Quotation System

Dynamic item addition

Real-time price calculation

Accept/Reject functionality

🛠️ Tech Stack

Backend: PHP 7.4+, MySQL

Frontend: HTML5, CSS3, JavaScript, Font Awesome

Database: MySQL with PDO

File Upload: PHP file handling

Email: PHP mail() function

📁 Project Structure
prescription_system/
├── config/database.php
├── includes/
│   ├── header.php
│   └── footer.php
├── uploads/prescriptions/
├── css/style.css
├── js/script.js
├── user/
│   ├── register.php
│   ├── login.php
│   ├── dashboard.php
│   ├── upload_prescription.php
│   └── view_quotations.php
├── pharmacy/
│   ├── login.php
│   ├── dashboard.php
│   ├── view_prescriptions.php
│   └── create_quotation.php
├── api/logout.php
└── index.php

🌐 URL Structure (Localhost)
Main Entry
http://localhost/prescription_system_1/
http://localhost/prescription_system_1/index.php

User/Patient
Register: http://localhost/prescription_system_1/user/register.php
Login: http://localhost/prescription_system_1/user/login.php
Dashboard: http://localhost/prescription_system_1/user/dashboard.php
Upload Prescription: http://localhost/prescription_system_1/user/upload_prescription.php
View Quotations: http://localhost/prescription_system_1/user/view_quotations.php

Pharmacy
Login: http://localhost/prescription_system_1/pharmacy/login.php
Dashboard: http://localhost/prescription_system_1/pharmacy/dashboard.php
View Prescriptions: http://localhost/prescription_system_1/pharmacy/view_prescriptions.php
Create Quotation: http://localhost/prescription_system_1/pharmacy/create_quotation.php

Demo Pharmacy Login
Email: pharmacy@citymed.lk
Password: password

🔄 User Workflow

Patient registers and logs in

Upload prescription (images + delivery details)

Wait for quotations (email notifications)

Review & Accept/Reject quotations

Confirm order

🏥 Pharmacy Workflow

Pharmacy login

View new prescriptions

Create detailed quotations

Send quotations to patients via email

Process accepted orders

📱 Responsive Design

Mobile-friendly interface

Grid layouts for multiple screen sizes

Touch-friendly buttons and forms

Optimized image viewing

🔗 GitHub & LinkedIn

GitHub: https://github.com/manojasomarathna

LinkedIn: https://www.linkedin.com/feed/update/urn:li:activity:7375097691950809088/
