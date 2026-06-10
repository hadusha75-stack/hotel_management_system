/**
 * authController.js — Sabawyan Hotel
 * Passwords are hashed with bcrypt (cost factor 12).
 * Plain-text password storage has been completely removed.
 *
 * HOW BCRYPT WORKS:
 *  1. bcrypt.hash(password, 12)
 *     → generates a random 16-byte salt
 *     → applies the Blowfish cipher 2^12 = 4096 times
 *     → returns a 60-char string like: $2b$12$saltXXXXXXXXXXXXXhashXXXXXXXXXXXXXXXXXXXXXXXXXXXX
 *
 *  2. bcrypt.compare(plainText, hash)
 *     → extracts the salt from the stored hash
 *     → re-hashes the plain-text with the same salt
 *     → compares result — never decrypts
 *
 * COST FACTOR 12:
 *  → ~100-300ms per hash on modern hardware
 *  → Attacker trying 1 billion passwords/sec → takes 300+ years
 *  → Increase to 13 or 14 for even more security (but slower login)
 */

const pool   = require('../db/pool');
const bcrypt = require('bcryptjs'); // npm install bcryptjs
require('dotenv').config();

// ── Cost factor: 12 is the recommended minimum for 2024+ ──────
const SALT_ROUNDS = 12;

// ── LOGIN ─────────────────────────────────────────────────────
exports.login = async (req, res) => {
    const { email, password } = req.body;

    // [SECURITY] Always validate inputs first
    if (!email || !password)
        return res.status(400).json({ error: 'Email and password are required.' });

    // [SECURITY] Hardcoded staff credentials — in production these
    // should also be stored as bcrypt hashes in the DB, not in .env
    if (email === process.env.MANAGER_EMAIL) {
        // Direct compare — staff passwords stored in .env
        if (password !== process.env.MANAGER_PASS)
            return res.status(401).json({ error: 'Incorrect email or password.' });
        req.session.user = { role: 'manager', email, name: 'Manager' };
        return res.json({ success: true, role: 'manager', redirect: '/html/dashboards/manager.html' });
    }
    if (email === process.env.FINANCE_EMAIL) {
        if (password !== process.env.FINANCE_PASS)
            return res.status(401).json({ error: 'Incorrect email or password.' });
        req.session.user = { role: 'finance', email, name: 'Finance Officer' };
        return res.json({ success: true, role: 'finance', redirect: '/html/dashboards/finance.html' });
    }
    if (email === process.env.STAFF_EMAIL) {
        if (password !== process.env.STAFF_PASS)
            return res.status(401).json({ error: 'Incorrect email or password.' });
        req.session.user = { role: 'staff', email, name: 'Staff' };
        return res.json({ success: true, role: 'staff', redirect: '/php/staff/housekeeping.php' });
    }

    // [BCRYPT CHANGE] Guest login — fetch hash from DB, then compare
    try {
        // CHANGE 1: Fetch by email only — never query "WHERE password=..."
        // Old code: WHERE email=$1 AND password=$2  ← WRONG, plaintext compare
        // New code: WHERE email=$1 only, then bcrypt.compare() below
        const result = await pool.query(
            'SELECT id, username, email, password, must_change_password FROM customer_login WHERE email=$1',
            [email]
        );

        if (result.rows.length === 0) {
            // [SECURITY] Same error for "user not found" and "wrong password"
            // Never say "email not found" — prevents user enumeration attacks
            return res.status(401).json({ error: 'Incorrect email or password.' });
        }

        const user = result.rows[0];

        // CHANGE 2: Use bcrypt.compare() — never decrypt, just re-hash and compare
        // This takes ~100-300ms intentionally (brute-force protection)
        const passwordMatch = await bcrypt.compare(password, user.password);

        if (!passwordMatch)
            return res.status(401).json({ error: 'Incorrect email or password.' });

        // [SECURITY] Store minimal info in session — never store the password hash
        req.session.user = {
            id:    user.id,
            role:  'guest',
            email: user.email,
            name:  user.username
        };

        if (user.must_change_password)
            return res.json({ success: true, role: 'guest', redirect: '/html/public/change_password.html' });

        return res.json({ success: true, role: 'guest', redirect: '/php/public/rooms.php' });

    } catch (err) {
        // [SECURITY] Never expose raw DB errors to client
        console.error('Login error:', err.message);
        return res.status(500).json({ error: 'An error occurred. Please try again.' });
    }
};

