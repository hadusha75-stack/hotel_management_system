const pool = require('../db/pool');

exports.getAll = async (req, res) => {
    try {
        const result = await pool.query('SELECT * FROM customer ORDER BY checkin DESC');
        return res.json({ success: true, data: result.rows });
    } catch (err) {
        return res.status(500).json({ error: err.message });
    }
};

exports.getMe = async (req, res) => {
    try {
        const result = await pool.query(
            'SELECT * FROM customer WHERE email=$1 LIMIT 1', [req.session.user.email]
        );
        return res.json({ success: true, data: result.rows[0] || null });
    } catch (err) {
        return res.status(500).json({ error: err.message });
    }
};

exports.getArchive = async (req, res) => {
    try {
        const result = await pool.query('SELECT * FROM deleted_customers ORDER BY deleted_at DESC');
        return res.json({ success: true, data: result.rows });
    } catch (err) {
        return res.status(500).json({ error: err.message });
    }
};

exports.updateGuest = async (req, res) => {
    const fields = ['name','email','mobilenumber','nationality','gender',
                    'idproof','address','checkin','checkout','bedtype','roomtype','priceperday'];
    const sets = []; const params = [];
    fields.forEach(f => {
        if (req.body[f] !== undefined) { params.push(req.body[f]); sets.push(`${f}=$${params.length}`); }
    });
    if (sets.length === 0) return res.status(400).json({ error: 'Nothing to update.' });
    params.push(req.params.roomnumber);
    try {
        await pool.query(`UPDATE customer SET ${sets.join(',')} WHERE roomnumber=$${params.length}`, params);
        return res.json({ success: true, message: 'Guest updated.' });
    } catch (err) {
        return res.status(500).json({ error: err.message });
    }
};
