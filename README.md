# 🏨 Sabawyan Hotel Management System

A full-stack hotel management web application built with **PHP**, **MySQL**, **HTML/CSS**, and **JavaScript**. Designed to manage guests, rooms, billing, housekeeping, and communications — with separate dashboards for each role.

---

## ✨ Features

| Module | Description |
|---|---|
| 🔐 Authentication | Login & Sign Up for Guests, Manager, Finance, and Staff |
| 🛎️ Check In / Out | Register arriving guests and process departures |
| 🛏️ Room Management | Add rooms, track availability and cleanliness |
| 👥 Guest Records | View, update, and archive guest details |
| 💰 Finance Dashboard | Revenue KPIs, billing archive, pending revenue |
| 🔔 Notifications | Real-time feedback alerts for the manager |
| 🧹 Housekeeping | Staff dashboard to update room cleanliness status |
| 📬 Communications | View contact messages and guest feedback |
| 🌐 Public Pages | About Us, Contact Us, Feedback, Room Availability |

---

## 🗂️ Project Structure

```
sabawyan-hotel/
│
├── 📁 html/                        # Frontend pages
│   ├── index.html                  # Landing / home page
│   ├── auth.html                   # Login & Sign Up (all roles)
│   ├── manager_page.html           # Manager dashboard
│   ├── finance.html                # Finance officer dashboard
│   ├── owner_dashboard.html        # Hotel owner dashboard
│   ├── about.html                  # About the hotel
│   ├── contact.html                # Contact form page
│   ├── feedback.html               # Guest feedback form
│   └── rate.html                   # Rating page
│
├── 📁 php/                         # Backend logic
│   │
│   ├── 🔐 Auth
│   │   ├── auth_login.php          # Login handler (all roles)
│   │   └── auth_signup.php         # Guest registration
│   │
│   ├── 🛎️ Manager / Finance Operations
│   │   ├── checkin_manager.php         # Check in a guest
│   │   ├── checkout_manager.php        # Check out a guest
│   │   ├── guest_details_manager.php   # View all active guests
│   │   ├── update_manager.php          # Edit guest record
│   │   └── billing_archive_manager.php # Checked-out guest records
│   │
│   ├── 👤 Guest Self-Service
│   │   ├── checkin_guest.php           # Guest self check-in
│   │   ├── checkout_guest.php          # Guest self check-out
│   │   ├── guest_details_customer.php  # Guest view own details
│   │   ├── update_guest.php            # Guest update own info
│   │   └── billing_archive_customer.php# Guest past stay records
│   │
│   ├── 🛏️ Room Management
│   │   ├── add_room.php                # Add a new room
│   │   ├── rooms_public.php            # Public room availability
│   │   ├── housekeeping_manager.php    # Manager room status view
│   │   └── housekeeping_staff.php      # Staff housekeeping dashboard
│   │
│   ├── 💬 Communications
│   │   ├── save_contact.php            # Save contact message
│   │   ├── view_contacts.php           # View contact messages
│   │   ├── save_feedback.php           # Save guest feedback
│   │   └── view_feedback.php           # View all feedback
│   │
│   └── 📡 API Endpoints
│       ├── api_notifications.php       # Unread feedback count (JSON)
│       ├── api_mark_feedback_seen.php  # Mark feedback as read
│       └── api_finance_kpi.php         # Finance KPI metrics (JSON)
│
├── 📁 css/                         # Stylesheets
│   ├── theme.css                   # Global design variables & utilities
│   ├── main.css                    # Main project styles
│   ├── auth.css                    # Auth page styles
│   ├── dashboard.css               # Manager/dashboard styles
│   ├── hotel_management.css        # Hotel management styles
│   ├── hotel_owner.css             # Hotel owner page styles
│   ├── checkin.css                 # Check-in form styles
│   ├── checkout.css                # Check-out form styles
│   ├── update.css                  # Update form styles
│   ├── rooms_public.css            # Room availability styles
│   ├── about.css                   # About page styles
│   ├── contact_us.css              # Contact page styles
│   └── feedback.css                # Feedback page styles
│
└── 📁 photo/                       # Images & icons
    ├── hotelnamebg.jpg             # Hero background
    ├── checkinbg.jpg               # Check-in background
    ├── mainhotel1.jpg              # Hotel exterior 1
    ├── mainhotel2.jpg              # Hotel exterior 2
    └── ...                         # Other UI assets
```

---

## 🚀 Getting Started

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL + PHP 8+)
- A web browser

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-username/sabawyan-hotel.git
   ```

2. **Move to XAMPP's htdocs**
   ```
   C:\xampp\htdocs\php\
   ```

3. **Start XAMPP** — enable Apache and MySQL

4. **Import the database**
   - Open `http://localhost/phpmyadmin`
   - Create a database named `web_hotel_managment`
   - Import the SQL file (if provided)

5. **Open the app**
   ```
   http://localhost/php/html/index.html
   ```

---

## 🗄️ Database Tables

| Table | Description |
|---|---|
| `customer` | Active guests currently checked in |
| `deleted_customers` | Archived records of checked-out guests |
| `rooms` | Room inventory with status and cleanliness |
| `feedback` | Guest reviews and ratings |
| `contact_messages` | Guest contact form submissions |
| `users` | Registered guest accounts |

---

## 👥 User Roles

| Role | Access | Login |
|---|---|---|
| **Guest** | Self check-in/out, view own details, feedback | Sign up or log in |
| **Manager** | Full operations, notifications, all records | `man1212ager@gmail.com` |
| **Finance** | Revenue KPIs, billing, check-in/out | `finance@gmail.com` |
| **Staff** | Housekeeping dashboard (room cleanliness) | Staff credentials |

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, JavaScript (Vanilla) |
| Backend | PHP 8 (procedural + OOP mysqli) |
| Database | MySQL (via XAMPP) |
| Icons | Font Awesome 6.5 |
| Fonts | Google Fonts — Playfair Display, Inter |

---

## 🎨 Design System

- **Colors:** Navy (`#0d1b2a`) + Gold (`#c9a84c`) primary palette
- **Typography:** Playfair Display (headings) + Inter (body)
- **Components:** Shared topbar, action cards, data tables, form cards
- **Theme file:** `css/theme.css` — all CSS variables in one place

---

## 📄 License

This project is for educational purposes.

---

## 🙏 Acknowledgements

Built with ❤️ as a hotel management system project.
