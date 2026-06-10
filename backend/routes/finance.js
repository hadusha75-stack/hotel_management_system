const express    = require('express');
const router     = express.Router();
const controller = require('../controllers/financeController');
const { requireFinance } = require('../middleware/auth');

router.get('/kpi',                          requireFinance, controller.getKpi);
router.get('/reports',                      requireFinance, controller.getReports);
router.get('/payment-approval',             requireFinance, controller.getPaymentList);
router.post('/payment-approval/:roomnumber',requireFinance, controller.approvePayment);

module.exports = router;
