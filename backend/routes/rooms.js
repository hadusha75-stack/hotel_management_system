const express    = require('express');
const router     = express.Router();
const controller = require('../controllers/roomsController');
const { requireLogin, requireManager, requireManagerFinance } = require('../middleware/auth');

router.get('/',                    requireLogin,          controller.getAll);
router.get('/available',           requireLogin,          controller.getAvailable);
router.get('/:roomnumber/price',   requireLogin,          controller.getPrice);
router.post('/',                   requireManager,        controller.addRoom);
router.put('/:roomnumber',         requireManagerFinance, controller.updateRoom);

module.exports = router;
