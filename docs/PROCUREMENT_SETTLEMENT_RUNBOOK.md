# Procurement Settlement Runbook

Operational runbook for the supplier settlement reconciliation of the Oceanovia
application: the scheduled detection, the manual inspection, the repair of a
lost settlement and the handling of a record that needs a manual decision.

This document describes what the repository does and what an operator still has
to do on the server. It does **not** report any repair that was performed on
production.

---

## 1. What the application does (verified in the repository)

| Behaviour | Where | Status |
|---|---|---|
| The reconciliation command exists and is discoverable as `money:reconcile-procurement-settlements` | `app/Console/Commands/ReconcileProcurementSettlements.php` | verified |
| A **dry run is the default**: the command only writes when `--write` is passed | same file, the `$write` option | verified |
| The command finds procurement orders that are `delivered` but have no `settled_at` | same file, the candidate query | verified |
| A record whose ledger already shows a supplier credit but whose settlement marker is missing is **never credited again**; it is reported as `ambiguous` | `app/Services/ProcurementService.php`, `evaluateSettlement()` | verified |
| The scheduled task runs the command **with `--dry-run`** and never with `--write` | `routes/console.php` | verified |
| The scheduled task runs daily at 03:15 **UTC** and is protected against overlapping runs | `routes/console.php` (`dailyAt`, `withoutOverlapping`, `onOneServer`); the timezone is the application timezone, which is UTC (`config/app.php`, and the deployment sets `APP_TIMEZONE=UTC`) | verified |
| A repair writes the balance credit, the ledger entry and the settlement marker in one transaction | `app/Services/ProcurementService.php`, `reconcileSettlement()` | verified |
| A repeated repair run is a no-op, and two concurrent repairs credit the supplier once | `app/Services/ProcurementService.php` (the procurement order is locked) | verified by test, see section 7 |

---

## 2. Deployment steps an operator still has to perform

These are **not** done by the repository. They are performed by the deployment.

1. **Install the scheduler cron entry.** The deployment runs
   `bash deploy/install-cron.sh` (step 9 of `deploy/deploy.sh`). The installer
   is idempotent, so it can be run on every deploy. It must be run as root,
   because it writes the crontab of the application user. It refuses to
   install anything when the application directory, the artisan file or the
   PHP binary is missing, and it exits with an error when the crontab cannot
   be written, so a failed installation stops the deployment.
2. **Verify the cron entry exists**:

   ```bash
   sudo -u www-data crontab -l | grep schedule:run
   ```

   Expected: exactly one line that runs `artisan schedule:run` every minute.
3. **Verify the scheduler is actually running.** The crontab entry is not proof
   that the scheduler executes; check the scheduler log (section 6) or run the
   task by hand:

   ```bash
   cd /var/www/oceanovia
   sudo -u www-data /usr/bin/php artisan schedule:run
   ```

   The output must show the scheduled task and no error.
4. **Confirm the cache store is shared.** `onOneServer()` needs a cache store
   that all application servers share. The deployment writes
   `CACHE_STORE=redis` (see `deploy/deploy.sh`). If the cache store is changed
   to a local one, remove `onOneServer()` from `routes/console.php`, because a
   local lock cannot coordinate the servers.
5. **Assign an owner** for unresolved records (section 5). No owner is
   configured in the repository, so this is a deployment responsibility.

### 2.1 Verification checklist

Every line has to be checked on the server; none of them can be proven from
the repository. The right-hand column is the **expected result**; the output of
each command is the **evidence to archive**.

