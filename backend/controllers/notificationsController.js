const pool = require('../db/pool');

exports.getNotifications = async (req, res) => {
    const lastSeen = req.session.fb_last_seen || 0;
    try {
        const [count, items] = await Promise.all([
            pool.query('SELECT COUNT(*) AS cnt FROM feedback WHERE id > $1', [lastSeen]),
            pool.query(`SELECT id, name, experience, SUBSTRING(message,1,60) AS message, created_at
                        FROM feedback WHERE id > $1 ORDER BY id DESC LIMIT 5`, [lastSeen])
        ]);
        return res.json({ count: parseInt(count.rows[0].cnt), items: items.rows });
    } catch (err) {
        return res.status(500).json({ error: err.message });
    }
};

exports.markSeen = async (req, res) => {
    try {
        const max = await pool.query('SELECT COALESCE(MAX(id),0) AS max_id FROM feedback');
        req.session.fb_last_seen = max.rows[0].max_id;
        return res.json({ success: true, last_seen: max.rows[0].max_id });
    } catch (err) {
        return res.status(500).json({ error: err.message });
    }
};
