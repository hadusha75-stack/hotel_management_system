const pool = require('../db/pool');

exports.getKpi = async (req, res) => {
    try {
        const [revenue, completed, active, rooms] = await Promise.all([
            pool.query('SELECT COALESCE(SUM(totalamount),0) AS rev FROM deleted_customers'),
            pool.query('SELECT COUNT(*) AS cnt FROM deleted_customers'),
            pool.query(`SELECT COUNT(*) AS cnt,
                               COALESCE(SUM(priceperday * GREATEST(1,
                                   EXTRACT(DAY FROM (CURRENT_DATE - checkin::date))
                               )),0) AS pending
                        FROM customer WHERE checkin IS NOT NULL`),
            pool.query(`SELECT COUNT(*) AS total,
                               SUM(CASE WHEN status != 'not booked' THEN 1 ELSE 0 END) AS booked
                        FROM rooms`)
        ]);
        return res.json({ success: true, data: {
            collected_revenue: parseFloat(revenue.rows[0].rev),
            completed_stays:   parseInt(completed.rows[0].cnt),
            active_guests:     parseInt(active.rows[0].cnt),
            pending_revenue:   parseFloat(active.rows[0].pending),
            total_rooms:       parseInt(rooms.rows[0].total),
            booked_rooms:      parseInt(rooms.rows[0].booked)
        }});
    } catch (err) {
        return res.status(500).json({ error: err.message });
    }
};

exports.getReports = async (req, res) => {
    const from = req.query.from || new Date().toISOString().slice(0,7) + '-01';
    const to   = req.query.to   || new Date().toISOString().split('T')[0];
    try {
        const [monthly, range, roomsQ, feedback, topRooms, recent, active] = await Promise.all([
            pool.query(`SELECT TO_CHAR(checkout,'YYYY-MM') AS month, SUM(totalamount) AS revenue, COUNT(*) AS guests
                        FROM deleted_customers WHERE checkout >= CURRENT_DATE - INTERVAL '12 months' AND checkout IS NOT NULL
                        GROUP BY month ORDER BY month ASC`),
            pool.query(`SELECT COALESCE(SUM(totalamount),0) AS total, COUNT(*) AS checkouts,
                               COALESCE(AVG(totalamount),0) AS avg_stay, COALESCE(AVG(daystayed),0) AS avg_nights
                        FROM deleted_customers WHERE checkout BETWEEN $1 AND $2`, [from, to]),
            pool.query(`SELECT COUNT(*) AS total, SUM(CASE WHEN status != 'not booked' THEN 1 ELSE 0 END) AS booked FROM rooms`),
            pool.query('SELECT experience, COUNT(*) AS cnt FROM feedback GROUP BY experience'),
            pool.query(`SELECT roomnumber, roomtype, bedtype, SUM(totalamount) AS revenue,
                               COUNT(*) AS stays, AVG(daystayed) AS avg_nights
                        FROM deleted_customers GROUP BY roomnumber, roomtype, bedtype ORDER BY revenue DESC LIMIT 5`),
            pool.query(`SELECT name, roomnumber, roomtype, checkin, checkout, daystayed, totalamount
                        FROM deleted_customers WHERE checkout BETWEEN $1 AND $2 ORDER BY checkout DESC LIMIT 20`, [from, to]),
            pool.query(`SELECT COUNT(*) AS cnt,
                               COALESCE(SUM(priceperday * GREATEST(1, EXTRACT(DAY FROM (CURRENT_DATE - checkin::date)))),0) AS pending
                        FROM customer WHERE checkin IS NOT NULL`)
        ]);
        return res.json({ success: true, data: {
            monthly_revenue: monthly.rows, range_stats: range.rows[0],
            rooms: roomsQ.rows[0], feedback: feedback.rows,
            top_rooms: topRooms.rows, recent: recent.rows,
            active: active.rows[0], range: { from, to }
        }});
    } catch (err) {
        return res.status(500).json({ error: err.message });
    }
};

exports.getPaymentList = async (req, res) => {
    const { filter } = req.query;
    let sql = 'SELECT * FROM customer';
    if (filter === 'paid')   sql += " WHERE payment_status='Paid'";
    if (filter === 'unpaid') sql += " WHERE payment_status='Unpaid'";
    sql += ' ORDER BY checkin DESC';
    try {
        const result = await pool.query(sql);
        return res.json({ success: true, data: result.rows });
    } catch (err) {
        return res.status(500).json({ error: err.message });
    }
};

exports.approvePayment = async (req, res) => {
    const { action } = req.body;
    const approver = req.session.user?.email;
    try {
        if (action === 'approve') {
            await pool.query(
                `UPDATE customer SET payment_status='Paid', payment_approved_by=$1, payment_approved_at=NOW() WHERE roomnumber=$2`,
                [approver, req.params.roomnumber]
            );
        } else {
            await pool.query(
                `UPDATE customer SET payment_status='Unpaid', payment_approved_by=NULL, payment_approved_at=NULL WHERE roomnumber=$1`,
                [req.params.roomnumber]
            );
        }
        return res.json({ success: true });
    } catch (err) {
        return res.status(500).json({ error: err.message });
    }
};
