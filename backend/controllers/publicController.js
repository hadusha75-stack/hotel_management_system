const pool = require('../db/pool');

exports.submitFeedback = async (req, res) => {
    const { name, email, experience, message } = req.body;
    if (!name || !email || !experience || !message)
        return res.status(400).json({ error: 'All fields are required.' });
    try {
        await pool.query('INSERT INTO feedback (name,email,experience,message) VALUES ($1,$2,$3,$4)',
            [name, email, experience, message]);
        return res.json({ success: true, message: 'Feedback submitted successfully.' });
    } catch (err) {
        return res.status(500).json({ error: err.message });
    }
};

exports.submitContact = async (req, res) => {
    const { name, email, message } = req.body;
    if (!name || !email || !message)
        return res.status(400).json({ error: 'All fields are required.' });
    try {
        await pool.query('INSERT INTO contact_messages (name,email,message) VALUES ($1,$2,$3)',
            [name, email, message]);
        return res.json({ success: true, message: 'Message sent successfully.' });
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

exports.getFeedback = async (req, res) => {
    try {
        const result = await pool.query('SELECT * FROM feedback ORDER BY id DESC');
        return res.json({ success: true, data: result.rows });
    } catch (err) {
        return res.status(500).json({ error: err.message });
    }
};

exports.getContacts = async (req, res) => {
    try {
        const result = await pool.query('SELECT * FROM contact_messages ORDER BY id DESC');
        return res.json({ success: true, data: result.rows });
    } catch (err) {
        return res.status(500).json({ error: err.message });
    }
};
