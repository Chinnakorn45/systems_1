# systems_1

## Configuration

Database credentials are now loaded from environment variables. Create a `.env` file
based on `.env.example` and set `DB_USERNAME` and `DB_PASSWORD` to a MySQL account
with limited privileges.

```
DB_USERNAME=appuser
DB_PASSWORD=secret_password
```

Grant minimal permissions to this account instead of using the MySQL `root` user:

```sql
CREATE USER 'appuser'@'localhost' IDENTIFIED BY 'secret_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON borrowing_db.* TO 'appuser'@'localhost';
FLUSH PRIVILEGES;
```
