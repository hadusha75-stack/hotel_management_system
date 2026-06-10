// ── Role-based access guards ──────────────────────────────────

function requireLogin(req, res, next) {
    if (!req.session?.user) {
        return res.status(401).json({ error: 'Authentication required. Please log in.' });
    }
    next();
}

function requireRole(...roles) {
    return (req, res, next) => {
        if (!req.session?.user) {
            return res.status(401).json({ error: 'Authentication required.' });
        }
        if (!roles.includes(req.session.user.role)) {
            return res.status(403).json({
                error: `Access denied. Required role: ${roles.join(' or ')}.`
            });
        }
        next();
    };
}

// Specific role guards
const requireManager        = requireRole('manager');
const requireFinance        = requireRole('finance', 'manager');
const requireManagerFinance = requireRole('manager', 'finance');
const requireStaff          = requireRole('staff', 'manager', 'finance');
const requireGuest          = requireLogin; // any logged-in user

// Audit log helper
async function auditLog(pool, userId, action, tableName = '', recordId = 0, oldValues = {}, newValues = {}, req = null) {
    try {
        await pool.query(
            `INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
             VALUES ($1,$2,$3,$4,$5,$6,$7,$8)`,
            [
                userId || null,
                action,
                tableName,
                recordId || null,
                JSON.stringify(oldValues),
                JSON.stringify(newValues),
                req?.ip || null,
                req?.get('user-agent') || null
            ]
        );
    } catch (e) { /* silently fail */ }
}

module.exports = {
    requireLogin,
    requireRole,
    requireManager,
    requireFinance,
    requireManagerFinance,
    requireStaff,
    requireGuest,
    auditLog
};
