# Sabawyan Hotel — Node.js Backend

## Setup

```bash
cd backend
npm install
npm run dev
```

API runs on: `http://localhost:3000`

## API Endpoints

| Method | Route | Description | Role |
|---|---|---|---|
| POST | /api/auth/login | Login | Public |
| POST | /api/auth/signup | Sign up | Public |
| GET  | /api/auth/logout | Logout | Any |
| POST | /api/auth/forgot-password/hint | Get security hint | Public |
| POST | /api/auth/forgot-password/verify | Verify + reset | Public |
| POST | /api/auth/change-password | Change password | Guest |
| GET  | /api/rooms | List rooms | Any logged-in |
| GET  | /api/rooms/available | Available rooms | Any logged-in |
| GET  | /api/rooms/:rn/price | Room price | Any logged-in |
| POST | /api/rooms | Add room | Manager |
| PUT  | /api/rooms/:rn | Update room | Manager/Finance |
| POST | /api/checkin | Check in guest | Manager/Finance |
| GET  | /api/checkin/active | Active guests | Manager/Finance |
| POST | /api/checkout | Check out | Manager/Finance |
| GET  | /api/checkout/guest | My booking | Guest |
| GET  | /api/guests | All active guests | Manager/Finance |
| GET  | /api/guests/me | My booking | Guest |
| GET  | /api/guests/archive | Checkout archive | Manager/Finance |
| PUT  | /api/guests/:rn | Update guest | Manager/Finance |
| GET  | /api/finance/kpi | KPI metrics | Finance/Manager |
| GET  | /api/finance/reports | Full reports | Finance/Manager |
| GET  | /api/finance/payment-approval | Payment list | Finance/Manager |
| POST | /api/finance/payment-approval/:rn | Approve payment | Finance/Manager |
| GET  | /api/housekeeping | Pending tasks | Staff+ |
| GET  | /api/housekeeping/rooms | All rooms | Staff+ |
| PUT  | /api/housekeeping/rooms/:rn | Update cleanliness | Staff+ |
| PUT  | /api/housekeeping/tasks/:id | Update task | Staff+ |
| GET  | /api/notifications | Unread feedback | Manager/Finance |
| POST | /api/notifications/mark-seen | Mark as read | Manager/Finance |
| POST | /api/public/feedback | Submit feedback | Public |
| POST | /api/public/contact | Submit contact | Public |
| GET  | /api/public/rooms | Room availability | Public |

## Switch from PHP to Node.js

Change form actions in HTML from:
```
action="../../php/auth/login.php"
```
To:
```
action="http://localhost:3000/api/auth/login"
```
Or use `fetch()` for all API calls.