| # | Check | Command | Expected result |
|---|---|---|---|
| 1 | The crontab of the application user holds exactly one managed entry | `sudo -u www-data crontab -l \| grep -c 'schedule:run'` | `1` |
| 2 | The managed block is not duplicated | `sudo -u www-data crontab -l \| grep -c 'BEGIN oceanovia managed entries'` | `1` |
| 3 | The application directory and the PHP binary are correct | `sudo -u www-data crontab -l \| grep schedule:run` | the entry contains `/var/www/oceanovia` and the PHP binary that exists on the server |
| 4 | The scheduler runs successfully | `cd /var/www/oceanovia && sudo -u www-data /usr/bin/php artisan schedule:run` | the scheduled task is listed, no error |
| 5 | The scheduled reconciliation appears in the scheduler log | `grep 'money:reconcile' /var/www/oceanovia/storage/logs/scheduler.log \| tail` | the reconciliation output of the last run (after 03:15 UTC) |
| 6 | The effective cache driver is shared between the application servers | `cd /var/www/oceanovia && sudo -u www-data /usr/bin/php artisan tinker --execute="echo config('cache.default');"` | a shared store (`redis`), not `file` or `array` |
| 7 | The overlap lock and `onOneServer()` work with that store | two application servers, one cron: only one run per day, and `storage/logs/scheduler.log` shows a single run | one run: the reconciliation output appears **once** in the window. A run that `withoutOverlapping()` skips is **silent** (Laravel prints nothing for it), so the evidence is the *absent* second output. A run that `onOneServer()` skips prints `Skipping […] because the command already ran on another server.` |
| 8 | The scheduler may write its log | `sudo -u www-data test -w /var/www/oceanovia/storage/logs && echo writable` | `writable` |
| 9 | Log rotation is configured | `ls /etc/logrotate.d/ \| grep -i oceanovia` and `logrotate -d /etc/logrotate.d/oceanovia` | a rule for `storage/logs/scheduler.log`, and the dry run shows the intended rotation |
| 10 | An owner and a review frequency exist | the deployment documentation | a named role and a review interval for the dry run output |
| 11 | Actionable findings reach a monitored destination | the monitoring configuration of the server | either a monitored sink, or the documented review process from section 5 with a named owner |

### 2.2 Schedule, clock and synchronisation

The daily task is registered as `dailyAt('03:15')` in `routes/console.php`.
The scheduler uses the timezone of the application, which is UTC
(`config/app.php`, and the deployment sets `APP_TIMEZONE=UTC`), so the task is
expected at **03:15 UTC**. The checks below prove that the deployed code
carries that task and that the host clock makes it run at that time. None of
them has been executed on the production server; the right-hand column is the
expected result, and the command output is the evidence to archive.

| # | Check | Command | Acceptance criterion | Evidence to archive |
|---|---|---|---|---|
| A | The deployed code carries the reconciliation task | `cd /var/www/oceanovia && sudo -u www-data /usr/bin/php artisan schedule:list` | one line for the task with the expression `15 3 * * *` and the command `money:reconcile-procurement-settlements --dry-run`; no second entry for the same task | the complete `schedule:list` output |
| B | The task is not able to write | the same `schedule:list` output | the scheduled command contains `--dry-run` and **no** `--write` | the same output |
| C | The host clock is on UTC | `date -u` and `date` | `date -u` and `date` show the same wall-clock time, i.e. the host is on UTC | the output of both commands |
| D | The clock is synchronised | `timedatectl status` (or `timedatectl show -p NTPSynchronized`), and the status of the time-synchronisation service the host actually uses, for example `systemctl is-active chronyd` or `systemctl is-active systemd-timesyncd` | the synchronisation service is **active** and the status shows the clock is synchronised (`System clock synchronized: yes`, or `NTPSynchronized=yes`) | the output of the status command and of the service check |
| E | The task ran at the intended time | `grep 'money:reconcile' /var/www/oceanovia/storage/logs/scheduler.log \| tail` together with `date -u` | a reconciliation block from a run after 03:15 UTC of the current day, and no block outside the expected window | the log block and the UTC timestamp it was found at |

**Note.** The expression `15 3 * * *` is evaluated in the timezone of the
scheduler, which is the timezone of the application. The application timezone
is UTC, so the task runs at 03:15 UTC regardless of the local time of the
host. The host clock still has to be accurate (check D), because the cron
entry fires on the host clock; when the host is not on UTC, the deployment has
to keep `APP_TIMEZONE=UTC` so that the task keeps its documented time.

---

## 3. Running the reconciliation by hand

```bash
cd /var/www/oceanovia

# Inspect only. Changes nothing.
sudo -u www-data /usr/bin/php artisan money:reconcile-procurement-settlements --dry-run

# Repair the records that pass the safety checks.
sudo -u www-data /usr/bin/php artisan money:reconcile-procurement-settlements --write

# Repair one record only.
sudo -u www-data /usr/bin/php artisan money:reconcile-procurement-settlements --write --po=PO-6AD898F8F1A66
sudo -u www-data /usr/bin/php artisan money:reconcile-procurement-settlements --write --po=42
```

