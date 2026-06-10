require('dotenv').config();
const express = require('express');
const session = require('express-session');
const cors    = require('cors');
const path    = require('path');

const app  = express();
const PORT = process.env.PORT || 3000;

// ── Middleware ────────────────────────────────────────────────
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// CORS — allow frontend pages to call the API
app.use(cors({
    origin: [
        'http://localhost',
        'http://localhost:80',
        'http://127.0.0.1',
        'https://sabawyanhotel.xo.je'
    ],
    credentials: true
}));

// Session
app.use(session({
    secret:            process.env.SESSION_SECRET || 'sabawyan_secret',
    resave:            false,
    saveUninitialized: false,
    cookie: {
        secure:   false,          // set true when using HTTPS
        httpOnly: true,
        maxAge:   8 * 60 * 60 * 1000  // 8 hours
    }
}));

// Serve static frontend files
app.use(express.static(path.join(__dirname, '..')));

// ── API Routes ────────────────────────────────────────────────
app.use('/api/auth',          require('./routes/auth'));
app.use('/api/rooms',         require('./routes/rooms'));
app.use('/api/checkin',       require('./routes/checkin'));
app.use('/api/checkout',      require('./routes/checkout'));
app.use('/api/guests',        require('./routes/guests'));
app.use('/api/finance',       require('./routes/finance'));
app.use('/api/housekeeping',  require('./routes/housekeeping'));
app.use('/api/notifications', require('./routes/notifications'));
app.use('/api/public',        require('./routes/public'));

// ── Health check ──────────────────────────────────────────────
app.get('/api/health', (req, res) => {
    res.json({
        status:  'ok',
        server:  'Sabawyan Hotel API',
        version: '1.0.0',
        time:    new Date().toISOString()
    });
});

// ── 404 handler ───────────────────────────────────────────────
app.use('/api/*', (req, res) => {
    res.status(404).json({ error: `Route ${req.method} ${req.path} not found.` });
});

// ── Start server ──────────────────────────────────────────────
app.listen(PORT, () => {
    console.log(`\nSabawyan Hotel API running on http://localhost:${PORT}`);
    console.log(`Health: http://localhost:${PORT}/api/health`);
    console.log(`Login:  POST http://localhost:${PORT}/api/auth/login\n`);
});

module.exports = app;