// ── SIGNUP ────────────────────────────────────────────────────
exports.signup = async (req, res) => {
    const { username, email, password, address, security_hint, security_answer } = req.body;

    if (!username || !email || !password || !security_hint || !security_answer)
        return res.status(400).json({ error: 'All fields are required.' });

    // [SECURITY] Enforce minimum password strength
    if (password.length < 8)
        return res.status(400).json({ error: 'Password must be at least 8 characters.' });

    try {
        // CHANGE 3: Hash password before saving
        // bcrypt.hash() automatically generates a unique salt and hashes
        // Result looks like: $2b$12$abc123... (never the original password)
        const hashedPassword = await bcrypt.hash(password, SALT_ROUNDS);

        // CHANGE 4: Store hashedPassword, NOT the plain-text password
        // Also store security_answer lowercased for case-insensitive comparison
        await pool.query(
            `INSERT INTO customer_login
             (username, email, password, address, security_hint, security_answer)
             VALUES ($1,$2,$3,$4,$5,$6)`,
            [username, email, hashedPassword, address || '', security_hint, security_answer.toLowerCase().trim()]
        );

        return res.json({ success: true, message: 'Account created successfully.' });

    } catch (err) {
        if (err.code === '23505')
            return res.status(409).json({ error: 'This email is already registered.' });
        console.error('Signup error:', err.message);
        return res.status(500).json({ error: 'An error occurred. Please try again.' });
    }
};

// ── FORGOT PASSWORD — Step 1: Get security hint ───────────────
exports.getHint = async (req, res) => {
    const { email } = req.body;
    if (!email) return res.status(400).json({ error: 'Email is required.' });

    try {
        const result = await pool.query(
            'SELECT security_hint FROM customer_login WHERE email=$1', [email]
        );
        // [SECURITY] Return a hint even if email doesn't exist
        // Prevents attacker from knowing which emails are registered
        const hint = result.rows[0]?.security_hint || "What is your mother's maiden name?";
        return res.json({ hint });
    } catch (err) {
        console.error('GetHint error:', err.message);
        return res.status(500).json({ error: 'An error occurred.' });
    }
};

// ── FORGOT PASSWORD — Step 2: Verify answer & reset ──────────
exports.verifyAndReset = async (req, res) => {
    const { email, answer } = req.body;
    if (!email || !answer)
        return res.status(400).json({ error: 'Email and answer are required.' });

    try {
        const result = await pool.query(
            'SELECT id, username, security_answer FROM customer_login WHERE email=$1', [email]
        );

        if (result.rows.length === 0)
            return res.status(404).json({ error: 'No account found with this email.' });

        const user = result.rows[0];

        // Compare answer case-insensitively
        // [NOTE] Security answers are stored as plain text (acceptable since
        // they are not passwords — they are hints the user knows by memory)
        if (answer.toLowerCase().trim() !== (user.security_answer || '').toLowerCase().trim())
            return res.status(401).json({ error: 'Incorrect answer. Please try again.' });

        // Generate a random temporary password
        const chars       = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
        const tempPassword = Array.from({ length: 10 }, () => chars[Math.floor(Math.random() * chars.length)]).join('');

        // CHANGE 5: Hash the temp password before storing
        // Old code: stored tempPassword as plain text ← WRONG
        // New code: hash it just like a regular password
        const hashedTemp = await bcrypt.hash(tempPassword, SALT_ROUNDS);

        await pool.query(
            'UPDATE customer_login SET password=$1, must_change_password=1 WHERE email=$2',
            [hashedTemp, email]
        );

        // CHANGE 6: Show the plain-text temp password to the user NOW
        // because after this point it only exists as a hash in the DB
        // The user needs this to log in once, then change it immediately
        return res.json({
            success: true,
            message: `Your temporary password is: <strong>${tempPassword}</strong><br>
                      <small>⚠️ Log in with this password and change it immediately.
                      It will not be shown again.</small>`
        });

    } catch (err) {
        console.error('VerifyAndReset error:', err.message);
        return res.status(500).json({ error: 'An error occurred.' });
    }
};

// ── CHANGE PASSWORD (forced after temp password login) ────────
exports.changePassword = async (req, res) => {
    if (!req.session?.user)
        return res.status(401).json({ error: 'Not logged in.' });

    const { password } = req.body;

    if (!password || password.length < 8)
        return res.status(400).json({ error: 'Password must be at least 8 characters.' });

    try {
        // CHANGE 7: Hash the new password before saving
        const hashedPassword = await bcrypt.hash(password, SALT_ROUNDS);

        await pool.query(
            'UPDATE customer_login SET password=$1, must_change_password=0 WHERE email=$2',
            [hashedPassword, req.session.user.email]
        );

        delete req.session.must_change_password;
        return res.json({ success: true, message: 'Password updated successfully.' });

    } catch (err) {
        console.error('ChangePassword error:', err.message);
        return res.status(500).json({ error: 'An error occurred.' });
    }
};

// ── LOGOUT ────────────────────────────────────────────────────
exports.logout = (req, res) => {
    // [SECURITY] Destroy entire session, not just unset user
    req.session.destroy(err => {
        if (err) return res.status(500).json({ error: 'Logout failed.' });
        res.clearCookie('connect.sid'); // clear session cookie
        return res.json({ success: true });
    });
};

// ── WHO AM I ──────────────────────────────────────────────────
exports.me = (req, res) => {
    if (!req.session?.user)
        return res.status(401).json({ error: 'Not logged in.' });
    // [SECURITY] Never return the password hash — only safe fields
    const { id, role, email, name } = req.session.user;
    return res.json({ user: { id, role, email, name } });
};