`--po` accepts the procurement order id or its `po_number`. `--limit` caps the
number of records per run (default 500).

### Exit codes

| Code | Meaning | Operator action |
|---|---|---|
| `0` | The run finished. Either nothing was outstanding, or the outstanding records were repairable and were repaired in a `--write` run. | None. In a dry run, a `Repairable: n` count above zero means a repair is still pending. |
| `1` | The run finished but some records are `ambiguous` or `failed`. | Investigate the listed records (section 5). An ambiguous record is **not** repaired by the command. |
| `2` | The command was called with an invalid combination of options (`--dry-run` together with `--write`). | Fix the command line. |

---

## 4. Reading the output

The command prints one row per candidate:

```
+----+------------------+----------+------------+--------------------------------------------------------------+
| PO | Number           | Supplier | State      | Detail                                                       |
+----+------------------+----------+------------+--------------------------------------------------------------+
| 42 | PO-6AD898F8F1A66 | 7        | repairable | Delivered, not settled and no supplier credit in the ledger. |
+----+------------------+----------+------------+--------------------------------------------------------------+
Discovered: 1
Reconciled: 0
Repairable: 1
Skipped: 0
Ambiguous: 0
Failed: 0
Mode: dry run
```

| State | Meaning | What to do |
|---|---|---|
| `repairable` | The procurement order is delivered, its settlement marker is missing and the ledger shows no supplier credit. This is the state a crash between the two settlement transactions leaves behind. | Repair it with `--write` (section 4, second command). |
| `reconciled` | The supplier was credited by this run. | None. The run is complete for that record. |
| `ambiguous` | The ledger already shows a supplier credit for this procurement order, but the settlement marker is missing. The correct financial state cannot be derived from the data. | **Do not force a repair.** Investigate manually (section 5). |
| `skipped` | The record is already settled or is not eligible for a settlement. | None. |
| `failed` | The settlement raised an exception for that record. The transaction was rolled back, so the balance, the ledger and the marker are unchanged. | Read the error in the `Detail` column and in the application log, fix the cause, then re-run. |

Only the procurement order id, its `po_number`, the supplier id, the state and a
short reason are printed. No customer data, account details or credentials are
part of the output.

---

## 5. Ambiguous records: manual investigation, no forced repair

An `ambiguous` record means the money side and the marker side of the ledger
disagree:

* the procurement order is `delivered` and its `settled_at` is `NULL`, **and**
* the ledger contains a supplier credit for that procurement order that is not
  matched by a reversal.

The command deliberately does not credit such a record again, because it cannot
tell from the data whether the supplier was already paid once or twice.

**Investigation steps**

1. Look up the procurement order in the admin panel and note its `po_number`,
   the supplier id and the order it belongs to.
2. Read the ledger entries for that procurement order:

   ```sql
   SELECT id, type, amount, status, description, created_at
   FROM transactions
   WHERE reference_type = 'App\Models\ProcurementOrder'
     AND reference_id = <po id>
   ORDER BY id;
   ```

   * A `credit` row without a matching `refund` row means the supplier was
     credited once and the settlement marker was lost. The correct end state is
     a settled procurement order, not a second credit. Record the decision and
     set the marker in the database **only** through a reviewed change, never
     through `--write`.
   * A `credit` row and a `refund` row that net to zero mean the settlement was
     made and reversed. The procurement order was then cancelled, so it is not
     eligible for a settlement and the command correctly skips it.
3. Document the decision and who made it.

**Ownership**: the repository defines no owner for unresolved records. The
deployment has to name one (a finance or operations role) and define how often
the dry run is reviewed. Until that is done, an ambiguous record can stay
unresolved without anybody noticing.

---

## 6. Logs and retention

| Log | Path | Content |
|---|---|---|
| Application log | `storage/logs/laravel.log` (channel `stack` → `single`) | The reconciliation summary is written with `Log::info`. A run with actionable findings additionally writes a `Log::warning` with the same counters **and with the procurement order ids, po_numbers and supplier ids of the records that need attention** (up to 25; the rest is counted in `records_omitted`), so the warning alone is enough to act on. Ambiguous and failed records are named in the warning as well. |
| Scheduler output | `storage/logs/scheduler.log` | The standard output of `artisan schedule:run`, which includes the whole table and the counters of every run, regardless of the application log level. |

