# Sabawyan Hotel Management System — API Documentation

Base URL: `http://localhost/php/php/api/`

All protected endpoints require a valid session (login first via the auth page).  
All responses are JSON.

---

## Authentication

| Method | Endpoint | Description |
|---|---|---|
| POST | `../auth/login.php` | Login — sets session |
| GET  | `../auth/logout.php` | Logout — destroys session |

**Login credentials:**
| Role | Email | Password |
|---|---|---|
| Manager | manager@sabawyan.com | manager123 |
| Finance | finance@sabawyan.com | finance123 |
| Staff   | staff@sabawyan.com   | staff123   |

---

## Rooms

### List all rooms
```
GET /rooms.php
GET /rooms.php?status=Available
```
Response:
```json
{ "success": true, "data": [{ "id": 1, "room_number": "101", "status": "Available", "effective_price": 800.00 }] }
```

### Check availability
```
GET /rooms.php?available=1&check_in=2025-07-01&check_out=2025-07-05&bed_type=Double&ac_type=AC
```

### Add room (Manager only)
```
POST /rooms.php
Body: { "room_number": "205", "room_type_id": 2, "bed_type": "Double", "ac_type": "AC", "floor": 2 }
```

### Update room status
```
PUT /rooms.php?id=1
Body: { "status": "Maintenance", "notes": "AC repair needed" }
```
Valid statuses: `Available`, `Reserved`, `Occupied`, `Cleaning`, `Maintenance`

---

## Guests

### Search guests
```
GET /guests.php?search=Ashenafi
```

### Get guest + stay history
```
GET /guests.php?id=1
```

### Create guest
```
POST /guests.php
Body: {
  "full_name": "Ashenafi Hadush",
  "email": "ashenafi@email.com",
  "phone": "0912345678",
  "nationality": "Ethiopian",
  "gender": "Male",
  "id_type": "NationalID",
  "id_number": "ETH123456",
  "address": "Mekelle, Tigray"
}
```

### Update guest
```
PUT /guests.php?id=1
Body: { "phone": "0987654321", "address": "Addis Ababa" }
```

---

## Reservations

### List reservations
```
GET /reservations.php
GET /reservations.php?status=Confirmed
```

### Get one reservation
```
GET /reservations.php?id=1
```

### Create reservation
```
POST /reservations.php
Body: {
  "guest_id": 1,
  "room_id": 3,
  "check_in_date": "2025-07-10",
  "check_out_date": "2025-07-15",
  "adults": 2,
  "children": 0,
  "special_requests": "High floor preferred",
  "source": "Online"
}
```
Response:
```json
{ "success": true, "data": { "id": 12, "reservation_code": "ARG-2025-00012", "total_amount": 6000.00, "nights": 5 } }
```

**Validation rules:**
- Check-out must be after check-in
- Check-in cannot be in the past
- Room must not be Occupied or under Maintenance
- Room must not have overlapping confirmed reservations

### Cancel reservation
```
DELETE /reservations.php?id=1&reason=Guest+request
```

---

## Check-In

### Check in from reservation
```
POST /checkin.php
Body: { "type": "reservation", "reservation_id": 12, "id_verified": true }
```

### Walk-in check-in
```
POST /checkin.php
Body: {
  "type": "walkin",
  "guest_id": 1,
  "room_id": 3,
  "expected_checkout": "2025-07-15",
  "id_verified": true,
  "adults": 1,
  "children": 0
}
```

**Validation rules:**
- Cannot check in a Cancelled or NoShow reservation
- Room must be Available (for walk-in)
- Only Confirmed reservations can be checked in
- Expected checkout must be a future date

### Get active check-ins
```
GET /checkin.php?active=1
```

---

## Check-Out

### Process checkout
```
POST /checkout.php
Body: { "checkin_id": 5 }
```

Response:
```json
{
  "success": true,
  "data": {
    "invoice_id": 8,
    "nights_stayed": 5,
    "room_charge": 6000.00,
    "service_charge": 230.00,
    "total": 6230.00
  }
}
```

**If unpaid balance exists:**
```json
{ "success": false, "message": "Unpaid balance exists.", "invoice_id": 8, "balance": 6230.00 }
```

**Manager force checkout (override unpaid):**
```
POST /checkout.php
Body: { "checkin_id": 5, "force_checkout": true }
```

**After checkout:**
- Room status → `Cleaning`
- Housekeeping task created automatically
- When housekeeping marks Done → Room status → `Available`

---

## Invoices & Payments

### Get invoice for a check-in
```
GET /invoices.php?checkin_id=5
```

### Record payment
```
POST /invoices.php?action=payment
Body: {
  "invoice_id": 8,
  "amount": 3000.00,
  "method": "Cash",
  "reference": ""
}
```
Payment methods: `Cash`, `Card`, `BankTransfer`, `Mobile`

**Validation rules:**
- Amount must be > 0
- Amount cannot exceed outstanding balance
- Every payment must be linked to an invoice

---

## Housekeeping

### Get pending tasks
```
GET /housekeeping.php
```

### Update task status
```
PUT /housekeeping.php?id=3
Body: { "status": "Done" }
```
Valid statuses: `Pending` → `InProgress` → `Done`

When `Done`:
- Room cleaning_status → `Clean`
- Room status → `Available`

### Assign task to staff
```
PUT /housekeeping.php?id=3
Body: { "assigned_to": 4 }
```

---

## Inventory

### List items (with low-stock alerts)
```
GET /inventory.php
GET /inventory.php?low_stock=1
```

### Stock in
```
PUT /inventory.php?id=2
Body: { "type": "StockIn", "quantity": 50, "reason": "Monthly restock" }
```

### Stock out
```
PUT /inventory.php?id=2
Body: { "type": "StockOut", "quantity": 5, "reason": "Room 101 supply" }
```

---

## Reports (Finance role required)

### Daily revenue
```
GET /reports.php?type=daily_revenue&date=2025-06-15
```

### Monthly revenue
```
GET /reports.php?type=monthly_revenue&year=2025&month=6
```

### Occupancy rate
```
GET /reports.php?type=occupancy
```

### Guest statistics
```
GET /reports.php?type=guest_stats
```

### Reservation statistics
```
GET /reports.php?type=reservation_stats&from=2025-06-01&to=2025-06-30
```

### Inventory report
```
GET /reports.php?type=inventory_report
```

---

## Room Status Flow

```
Available → Reserved (on reservation creation)
Reserved  → Occupied (on check-in)
Available → Occupied (on walk-in check-in)
Occupied  → Cleaning (on check-out)
Cleaning  → Available (when housekeeping marks Done)
Any       → Maintenance (manual update)
Maintenance → Available (manual update)
```

## Error Responses

```json
{ "success": false, "message": "Error description here." }
{ "success": false, "message": "Validation failed.", "errors": { "field": "message" } }
```

HTTP Status Codes:
- `200` OK
- `201` Created
- `400` Bad Request / Validation error
- `401` Unauthenticated
- `403` Forbidden (wrong role)
- `404` Not Found
- `405` Method Not Allowed
- `422` Business logic error (e.g. unpaid balance)
