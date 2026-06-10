const express    = require('express');
const router     = express.Router();
const controller = require('../controllers/checkinController');
const { requireManagerFinance } = require('../middleware/auth');

router.post('/',        requireManagerFinance, controller.checkIn);
router.get('/active',   requireManagerFinance, controller.getActive);

module.exports = router;
