const express    = require('express');
const router     = express.Router();
const controller = require('../controllers/housekeepingController');
const { requireStaff } = require('../middleware/auth');

router.get('/',                    requireStaff, controller.getTasks);
router.get('/rooms',               requireStaff, controller.getRooms);
router.put('/rooms/:roomnumber',   requireStaff, controller.updateCleanliness);
router.put('/tasks/:id',           requireStaff, controller.updateTask);

module.exports = router;
