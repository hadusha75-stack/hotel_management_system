const pool = require('../db/pool');
const { auditLog } = require('../middleware/auth');

exports.checkOut = async (req, res) => {
    const { roomnumber, checkout_date } = req.body;
    if (!roomnumber) return res.status(400).json({ error: 'roomnumber is required.' });
    try {
        const result = await pool.query('SELECT * FROM customer WHERE roomnumber=$1 LIMIT 1', [roomnumber]);
        if (result.rows.length === 0)
            return res.status(404).json({ error: 'No active guest found for this room.' });

        const guest        = result.rows[0];
        const checkoutDate = checkout_date || new Date().toISOString().split('T')[0];
        const days         = Math.max(1, Math.round((new Date(checkoutDate) - new Date(guest.checkin)) / 86400000));
        const total        = days * parseFloat(guest.priceperday);

        await pool.query(
            `INSERT INTO deleted_customers
             (name,email,mobilenumber,nationality,gender,idproof,address,
              checkin,checkout,bedtype,roomtype,priceperday,roomnumber,daystayed,totalamount)
             VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13,$14,$15)`,
            [guest.name, guest.email, guest.mobilenumber, guest.nationality,
             guest.gender, guest.idproof, guest.address, guest.checkin,
             checkoutDate, guest.bedtype, guest.roomtype, guest.priceperday,
             roomnumber, days, total]
        );
        await pool.query('DELETE FROM customer WHERE roomnumber=$1', [roomnumber]);
        await pool.query(`UPDATE rooms SET status='not booked', "cleanDerty"='Dirty' WHERE roomnumber=$1`, [roomnumber]);
        await pool.query(
            `INSERT INTO housekeeping_tasks (room_id, task_type, status)
             SELECT id, 'Cleaning', 'Pending' FROM rooms WHERE roomnumber=$1`, [roomnumber]
        );
        await auditLog(pool, null, 'CHECKOUT', 'customer', 0, {}, { roomnumber, days, total }, req);

        return res.json({ success: true, room: roomnumber, guest: guest.name, nights: days, total });
    } catch (err) {
        return res.status(500).json({ error: err.message });
    }
};

exports.getGuestBooking = async (req, res) => {
    try {
        const result = await pool.query(
            'SELECT * FROM customer WHERE email=$1 LIMIT 1', [req.session.user.email]
        );
        if (result.rows.length === 0) return res.status(404).json({ error: 'No active booking found.' });
        return res.json({ success: true, data: result.rows[0] });
    } catch (err) {
        return res.status(500).json({ error: err.message });
    }
};
