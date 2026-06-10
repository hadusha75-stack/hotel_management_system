const express    = require('express');
const router     = express.Router();
const controller = require('../controllers/guestsController');
const { requireManagerFinance, requireGuest } = require('../middleware/auth');

router.get('/',                requireManagerFinance, controller.getAll);
router.get('/me',              requireGuest,          controller.getMe);
router.get('/archive',         requireManagerFinance, controller.getArchive);
router.put('/:roomnumber',     requireManagerFinance, controller.updateGuest);

module.exports = router;
