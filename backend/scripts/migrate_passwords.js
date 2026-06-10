/**
 * ONE-TIME SCRIPT: Migrate plain-text passwords to bcrypt hashes
 * Run once: node backend/scripts/migrate_passwords.js
 * Delete this file after running!
 *
 * This script:
 * 1. Reads all customer_login records
 * 2. If password doesn't start with "$2b$" (not yet hashed)
 * 3. Hashes it with bcrypt and updates the DB
 */

require('dotenv').config({ path: require('path').join(__dirname, '../.env') });
const pool   = require('../db/pool');
const bcrypt = require('bcryptjs');

async function migratePasswords() {
    console.log('🔐 Starting password migration...\n');

    const result = await pool.query('SELECT id, email, password FROM customer_login');
    let migrated = 0, skipped = 0;

    for (const user of result.rows) {
        // Skip already hashed passwords (bcrypt hashes start with $2b$ or $2a$)
        if (user.password.startsWith('$2b$') || user.password.startsWith('$2a$')) {
            console.log(`  ✓ SKIP  ${user.email} (already hashed)`);
            skipped++;
            continue;
        }

        // Hash the plain-text password
        const hashed = await bcrypt.hash(user.password, 12);
        await pool.query('UPDATE customer_login SET password=$1 WHERE id=$2', [hashed, user.id]);
        console.log(`  ✅ DONE  ${user.email} → hashed`);
        migrated++;
    }

    console.log(`\nMigration complete: ${migrated} migrated, ${skipped} already hashed.`);
    console.log('⚠️  DELETE this script file now!');
    await pool.end();
}

migratePasswords().catch(err => {
    console.error('Migration failed:', err);
    process.exit(1);
});
