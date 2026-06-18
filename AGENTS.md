---
applyTo: '**'
---

# Global Development Rules

## 1. File and Output Rules
- Never create or output .md (Markdown) files under any circumstances unless explicitly requested.
- Never create README.md, CHANGELOG.md, or any documentation files unless explicitly requested.
- Only produce required code files (.php, .js, .html, .css, .sql, etc.).
- Keep each file within 400 lines maximum. Split only when logically necessary.
- Avoid unnecessary folder depth. Adapt to existing project structure.

## 2. Core Development Principles
- KISS: Write concise and clear code.
- DRY: Extract repeated logic into reusable functions or classes.
- SOLID: Follow object-oriented design principles when applicable.
- Flexibility: Adapt to evolving project structure and requirements.

## 3. PHP Security (Critical Priority)
- Always use prepared statements with parameterized queries (mysqli or PDO).
- Never use mysqli_real_escape_string alone for SQL injection prevention.
- Sanitize and validate all user inputs with filter_input() or filter_var().
- Use password_hash() and password_verify() for password operations.
- Implement CSRF tokens for all state-changing forms.
- Set secure session configuration (session.cookie_httponly, session.cookie_secure, session.cookie_samesite).
- Escape all output with htmlspecialchars() to prevent XSS attacks.
- Never expose sensitive information in error messages.
- Validate file uploads (type, size, extension) if implemented.
- Use .htaccess to restrict direct access to sensitive files and directories.

## 4. Database Best Practices
- Use prepared statements for all database queries without exception.
- Specify required columns instead of SELECT *.
- Use transactions for multi-step operations to ensure data integrity.
- Index frequently queried columns for performance.
- Close database connections explicitly when done.
- Avoid N+1 query problems. Use JOINs efficiently.
- Handle database errors gracefully with try-catch or error checking.

## 5. Session Management
- Regenerate session ID after authentication (session_regenerate_id(true)).
- Store minimal data in sessions (user ID, role, essential flags only).
- Implement session timeout for inactive users.
- Validate session data on every protected page request.
- Destroy sessions completely on logout (session_destroy(), unset cookies).
- Check user role/permissions before allowing access to protected resources.

## 6. Code Structure and Organization
- Separate concerns where logical (authentication, database logic, presentation).
- Keep functions focused on single responsibility and under 40 lines.
- Use type declarations when possible (declare(strict_types=1), parameter types, return types).
- Handle errors with try-catch blocks for exceptions.
- Use configuration files for constants and settings.
- Never hard-code sensitive values (credentials, API keys, secrets).
- Use environment variables or config files outside web root for sensitive data.

## 7. Performance Optimization
- Enable OPcache in production for PHP bytecode caching.
- Minimize database queries. Cache results when appropriate.
- Use output buffering strategically for complex pages.
- Lazy load resources. Only load what is needed.
- Optimize session usage. Avoid storing large objects.
- Use GZIP compression for responses.
- Optimize images and use appropriate formats (WebP, compressed JPEG/PNG).

## 8. PHP Naming Conventions
- Variables and functions: snake_case ($user_data, get_user_by_id()).
- Classes: PascalCase (UserController, DatabaseConnection, TestManager).
- Constants: UPPERCASE_SNAKE_CASE (DB_HOST, MAX_ATTEMPTS, SESSION_TIMEOUT).
- File names: snake_case or match class names (user_controller.php, TestManager.php).
- Database tables: snake_case (users, test_results, teacher_assignments).
- Avoid abbreviations unless universally understood (id, url, api, db).

## 9. Frontend Development
- Desktop-first design approach for web applications.
- Use rem, %, vh/vw for scalable units instead of fixed px.
- Optimize for common desktop resolutions (1366x768, 1920x1080, 2560x1440).
- Minimize inline styles. Prefer external CSS files.
- Defer non-critical JavaScript loading.
- Use semantic HTML5 elements for better accessibility.
- Ensure forms have proper labels and validation feedback.
- Test across major browsers (Chrome, Firefox, Edge, Safari).

## 10. Error Handling and Logging
- Use exceptions for exceptional conditions, not control flow.
- Log errors to files in production. Never display to end users.
- Return user-friendly error messages without exposing system internals.
- Validate inputs early and fail fast with clear error messages.
- Implement custom error handlers for graceful degradation.
- Log security-relevant events (failed logins, permission denials).

## 11. Code Style and Readability
- Use descriptive, consistent naming that explains purpose.
- Add comments only for non-obvious logic or business rules.
- Keep code DRY. Extract repeated patterns into functions.
- Write self-documenting code that minimizes documentation needs.
- Use meaningful variable names over single letters (except loop counters).
- Maintain consistent indentation (4 spaces or 1 tab, stay consistent).
- Prefer built-in PHP functions over reinventing functionality.

## 12. Testing and Validation
- Validate all inputs on both client-side and server-side.
- Test edge cases (null, empty strings, special characters, SQL injection attempts).
- Test authentication and authorization flows thoroughly.
- Verify role-based access control works as expected.
- Test session timeout and logout functionality.
- Check for XSS vulnerabilities in all user-generated content display.

## 13. Version Control
- AVOID committing code unless explicitly instructed to do so.
- AVOID running git commands (add, commit, push) on your own.
- Never commit sensitive data (.env files, credentials, API keys).
- Use .gitignore for environment-specific and generated files.
- When explicitly asked to commit, use clear, descriptive commit messages.

## 14. Project Flexibility Guidelines
- Adapt to existing file organization without forcing restructure.
- Be prepared to work with mixed naming conventions during transition.
- Suggest improvements but implement requested patterns.
- Focus on functionality and security over perfect architecture.
- Allow for iterative refinement as project requirements evolve.
- Respect existing code patterns unless refactoring is explicitly requested.

## 15. Dependencies and Libraries
- Minimize external dependencies. Use built-in PHP features first.
- Document any third-party libraries or CDN resources used.
- Keep Bootstrap and other frontend frameworks updated to latest stable versions.
- Verify CDN availability and consider local fallbacks for critical resources.

## 16. Development Workflow
- Test locally before committing (XAMPP or similar environment).
- Verify database connections and queries work as expected.
- Check all user roles and access levels function correctly.
- Ensure error handling provides useful feedback during development.
- Clear sessions and cache when testing authentication changes.

## 17. Documentation
- Document complex algorithms or business logic inline with comments.
- Explain security-critical sections clearly.
- Note any temporary workarounds or technical debt for future improvement.
- AVOID creating documentation files (.md, .txt) unless explicitly requested.