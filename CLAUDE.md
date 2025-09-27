# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Real Estate Management System (Sistema de Gestión Inmobiliaria) built as a single-page application using vanilla HTML, CSS, and JavaScript. The application manages properties, clients, agents, sales, contracts, rentals, and property visits for a real estate business.

## Architecture

- **Single HTML File**: The entire application is contained in `index.html`
- **Vanilla JavaScript**: No frameworks or build tools - pure JavaScript for all functionality
- **Client-Side Only**: All data is simulated in memory, no backend or database integration
- **Modular Sections**: Seven main modules accessible via navigation tabs:
  - Inmuebles (Properties)
  - Clientes (Clients)
  - Agentes (Agents)
  - Ventas (Sales)
  - Contratos (Contracts)
  - Arriendos (Rentals)
  - Visitas (Visits)

## Development Commands

Since this is a static HTML application, development is straightforward:

- **View the application**: Open `index.html` in a web browser
- **Local development server**: Use any local server like `python -m http.server` or `npx serve`
- **No build process**: Direct file editing and browser refresh

## Key Features

- Form-based data entry for all entities
- Auto-generated IDs for each record type (INM, CLI, AGE, VEN, CON, ARR, VIS)
- Table-based data display with action buttons
- Section-based navigation with active state management
- Responsive grid layouts and form styling
- File upload inputs for property photos and contract documents
- Status indicators with color-coded badges

## Code Structure

- **CSS**: Embedded styles with a clean, professional design using a blue/gray color scheme
- **JavaScript Functions**:
  - `showSection()`: Tab navigation between modules
  - `handleFormSubmit()`: Form submission handling (currently simulated)
  - `generateId()`: Auto-ID generation for different entity types
  - Utility functions for validation, formatting, filtering, and data export
- **Form Patterns**: Consistent form layouts using `.form-row` and `.form-group` classes
- **Table Patterns**: Standardized table structure with action columns

## Important Notes

- All functionality is currently simulated - forms show alerts instead of persisting data
- No actual backend integration exists
- Sample data is hardcoded in HTML tables
- File uploads and exports are placeholder functionality
- This appears to be a prototype or demo version of a real estate management system