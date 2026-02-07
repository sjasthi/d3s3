# Database Files

This directory contains SQL files for setting up the D3S3 CareSystem database.

## Files

- **`schema.sql`** - Database structure (tables, indexes, constraints)
- **`test_data.sql`** - Sample test data for development/testing
- **`init.sql`** - Combined schema + test data (optional, for quick setup)

## Usage

### On Bluehost (Production)
1. Log into cPanel → phpMyAdmin
2. Create a new database (note the name)
3. Import `schema.sql` only (no test data in production)

### Local Development (XAMPP)
```bash
# Import schema
mysql -u root -p your_db_name < database/schema.sql

# Import test data
mysql -u root -p your_db_name < database/test_data.sql
```

Or use a combined file:
```bash
mysql -u root -p your_db_name < database/init.sql
```

## Important Notes

- **Test data only** - Contains no real personal information
- Do NOT export production data to this directory
- Production backups should never be committed to Git
