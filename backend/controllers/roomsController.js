const pool = require('../db/pool');
const { auditLog } = require('../middleware/auth');

exports.getAll = async (req, res) => {
    try {
        const { status, bed_type, ac_type } = req.query;
        let sql = 'SELECT * FROM rooms WHERE is_active=1';
        const params = [];
        if (status)   { params.push(status);   sql += ` AND status=$${params.length}`; }
        if (bed_type) { params.push(bed_type); sql += ` AND bedtype=$${params.length}`; }
        if (ac_type)  { params.push(ac_type);  sql += ` AND roomtype=$${params.length}`; }
        sql += ' ORDER BY roomnumber';
        const result = await pool.query(sql, params);
        return res.json({ success: true, data: result.rows });
    } catch (err) {
        return res.status(500).json({ error: err.message });
    }
};

exports.getAvailable = async (req, res) => {
    try {
        const { bed_type, room_type } = req.query;
        let sql = `SELECT roomnumber, price, bedtype, roomtype FROM rooms
                   WHERE status='not booked' AND is_active=1`;
        const params = [];
        if (bed_type)  { params.push(bed_type);  sql += ` AND bedtype=$${params.length}`; }
        if (room_type) { params.push(room_type); sql += ` AND roomtype=$${params.length}`; }
        sql += ' ORDER BY roomnumber';
        const result = await pool.query(sql, params);
        return res.json({ success: true, data: result.rows });
    } catch (err) {
        return res.status(500).json({ error: err.message });
    }
};

exports.getPrice = async (req, res) => {
    try {
        const result = await pool.query(
            'SELECT price, bedtype, roomtype FROM rooms WHERE roomnumber=$1',
            [req.params.roomnumber]
        );
        if (result.rows.length === 0) return res.status(404).json({ error: 'Room not found.' });
        return res.json({ success: true, data: result.rows[0] });
    } catch (err) {
        return res.status(500).json({ error: err.message });
    }
};

exports.addRoom = async (req, res) => {
    const { roomnumber, roomtype, bedtype, price } = req.body;
    if (!roomnumber || !roomtype || !bedtype || !price)
        return res.status(400).json({ error: 'roomnumber, roomtype, bedtype, price are required.' });
    try {
        await pool.query(
            `INSERT INTO rooms (roomnumber, roomtype, bedtype, price, status, "cleanDerty")
             VALUES ($1,$2,$3,$4,'not booked','Clean')`,
            [roomnumber, roomtype, bedtype, price]
        );
        await auditLog(pool, req.session.user?.id, 'ROOM_ADDED', 'rooms', 0, {}, req.body, req);
        return res.status(201).json({ success: true, message: 'Room added successfully.' });
    } catch (err) {
        if (err.code === '23505') return res.status(409).json({ error: 'Room number already exists.' });
        return res.status(500).json({ error: err.message });
    }
};

exports.updateRoom = async (req, res) => {
    const { status, cleanDerty } = req.body;
    const sets = []; const params = [];
    if (status)     { params.push(status);     sets.push(`status=$${params.length}`); }
    if (cleanDerty) { params.push(cleanDerty); sets.push(`"cleanDerty"=$${params.length}`); }
    if (sets.length === 0) return res.status(400).json({ error: 'Nothing to update.' });
    params.push(req.params.roomnumber);
    try {
        await pool.query(`UPDATE rooms SET ${sets.join(',')} WHERE roomnumber=$${params.length}`, params);
        return res.json({ success: true, message: 'Room updated.' });
    } catch (err) {
        return res.status(500).json({ error: err.message });
    }
};