**Retention**: the repository ships no log rotation for either file. The
scheduler output grows by one block per minute (the task output is empty on
the runs where nothing is due, but the file still receives the output). Add a
`logrotate` rule for `storage/logs/scheduler.log` (and confirm the policy for
`storage/logs/laravel.log`) as a deployment step, for example:

```
# save as /etc/logrotate.d/oceanovia
/var/www/oceanovia/storage/logs/scheduler.log {
    daily
    rotate 14
    compress
    missingok
    notifempty
    copytruncate
}
```

`copytruncate` is used because the scheduler writes to the file with an
appending redirect.

---

## 7. After a repair

1. Re-run the dry run:

   ```bash
   sudo -u www-data /usr/bin/php artisan money:reconcile-procurement-settlements --dry-run
   ```

2. The repaired record must no longer appear, and `Discovered` must be `0` (or
   the number of records that are still outstanding). `Repairable` must be `0`
   for the record that was repaired.
3. The supplier balance must show exactly one credit for the settlement and
   the ledger must contain exactly one `credit` entry for that procurement
   order:

   ```sql
   SELECT COUNT(*) FROM transactions
   WHERE reference_type = 'App\Models\ProcurementOrder'
     AND reference_id = <po id> AND type = 'credit';
   ```

---

## 8. Verifying the behaviour on MySQL 8.0.46 (prerequisite, not yet done)

The concurrency behaviour of the settlement was verified on MySQL 9.4.0
(the only server that was available in the development environment). The
production server runs MySQL 8.0, so the following verification is still
outstanding. It must be run against an **isolated** test instance, never
against production.

**Prerequisites**

* An isolated MySQL **8.0.46** instance that is not production and not a copy
  of production data.
* The environment variables that the gated test class reads:

  | Variable | Meaning |
  |---|---|
  | `MYSQL_TEST_DB_HOST` | host of the test instance (default `127.0.0.1`) |
  | `MYSQL_TEST_DB_PORT` | port of the test instance (default `3306`) |
  | `MYSQL_TEST_DB_DATABASE` | the test database; the name **must contain `test`**, the test refuses to run otherwise |
  | `MYSQL_TEST_DB_USERNAME` | user of the test instance |
  | `MYSQL_TEST_DB_PASSWORD` | password of the test instance |

  The test class rebuilds the schema of that database for every test, so it
  must be a disposable database.
* The same environment variables have to be exported for the worker processes,
  they inherit them.

**Engine evidence to collect before the run**

Run the following on the test instance and archive the output. The engine
facts are part of the result: a run on a different engine is a different
result.

```sql
SELECT VERSION();
SELECT @@default_storage_engine, @@transaction_isolation, @@innodb_lock_wait_timeout;
```

| Property | Required value | Why |
|---|---|---|
| Server version | `8.0.46` | the version the production server runs |
| Storage engine | `InnoDB` | the engine that provides the row locks the settlement relies on; a non-InnoDB table is not locked at all |
| Transaction isolation | `REPEATABLE-READ` (the MySQL 8.0 default) | the isolation level the production server uses |
| `innodb_lock_wait_timeout` | the value of the instance, recorded with the result | the time a lock wait is allowed to take before it is reported as a concurrency error that the retry budget handles |

**Commands**

```bash
export MYSQL_TEST_DB_HOST=127.0.0.1
export MYSQL_TEST_DB_PORT=3306
export MYSQL_TEST_DB_DATABASE=oceanovia_mysql_test
export MYSQL_TEST_DB_USERNAME=root
export MYSQL_TEST_DB_PASSWORD='<password>'

cd /var/www/oceanovia

# 1. The 8 gated concurrency tests.
php artisan test --filter='MySqlTest'

# 2. The 83 money-path regression tests.
php artisan test --filter='MoneyPathRetryTest|ProcurementTest|OrderCancellationTest|OrderCancellationRetryTest|PayoutServiceTest|RefundServiceTest|CodCollectionTest|CommissionIdempotencyTest|CommissionServiceTest'

# 3. The 13 settlement reconciliation tests.
php artisan test --filter='ProcurementSettlementReconciliationTest'
```

**Acceptance criteria**

