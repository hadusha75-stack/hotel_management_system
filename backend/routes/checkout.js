const express    = require('express');
const router     = express.Router();
const controller = require('../controllers/checkoutController');
const { requireManagerFinance, requireGuest } = require('../middleware/auth');

router.post('/',        requireManagerFinance, controller.checkOut);
router.get('/guest',    requireGuest,          controller.getGuestBooking);

module.exports = router;
