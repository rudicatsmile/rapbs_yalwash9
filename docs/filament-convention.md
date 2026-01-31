# Filament Conventions - DETAILED GUIDE

> **⚠️ IMPORTANT**: This file contains detailed Filament v4 best practices for this Laravel 12 + Filament 4 project. The main `Claude.md` has the critical summary. Read the relevant section here before implementing any Filament feature.

**🔥 FILAMENT V4 KEY CHANGES**:
- **Schemas namespace**: Layout components (`Section`, `Grid`, `Tabs`, etc.) moved to `Filament\Schemas\Components`
- **Unified Actions**: All actions now use `Filament\Actions` namespace (no more `Filament\Tables\Actions`)
- **Form fields**: Still in `Filament\Forms\Components`

## Creating resource with Filament
- Always use the Artisan command to create resources:
  ```bash
  php artisan make:filament-resource ModelName --generate --no-interaction
  ```
- This generates the filament resource, file in this file tree :

├── BookResource.php
├── Pages
│   ├── CreateBook.php
│   ├── EditBook.php
│   └── ListBooks.php
├── Schemas
│   └── BookForm.php
└── Tables
    └── BooksTable.php