| Suite | Expected | What it proves |
|---|---|---|
| `MySqlTest` (gated) | 8 tests, 62 assertions, no failures and no skips | **This is the suite that proves the MySQL row-locking behaviour**: it runs two independent worker processes with separate connections against a real InnoDB server, so it shows that concurrent settlements and concurrent reconciliations credit the supplier once |
| Money-path regressions | 83 tests, 404 assertions, no failures | The retry, rollback and ledger behaviour of the money paths on the target engine. The suite is engine-independent (it also passes on SQLite), so it has to be **rerun here** to show it holds on 8.0.46 |
| Settlement reconciliation | 13 tests, 69 assertions, no failures | The settlement recovery, the dry run, the write mode and the ambiguity handling on the target engine. This suite is engine-independent as well and has to be rerun here for the same reason |

All three suites have to be run against the 8.0.46 instance. The gated suite
is the only one that demonstrates MySQL locking; the other two demonstrate
that the money-path logic is unchanged on that engine.

**What must not be reported as 8.0.46 verification**

* A run on **MySQL 9.4.0** — that was the development engine, not the
  production engine.
* A run on **MariaDB 10.4.32** — a different engine with different locking and
  isolation semantics.
* A run on **SQLite** — SQLite has no row-level locking (`lockForUpdate()` is
  compiled to an empty string there), so it can show the logic and the
  rollback, but never the locking, the deadlock handling or the concurrent
  behaviour.

The counts above are the historical expectations from the MySQL 9.4.0 run. The
result of the 8.0.46 run has to be reported separately, including the exact
server version, the storage engine, the isolation level and the
`innodb_lock_wait_timeout` of the instance that was used.

---

## 9. Cleaning up the disposable test database

The database that the gated tests use is **disposable and test-only**. The
gated test class rebuilds its schema for every test
(`Artisan::call('migrate:fresh', …)`), and the database name has to contain
`test` or the class refuses to run. Nothing in it has to survive the run.

**Never clean up against production.** A production database and production
data must never be used for this verification, and no cleanup command may ever
be pointed at a production server or a production database. The cleanup below
is destructive and is only allowed for the disposable test instance.

**1. Confirm the target before anything destructive**

Print the values that are going to be used, and check them against the
exported variables:

```bash
echo "host:     $MYSQL_TEST_DB_HOST"
echo "port:     $MYSQL_TEST_DB_PORT"
echo "database: $MYSQL_TEST_DB_DATABASE"
```

All three have to point at the **test instance** and at a database whose name
contains `test`. If the host is a production host, or the database name does
not contain `test`, **stop** and do not run any command of this section.

**2. Decide about the data**

The database holds only test fixtures. Either

* take a fresh backup of the disposable instance before the cleanup, or
* state explicitly in the report that no data needed preservation, because the
  database was rebuilt by the test class.

A backup of production data is not a substitute for this step: production data
must not be copied into the test instance in the first place.

**3. Stop the test workers and release the connections**

The gated tests spawn worker processes that hold their own connections. Let
them finish, and make sure no worker is left behind before the database is
dropped:

```bash
pgrep -af 'CartConcurrencyWorker|MoneyPathConcurrencyWorker'   # expect no output
```

If the workers were started by hand, stop them (Ctrl-C in their shell). The
connections of the test run itself are closed when the `php artisan test`
process exits.

**4. Remove the disposable database**

```bash
mysql --protocol=TCP -h "$MYSQL_TEST_DB_HOST" -P "$MYSQL_TEST_DB_PORT" \
      -u "$MYSQL_TEST_DB_USERNAME" -p \
      -e "DROP DATABASE \`$MYSQL_TEST_DB_DATABASE\`;"
```

The password is entered at the prompt; it is never written into a command, a
script or a report. If the test instance itself was a throwaway container or
virtual machine, remove that environment instead of keeping a server that
only exists for the tests.

**5. Verify the cleanup**

```bash
mysql --protocol=TCP -h "$MYSQL_TEST_DB_HOST" -P "$MYSQL_TEST_DB_PORT" \
      -u "$MYSQL_TEST_DB_USERNAME" -p \
      -e "SHOW DATABASES LIKE '$MYSQL_TEST_DB_DATABASE';"
```

Expected: **no rows**. The database is gone. Archive this output together
with the output of step 1, so that the report shows which instance was
touched and that the cleanup happened on the test instance and not anywhere
else.

