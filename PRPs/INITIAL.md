## FEATURE

We want to create a Real Estate Management System for educational purposes

The goal is to build a minimalist full-stack property management application using basic HTML, CSS, JavaScript, PHP, and MySQL that demonstrates professional web development skills while providing students with a portfolio-ready project that includes both frontend and backend development.

Core System Modules:

- Properties management with photos and details
- Client database with contact information
- Real estate agent profiles and assignments
- Sales transaction tracking and reporting
- Contract management with document handling
- Rental property management and payments
- Visit scheduling and coordination

We need:

- To be able to manage properties (add, edit, view, filter by type/price/location) with MySQL database storage
- To be able to handle client information with document types and preferences using database persistence
- To be able to track sales, contracts, rentals, and property visits with real database relationships
- To be able to generate automatic IDs (CLI001, INM001, AGE001, etc.) using database auto-increment
- To be able to perform real CRUD operations for all entities using PHP and MySQL
- To be able to validate data on both client-side (JavaScript) and server-side (PHP)
- To be able to manage user sessions and provide feedback messages
- To be able to handle basic file uploads for property photos and contract documents

## EXAMPLES & DOCUMENTATION

All specifications and requirements are detailed in:
- Database schema: `db-schema.jpeg` (7 entities with relationships for MySQL implementation)
- Educational approach: `propuesta-mejoramiento.md` (interface design and user guidance)
- Current implementation: `index.html` (existing real estate management structure - to be converted to PHP)
- Complete specification: `PRPs/real-state-landing-page.md` (comprehensive technical details with PHP/MySQL)

Additional research may be needed for:
- Basic PHP syntax and best practices for beginners
- MySQL database design and normalization principles
- Simple CRUD operations using PHP and PDO
- Basic security practices (prepared statements, input sanitization)
- Local development environment setup (XAMPP/WAMP)
- Professional interface patterns for full-stack business applications
- Form validation techniques using both JavaScript and PHP
- Session management and user feedback systems
- File upload handling for property photos and documents

## OTHER CONSIDERATIONS

- Educational Focus: Code must be clear, well-documented, and easy for students to understand full-stack development
- Modular File Structure: Organize PHP files logically with separate modules for each entity (Properties, Clients, etc.)
- No External Frameworks: Use only vanilla HTML, CSS, JavaScript, PHP, and MySQL - no frameworks or libraries
- Professional Appearance: Design should look like a real business application suitable for portfolios
- Real Database Integration: Use MySQL database with proper table structure and relationships
- Entity Relationships: Implement and enforce foreign key relationships between Cliente, Inmueble, Agente, Venta, Contrato, Arriendo, and Visita
- Environment Setup: Must work with standard XAMPP/WAMP local development environments
- Basic Security: Implement fundamental security practices (prepared statements, input validation, session management)
- Error Handling: Provide clear error messages and graceful failure handling for database operations
- Data Persistence: All data must persist in MySQL database between sessions
- Educational SQL: Students should learn basic SQL through the implementation
- Responsive Design: Must work properly on both mobile and desktop devices
- It's very important that the application demonstrates real-world full-stack business patterns while remaining educationally accessible for beginner PHP/MySQL students
