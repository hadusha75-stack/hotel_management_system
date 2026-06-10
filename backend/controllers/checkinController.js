const pool = require('../db/pool');
const { auditLog } = require('../middleware/auth');

exports.checkIn = async (req, res) => {
    const { name, email, mobilenumber, nationality, gender, address, idproof,
            bedtype, roomtype, roomnumber, checkin, price } = req.body;

    const errors = {};
    if (!name || !/^[a-zA-Z]+(\s+[a-zA-Z]+)+$/.test(name)) errors.name = 'Enter first and last name.';
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errors.email = 'Enter a valid email.';
    if (!mobilenumber || !/^(\+?251[79]\d{8}|0[79]\d{8})$/.test(mobilenumber)) errors.mobilenumber = 'Enter a valid Ethiopian number.';
    if (!nationality || nationality.length < 3) errors.nationality = 'Nationality required.';
    if (!idproof || idproof.length < 4) errors.idproof = 'ID proof required.';
    if (!address || address.length < 5) errors.address = 'Address required.';
    if (!checkin) errors.checkin = 'Check-in date is required.';
    if (!roomnumber) errors.roomnumber = 'Room number required.';

    if (Object.keys(errors).length > 0)
        return res.status(400).json({ error: 'Validation failed.', errors });

    try {
        const [dupEmail, dupPhone, dupId] = await Promise.all([
            pool.query('SELECT id FROM customer WHERE email=$1 LIMIT 1', [email]),
            pool.query('SELECT id FROM customer WHERE mobilenumber=$1 LIMIT 1', [mobilenumber]),
            pool.query('SELECT id FROM customer WHERE idproof=$1 LIMIT 1', [idproof])
        ]);
        if (dupEmail.rows.length > 0) return res.status(409).json({ error: 'This email already has an active booking.', field: 'email' });
        if (dupPhone.rows.length > 0) return res.status(409).json({ error: 'This phone already has an active booking.', field: 'mobilenumber' });
        if (dupId.rows.length > 0)    return res.status(409).json({ error: 'This ID proof already has an active booking.', field: 'idproof' });

        let pricePerDay = parseFloat(price) || 0;
        if (!pricePerDay) {
            const room = await pool.query('SELECT price FROM rooms WHERE roomnumber=$1', [roomnumber]);
            pricePerDay = parseFloat(room.rows[0]?.price) || 0;
        }

        const role           = req.session.user?.role || 'guest';
        const paymentStatus  = ['manager','finance'].includes(role) ? 'Paid' : 'Unpaid';
        const approvedBy     = ['manager','finance'].includes(role) ? req.session.user?.email : null;
        const approvedAt     = ['manager','finance'].includes(role) ? new Date() : null;

        await pool.query(
            `INSERT INTO customer (name,email,mobilenumber,nationality,gender,address,idproof,
             bedtype,roomtype,roomnumber,checkin,priceperday,payment_status,payment_approved_by,payment_approved_at)
             VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13,$14,$15)`,
            [name, email, mobilenumber, nationality, gender||'Male', address, idproof,
             bedtype, roomtype, roomnumber, checkin, pricePerDay, paymentStatus, approvedBy, approvedAt]
        );
        await pool.query("UPDATE rooms SET status='booked' WHERE roomnumber=$1", [roomnumber]);
        await auditLog(pool, null, 'CHECKIN', 'customer', 0, {}, { name, roomnumber }, req);

        return res.status(201).json({
            success: true,
            message: `Guest ${name} checked in to Room ${roomnumber} at ETB ${pricePerDay}/night.`,
            payment_status: paymentStatus
        });
    } catch (err) {
        return res.status(500).json({ error: err.message });
    }
};

exports.getActive = async (req, res) => {
    try {
        const result = await pool.query('SELECT * FROM customer ORDER BY checkin DESC');
        return res.json({ success: true, data: result.rows });
    } catch (err) {
        return res.status(500).json({ error: err.message });
    }
};
