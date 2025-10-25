---
marp: true
---

# SQL commands

```sql
mysql> USE project_db
mysql> ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user';
mysql> UPDATE users SET role = 'admin' WHERE username = 'admin';
mysql> INSERT INTO users (username, password, role, createdAt)
VALUES ('admin', '$2y$12$MI47hbRSggRO8nL0Ef3BPu2Znz3xFN6oj.mkt6xs2vZrpmthuOmOC', 'admin', NOW());
mysql> DELETE FROM users
WHERE id = 1;

```
