const pool = require('../db/pool');

exports.getTasks = async (req, res) => {
    try {
        const result = await pool.query(`
            SELECT ht.*, r.roomnumber, r.status AS room_status
            FROM housekeeping_tasks ht JOIN rooms r ON ht.room_id = r.id
            WHERE ht.status != 'Done' ORDER BY ht.created_at ASC
        `);
        return res.json({ success: true, data: result.rows });
    } catch (err) {
        return res.status(500).json({ error: err.message });
    }
};

exports.getRooms = async (req, res) => {
    try {
        const result = await pool.query('SELECT * FROM rooms ORDER BY roomnumber');
        return res.json({ success: true, data: result.rows });
    } catch (err) {
        return res.status(500).json({ error: err.message });
    }
};

exports.updateCleanliness = async (req, res) => {
    const { cleanDerty } = req.body;
    if (!cleanDerty) return res.status(400).json({ error: 'cleanDerty is required.' });
    try {
        await pool.query('UPDATE rooms SET "cleanDerty"=$1 WHERE roomnumber=$2', [cleanDerty, req.params.roomnumber]);
        return res.json({ success: true, message: `Room ${req.params.roomnumber} updated to ${cleanDerty}.` });
    } catch (err) {
        return res.status(500).json({ error: err.message });
    }
};

exports.updateTask = async (req, res) => {
    const { status } = req.body;
    const allowed = ['Pending','InProgress','Done'];
    if (!allowed.includes(status))
        return res.status(400).json({ error: `Status must be: ${allowed.join(', ')}` });
    try {
        let sql = 'UPDATE housekeeping_tasks SET status=$1';
        if (status === 'InProgress') sql += ', started_at=NOW()';
        if (status === 'Done')       sql += ', completed_at=NOW()';
        sql += ' WHERE id=$2';
        await pool.query(sql, [status, req.params.id]);
        if (status === 'Done') {
            await pool.query(
                `UPDATE rooms SET status='not booked', "cleanDerty"='Clean'
                 WHERE id = (SELECT room_id FROM housekeeping_tasks WHERE id=$1)`, [req.params.id]
            );
        }
        return res.json({ success: true });
    } catch (err) {
        return res.status(500).json({ error: err.message });
    }
};
