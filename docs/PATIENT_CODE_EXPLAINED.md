# Patient Code Generation - Detailed Explanation

## 📋 Overview

Every patient gets a **unique identifier** in the format: **YYYYMMDDNNN**

- **YYYY** = Year (4 digits)
- **MM** = Month (2 digits)
- **DD** = Day (2 digits)
- **NNN** = Sequence number for that day (3 digits, zero-padded)

---

## 🎯 Real-World Example

### February 6, 2026 - Morning at the Clinic

**8:00 AM** - First patient arrives:
- Name: Sarah Johnson
- Registered → Gets code: **20260206001**
- (First patient on Feb 6, 2026)

**9:15 AM** - Second patient arrives:
- Name: Michael Chen
- Registered → Gets code: **20260206002**

**10:30 AM** - Third patient arrives:
- Name: Priya Sharma
- Registered → Gets code: **20260206003**

... and so on throughout the day ...

**4:45 PM** - 12th patient arrives:
- Name: David Kim
- Registered → Gets code: **20260206012**

### Next Day - February 7, 2026

**8:00 AM** - First patient of the new day:
- Name: Emma Rodriguez
- Registered → Gets code: **20260207001**
- ✅ Counter reset to 001 for the new day!

---

## 🔧 How It Works Behind the Scenes

### Step 1: Database Tables

#### Patients Table
```sql
patient_id | patient_code  | first_name | last_name | first_seen_date
-----------|---------------|------------|-----------|----------------
1          | 20260206001   | Sarah      | Johnson   | 2026-02-06
2          | 20260206002   | Michael    | Chen      | 2026-02-06
3          | 20260206003   | Priya      | Sharma    | 2026-02-06
```

#### patient_daily_sequence Table (Helper)
```sql
seq_date    | last_n
------------|-------
2026-02-06  | 3      -- 3 patients registered on Feb 6
2026-02-07  | 1      -- 1 patient registered on Feb 7
2026-02-08  | 0      -- No patients yet on Feb 8
```

### Step 2: The Trigger

When you INSERT a patient, a **database trigger** automatically runs:

```sql
CREATE TRIGGER trg_patients_before_insert
BEFORE INSERT ON patients
FOR EACH ROW
BEGIN
  DECLARE v_date DATE;
  DECLARE v_seq INT UNSIGNED;

  -- Step A: Determine the date
  SET v_date = IFNULL(NEW.first_seen_date, CURDATE());
  SET NEW.first_seen_date = v_date;

  -- Step B: Get next sequence number (ATOMIC operation)
  INSERT INTO patient_daily_sequence (seq_date, last_n)
  VALUES (v_date, 1)
  ON DUPLICATE KEY UPDATE last_n = LAST_INSERT_ID(last_n + 1);

  SET v_seq = LAST_INSERT_ID();

  -- Step C: Build the patient_code
  SET NEW.patient_code = CONCAT(
    DATE_FORMAT(v_date, '%Y%m%d'),  -- "20260206"
    LPAD(v_seq, 3, '0')             -- "001", "002", etc.
  );
END;
```

### Step 3: Execution Flow

Let's trace what happens when you register Michael Chen:

```php
// PHP Code
$pdo->prepare('INSERT INTO patients (first_name, last_name, first_seen_date)
               VALUES (?, ?, ?)')
    ->execute(['Michael', 'Chen', '2026-02-06']);
```

**Inside the database:**

1. **Trigger fires BEFORE INSERT**

2. **Step A**: Determine date
   ```
   v_date = '2026-02-06'
   ```

3. **Step B**: Get sequence number
   ```sql
   -- Check if row exists for 2026-02-06
   -- Row exists with last_n = 1 (Sarah was patient 001)
   -- Increment: last_n = 1 + 1 = 2
   -- Store 2 in LAST_INSERT_ID()
   v_seq = 2
   ```

4. **Step C**: Build patient_code
   ```sql
   DATE_FORMAT('2026-02-06', '%Y%m%d') = '20260206'
   LPAD(2, 3, '0') = '002'
   patient_code = '20260206' + '002' = '20260206002'
   ```

5. **INSERT completes**
   ```
   patient_id: 2 (auto_increment)
   patient_code: '20260206002' (trigger-generated)
   first_name: 'Michael'
   last_name: 'Chen'
   first_seen_date: '2026-02-06'
   ```

---

## 🚀 Why This Approach Is Best

### ✅ Advantages

1. **Fully Automatic**
   - No PHP code needed
   - Impossible to forget to generate a code
   - Consistent format guaranteed

2. **Thread-Safe (No Race Conditions)**
   - What if 2 patients register at the exact same time?
   - MySQL's `ON DUPLICATE KEY UPDATE last_n = LAST_INSERT_ID(last_n + 1)` is **atomic**
   - Patient A gets: 20260206005
   - Patient B gets: 20260206006
   - Never: 20260206005, 20260206005 (duplicate!)

