const express    = require('express');
const router     = express.Router();
const controller = require('../controllers/notificationsController');
const { requireManagerFinance } = require('../middleware/auth');

router.get('/',          requireManagerFinance, controller.getNotifications);
router.post('/mark-seen',requireManagerFinance, controller.markSeen);

module.exports = router;
