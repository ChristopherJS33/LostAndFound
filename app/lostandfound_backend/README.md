# Lost & Found Backend (LAMP + Java OOP Module)

This backend is designed for the provided frontend and uses:
- PHP 8+
- MySQL / MariaDB
- Apache (XAMPP/LAMP)
- Java 17+ for the OOP matching engine

## What this includes
- User registration and login
- Token-based auth
- Lost item and found item reporting
- Browse items with filters
- View single item
- View your own items
- Submit claims
- Admin view for pending claims
- Java matching engine using OOP, inheritance, encapsulation, and interfaces

## Frontend API base URL
Update `config.js` in your frontend to:

```js
export const CONFIG = {
  API_BASE_URL: "http://localhost/lostandfound_backend/api",
};
```

If you rename the Apache folder, adjust the URL.

## Folder placement
Place the `lostandfound_backend` folder inside your Apache web root.
Examples:
- XAMPP on Windows: `htdocs/lostandfound_backend`
- Linux Apache: `/var/www/html/lostandfound_backend`

## Database setup
1. Create a MySQL database named `lostandfound_db`
2. Import `schema.sql`
3. Update DB credentials in `api/config/config.php`

## Java setup
Compile the Java matcher from the `java-matcher` folder:

```bash
cd java-matcher
javac -d bin src/com/lostandfound/*.java
```

Update the Java paths in `api/config/config.php` if needed.

## Default admin
Create a normal account first, then in MySQL run:

```sql
UPDATE users SET role = 'admin' WHERE email = 'your-email@example.com';
```

## API routes expected by the frontend
- POST `/auth/register`
- POST `/auth/login`
- GET `/items`
- GET `/items/{id}`
- POST `/items/lost`
- POST `/items/found`
- GET `/items/my-items`
- POST `/claims/{itemId}`
- GET `/admin/claims`

## Notes on Java OOP requirements
The Java module demonstrates:
- **Encapsulation**: private fields in `Item`
- **Inheritance**: `LostItem` and `FoundItem` extend `Item`
- **Interface**: `Matcher`
- **Polymorphism / abstraction**: `SimpleMatcher` used through `Matcher`

PHP calls the Java matcher after a lost or found report is created and saves possible matches into the `item_matches` table.