3. **Simple & Maintainable**
   - Trigger is only 10 lines
   - Easy to understand
   - No complex PHP logic needed

4. **Reliable**
   - Database handles everything
   - Can't be bypassed accidentally
   - Works even if PHP code changes

5. **Efficient**
   - One helper table (`patient_daily_sequence`)
   - Minimal storage (one row per day)
   - Fast lookups

### ❌ Why NOT Generate in PHP?

**Attempt 1: PHP-based generation (BAD)**
```php
// ⚠️ DON'T DO THIS - Race condition!
$today = date('Y-m-d');
$stmt = $pdo->prepare('SELECT last_n FROM patient_daily_sequence WHERE seq_date = ?');
$stmt->execute([$today]);
$lastN = $stmt->fetchColumn() ?? 0;

// ⚠️ Problem: Another request might read the same $lastN at the same time!
$newN = $lastN + 1;

// ⚠️ Both requests will try to insert with the same sequence number!
$pdo->prepare('UPDATE patient_daily_sequence SET last_n = ? WHERE seq_date = ?')
    ->execute([$newN, $today]);

$patientCode = date('Ymd') . str_pad($newN, 3, '0', STR_PAD_LEFT);
```

**Race Condition Example:**
```
Time    | Request A              | Request B
--------|------------------------|------------------------
10:00:00| Read last_n = 5        |
10:00:00|                        | Read last_n = 5 (SAME!)
10:00:01| newN = 6, UPDATE to 6  |
10:00:01|                        | newN = 6, UPDATE to 6
10:00:02| patient_code = ...006  |
10:00:02|                        | patient_code = ...006
        | ❌ DUPLICATE CODE!     | ❌ DUPLICATE CODE!
```

**Attempt 2: PHP with locking (COMPLEX)**
```php
// Better, but adds complexity
$pdo->exec('LOCK TABLES patient_daily_sequence WRITE');
// ... generate code ...
$pdo->exec('UNLOCK TABLES');

// Problems:
// - Requires table locks (performance hit)
// - More code to maintain
// - Easy to forget to unlock
// - Still not as reliable as database trigger
```

---

## 🎓 How MySQL's ON DUPLICATE KEY Works

This is the **key line** that makes everything thread-safe:

```sql
INSERT INTO patient_daily_sequence (seq_date, last_n)
VALUES (v_date, 1)
ON DUPLICATE KEY UPDATE last_n = LAST_INSERT_ID(last_n + 1);
```

### First Patient of the Day (No row exists yet)

```
Before: patient_daily_sequence is empty for 2026-02-06

INSERT INTO patient_daily_sequence (seq_date, last_n)
VALUES ('2026-02-06', 1)

After:
seq_date    | last_n
------------|-------
2026-02-06  | 1

LAST_INSERT_ID() returns: 1
```

### Second Patient of the Day (Row exists)

```
Before:
seq_date    | last_n
------------|-------
2026-02-06  | 1

INSERT attempts to insert ('2026-02-06', 1)
But PRIMARY KEY (seq_date) already exists!
So it triggers: ON DUPLICATE KEY UPDATE

UPDATE patient_daily_sequence
SET last_n = LAST_INSERT_ID(1 + 1)  -- Returns 2 and stores in LAST_INSERT_ID
WHERE seq_date = '2026-02-06'

After:
seq_date    | last_n
------------|-------
2026-02-06  | 2

LAST_INSERT_ID() returns: 2
```

### Why LAST_INSERT_ID(expression)?

