const express    = require('express');
const router     = express.Router();
const controller = require('../controllers/publicController');

router.post('/feedback',  controller.submitFeedback);
router.post('/contact',   controller.submitContact);
router.get('/rooms',      controller.getRooms);
router.get('/feedback',   controller.getFeedback);
router.get('/contacts',   controller.getContacts);

module.exports = router;
