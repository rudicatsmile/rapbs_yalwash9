# Plan: Create Book CRUD Feature Following User Resource Pattern

## Overview
Create a complete CRUD interface for the Book model in Filament admin panel, following the organizational pattern used in the User resource.

## Book Model
- **Model**: `App\Models\Book`
- **Fields**:
  - `title` (string, required)
  - `year` (integer, required)
  - `summary` (text, optional)

## Step 1: Create Feature Branch
- Branch name: `feat/09-book-resource`
- Checkout from: `main`

## Step 2: Generate Base Resource
```bash
php artisan make:filament-resource Book --generate --no-interaction
```
This command will:
- Auto-generate BookResource based on Book model schema
- Create basic CRUD pages (List, Create, Edit)
- Generate form fields and table columns from database

## Step 3: Restructure to Match User Pattern

### Directory Structure
```
app/Filament/Resources/Books/
├── BookResource.php                    (Main resource definition)
├── Schemas/
│   └── BookForm.php                    (Form field configuration)
├── Tables/
│   └── BooksTable.php                  (Table column & filter configuration)
└── Pages/
    ├── ListBooks.php                   (List/index page with header actions)
    ├── CreateBook.php                  (Create page)
    └── EditBook.php                    (Edit page)
```

### File Components

#### 1. BookForm.php (`Books/Schemas/BookForm.php`)
- Static `configure(Schema $schema): Schema` method
- Organize fields in Section component
- **Fields**:
  - `TextInput::make('title')` - Required, max length validation
  - `TextInput::make('year')` - Required, numeric, 4-digit validation
  - `Textarea::make('summary')` - Optional, multi-line

#### 2. BooksTable.php (`Books/Tables/BooksTable.php`)
- Static `configure(Table $table): Table` method
- **Columns**:
  - `TextColumn::make('title')` - Sortable, searchable
  - `TextColumn::make('year')` - Sortable
  - `TextColumn::make('summary')` - Limit display length
- **Filters**:
  - Year range filter
  - Layout: `FiltersLayout::AboveContentCollapsible` (matches User resource)
- **Actions**: EditAction, DeleteAction
- **Bulk Actions**: DeleteBulkAction

#### 3. BookResource.php
- Delegate form to `BookForm::configure()`
- Delegate table to `BooksTable::configure()`
- Navigation group: "Content Management"
- Icon: Book-related Heroicon (e.g., `Heroicon::OutlineBookOpen`)
- Update page namespace references

#### 4. Page Classes (`Books/Pages/`)

**ListBooks.php**:
- Extend `ListRecords`
- Header actions: CreateAction, ImportAction, ExportAction

**CreateBook.php**:
- Extend `CreateRecord`
- Redirect to index after creation

**EditBook.php**:
- Extend `EditRecord`
- Header actions: DeleteAction
- Redirect to index after save

#### 5. Import/Export Classes (Optional)

**BookExporter.php** (`app/Filament/Exports/`):
- Export title, year, summary fields
- Custom notification messages

**BookImporter.php** (`app/Filament/Imports/`):
- Import title, year, summary fields
- Validation rules

## Step 4: Code Formatting
```bash
vendor/bin/pint --dirty
```

## Step 5: Verification
- Access admin panel at `/admin/books`
- Test CRUD operations:
  - Create new book
  - Edit existing book
  - Delete book
  - Bulk delete
  - Import/Export functionality
  - Filter by year

## Expected Result
A fully functional Book CRUD interface at `/admin/books` that:
- Follows User resource organizational pattern
- Maintains separation of concerns (Schema/Table/Pages)
- Includes import/export capabilities
- Provides intuitive filtering and sorting
- Matches the code style and conventions of existing resources

## Key Patterns Followed
1. **Delegation Pattern**: Form and table configuration delegated to separate classes
2. **Static Configuration**: Schema/Table classes use static `configure()` method
3. **Separation of Concerns**: Resource class only handles model & page registration
4. **Consistent Navigation**: Grouped under "Content Management"
5. **Responsive Layout**: Fields organized in sections with proper column spans
