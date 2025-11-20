# Copilot Instructions

## Schema Validation
- All files in the `multiflexi/*.app.json` directory **must** conform to the schema:
  - Schema URL: [multiflexi.app.schema.json](https://raw.githubusercontent.com/VitexSoftware/php-vitexsoftware-multiflexi-core/refs/heads/main/multiflexi.app.schema.json)
- **Why:** Ensures compatibility with MultiFlexi framework and prevents runtime errors.

## PHP Code Quality
- After **every** edit to a PHP file, **mandatory** run syntax check:
  ```bash
  php -l <filename>
  ```
- **Why:** Catches syntax errors early and maintains code quality.
- **Example:**
  ```bash
  php -l src/Kimai2AbraFlexiSync.php
  ```

## Project Context
- This is a **Kimai2 to AbraFlexi synchronization** tool
- Integrates time tracking data from Kimai2 with AbraFlexi accounting system
- Uses MultiFlexi framework for configuration management