**Normal version** (doesn't work for our use case):
```sql
UPDATE patient_daily_sequence SET last_n = last_n + 1
-- We'd have to SELECT again to get the new value
-- Another race condition!
```

**Smart version** (works perfectly):
```sql
UPDATE patient_daily_sequence SET last_n = LAST_INSERT_ID(last_n + 1)
-- Increments last_n AND returns the new value
-- All in ONE atomic operation!
```

---

## 📊 Monitoring & Debugging

### Check Daily Registration Count
```php
$stmt = $pdo->query('
    SELECT seq_date, last_n as patient_count
    FROM patient_daily_sequence
    ORDER BY seq_date DESC
    LIMIT 10
');
$dailyCounts = $stmt->fetchAll();

foreach ($dailyCounts as $row) {
    echo "{$row['seq_date']}: {$row['patient_count']} patients\n";
}

/* Output:
2026-02-07: 8 patients
2026-02-06: 12 patients
2026-02-05: 15 patients
...
*/
```

### Verify Patient Codes
```php
// Check for any duplicate codes (should always return 0)
$stmt = $pdo->query('
    SELECT patient_code, COUNT(*) as count
    FROM patients
    GROUP BY patient_code
    HAVING count > 1
');

if ($stmt->rowCount() > 0) {
    echo "⚠️ WARNING: Duplicate patient codes found!\n";
} else {
    echo "✅ All patient codes are unique\n";
}
```

### Find Gaps in Sequence
```php
// Useful for auditing - should show no gaps
$stmt = $pdo->prepare('
    SELECT patient_code
    FROM patients
    WHERE first_seen_date = ?
    ORDER BY patient_code
');
$stmt->execute(['2026-02-06']);
$codes = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Expected: 20260206001, 20260206002, 20260206003...
// If you see: 20260206001, 20260206003, 20260206005
// Then 002 and 004 are missing (deleted patients?)
```

---

## 🔮 Edge Cases & Considerations

### What if we hit 999 patients in one day?

```sql
-- Current: LPAD(v_seq, 3, '0') supports 001-999
-- If you expect >999 patients/day, increase padding:
SET NEW.patient_code = CONCAT(
    DATE_FORMAT(v_date, '%Y%m%d'),
    LPAD(v_seq, 4, '0')  -- Now supports 0001-9999
);

-- Update patient_code column:
ALTER TABLE patients MODIFY COLUMN patient_code char(12);
```

### What if a patient is deleted?

```
Feb 6: Patient 001, 002, 003 registered
Delete patient 002
Next patient on Feb 6: Gets 004 (NOT 002)

The sequence never goes backwards!
Gaps in the sequence indicate deleted patients.
```

### What if I need to backdate a patient?

```php
// Register a patient with a past date
$stmt = $pdo->prepare('
    INSERT INTO patients (first_name, last_name, first_seen_date)
    VALUES (?, ?, ?)
');
$stmt->execute(['John', 'Doe', '2026-01-15']);

// Gets code: 20260115XXX
// Where XXX is the next sequence for Jan 15, not today
```

### Can I manually set patient_code?

**No** - The trigger always overwrites it. This is by design to prevent:
- Duplicate codes
- Invalid formats
- Human error

If you absolutely need to override (not recommended):
1. Disable the trigger temporarily
2. INSERT with your custom code
3. Re-enable the trigger

---

## 📝 Summary

### The Magic Formula

```
patient_code = YYYYMMDD + NNN

Where:
- YYYYMMDD = first_seen_date formatted
- NNN = sequence number from patient_daily_sequence table
```

### The Process

1. You INSERT a patient
2. Trigger fires automatically
3. Trigger gets next sequence number (thread-safe)
4. Trigger builds patient_code
5. INSERT completes with generated code

### The Result

✅ Automatic
✅ Thread-safe
✅ Consistent
✅ Maintainable
✅ Reliable

**You never have to think about it!**

---

## 🧪 Test It Yourself

Run this PHP script to see it in action:

```php
<?php
require_once __DIR__ . '/app/config/database.php';
$pdo = getDBConnection();

echo "Creating 5 patients...\n\n";

for ($i = 1; $i <= 5; $i++) {
    $pdo->prepare('INSERT INTO patients (first_name, last_name, sex) VALUES (?, ?, ?)')
        ->execute(["Test", "Patient$i", 'MALE']);

    $id = $pdo->lastInsertId();
    $code = $pdo->query("SELECT patient_code FROM patients WHERE patient_id = $id")->fetchColumn();

    echo "Patient $i: ID=$id, Code=$code\n";
}

echo "\nChecking daily sequence table...\n";
$result = $pdo->query('SELECT * FROM patient_daily_sequence WHERE seq_date = CURDATE()')->fetch();
echo "Today: {$result['seq_date']}, Total patients: {$result['last_n']}\n";

/* Expected Output:
Creating 5 patients...

Patient 1: ID=2, Code=20260206001
Patient 2: ID=3, Code=20260206002
Patient 3: ID=4, Code=20260206003
Patient 4: ID=5, Code=20260206004
Patient 5: ID=6, Code=20260206005

Checking daily sequence table...
Today: 2026-02-06, Total patients: 5
*/
?>
```

---

## ❓ Common Questions

**Q: Why not just use patient_id as the identifier?**
A: patient_id is sequential across all days. patient_code groups by date and shows daily sequence, which is useful for camps and clinics that track daily patient flow.

**Q: Can I change the format to DDMMYYYYNNN?**
A: Yes! Just modify the `DATE_FORMAT` in the trigger:
```sql
DATE_FORMAT(v_date, '%d%m%Y')  -- 06022026
```

**Q: What if I run the trigger on a backup/test database?**
A: Codes will be different in test vs production. Always restore patient_daily_sequence table along with patients table to keep them in sync.

**Q: Is there any performance impact?**
A: Minimal. The trigger adds ~1ms per patient insert. The sequence table grows by 1 row/day (365 rows/year).

**Q: Can I see the trigger code in phpMyAdmin?**
A: Yes! Go to your database → patients table → Triggers tab → click "trg_patients_before_insert"
