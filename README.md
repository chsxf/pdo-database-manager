# pdo-database-manager

## Error table structure

Providing you use the default name for the error table, the structure should be:

```sql
CREATE TABLE `mfx_database_errors` (
  `query` text COLLATE utf8_bin NOT NULL,
  `error_code` int(11) NOT NULL,
  `error_message` text COLLATE utf8_bin NOT NULL,
  `file` text COLLATE utf8_bin NOT NULL,
  `line` int(11) NOT NULL,
  `function` text COLLATE utf8_bin NOT NULL,
  `class` text COLLATE utf8_bin NOT NULL
);
```
