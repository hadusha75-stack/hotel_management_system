const express    = require('express');
const router     = express.Router();
const controller = require('../controllers/authController');

router.post('/login',                    controller.login);
router.post('/signup',                   controller.signup);
router.post('/forgot-password/hint',     controller.getHint);
router.post('/forgot-password/verify',   controller.verifyAndReset);
router.post('/change-password',          controller.changePassword);
router.get('/logout',                    controller.logout);
router.get('/me',                        controller.me);

module.exports = router;
