# Plan 06: Media Library with Spatie Media Library

## Overview
Implement comprehensive file and image management using `spatie/laravel-medialibrary` package, allowing users to upload, organize, and manipulate media files with support for conversions, collections, and responsive images.

## Current State
- Filament 4 admin panel operational
- No centralized media management system
- File uploads handled on per-model basis
- No image optimization or conversion capabilities

## Requirements
- Centralized media library for managing files and images
- Support for multiple file types (images, documents, videos)
- Image conversions and thumbnails
- Responsive images
- Media collections (organize by category)
- File optimization and compression
- Integration with Filament resources
- Storage on local disk or cloud (S3, etc.)
- File metadata and custom properties
- Media browsing and searching interface

## Implementation Steps

### Step 1: Install Spatie Media Library Package
**Command**:
```bash
composer require "spatie/laravel-medialibrary:^11.0"
```

**Actions**:
1. Install the package
2. Verify compatibility with Laravel 12

### Step 2: Publish Configuration and Migrations
**Commands**:
```bash
# Publish config file
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-config"

# Publish migrations
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
```

**Files Created**:
- `config/media-library.php` - Configuration file
- `database/migrations/*_create_media_table.php` - Media table migration

### Step 3: Run Migrations
**Command**:
```bash
php artisan migrate
```

**Expected Tables**:
- `media` - Stores all media files metadata

**Table Structure**:
- `id`, `model_type`, `model_id` (polymorphic relation)
- `uuid`, `collection_name`
- `name`, `file_name`, `mime_type`, `size`
- `disk`, `conversions_disk`
- `manipulations`, `custom_properties`, `generated_conversions`
- `responsive_images`
- `order_column`
- Timestamps

### Step 4: Configure Media Library Settings
**File**: `config/media-library.php`

**Key Configuration Options**:
```php
return [
    /*
     * The disk on which to store added files
     */
    'disk_name' => env('MEDIA_DISK', 'public'),

    /*
     * Maximum file size in KB
     */
    'max_file_size' => 1024 * 10, // 10MB

    /*
     * The class that contains the strategy for generating URLs
     */
    'url_generator' => Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator::class,

    /*
     * The class that handles conversions
     */
    'image_driver' => env('IMAGE_DRIVER', 'gd'),

    /*
     * Path generators
     */
    'path_generator' => Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator::class,

    /*
     * Queue settings for media conversions
     */
    'queue_connection_name' => env('QUEUE_CONNECTION', 'sync'),
    'queue_name' => '',

    /*
     * Custom media models
     */
    'media_model' => Spatie\MediaLibrary\MediaCollections\Models\Media::class,

    /*
     * Responsive images
     */
    'responsive_images' => [
        'width_calculator' => Spatie\MediaLibrary\ResponsiveImages\WidthCalculator\FileSizeOptimizedWidthCalculator::class,
        'use_tiny_placeholders' => true,
        'tiny_placeholder_generator' => Spatie\MediaLibrary\ResponsiveImages\TinyPlaceholderGenerator\Blurred::class,
    ],

    /*
     * Remote media settings
     */
    'remote' => [
        'extra_headers' => [
            'CacheControl' => 'max-age=604800',
        ],
    ],

    /*
     * Image optimization
     */
    'image_optimizers' => [
        Spatie\ImageOptimizer\Optimizers\Jpegoptim::class => [
            '-m85',
            '--strip-all',
            '--all-progressive',
        ],
        Spatie\ImageOptimizer\Optimizers\Pngquant::class => [
            '--force',
        ],
        // ... other optimizers
    ],
];
```

### Step 5: Update Environment Variables
**File**: `.env`

**Add Configuration**:
```env
# Media Library Configuration
MEDIA_DISK=public
IMAGE_DRIVER=gd
QUEUE_CONNECTION=database
```

### Step 6: Install Image Manipulation Package
**Command**:
```bash
composer require "spatie/image:^3.0"
```

**Actions**:
1. Install image manipulation library
2. Required for conversions and thumbnails

### Step 7: Install Image Optimizers (Optional but Recommended)
**Commands**:
```bash
# Install image optimizer package
composer require spatie/image-optimizer

# Install system-level optimizers (Ubuntu/Debian)
sudo apt-get install jpegoptim optipng pngquant gifsicle

# Or for macOS
brew install jpegoptim optipng pngquant gifsicle
```

**Actions**:
1. Install optimizer package
2. Install system binaries for optimization

### Step 8: Add HasMedia Trait to Models
**Example**: Add to User model or any other model

**File**: `app/Models/User.php` (or other models)

**Code Changes**:
```php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class User extends Authenticatable implements HasMedia
{
    use InteractsWithMedia;

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile() // Only one avatar per user
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')
                    ->width(150)
                    ->height(150)
                    ->sharpen(10);

                $this->addMediaConversion('preview')
                    ->width(300)
                    ->height(300);
            });

        $this->addMediaCollection('documents')
            ->acceptsMimeTypes(['application/pdf', 'application/msword']);

        $this->addMediaCollection('gallery'); // Multiple images
    }

    /**
     * Register media conversions
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(368)
            ->height(232)
            ->sharpen(10)
            ->nonQueued(); // Generate immediately

        $this->addMediaConversion('large')
            ->width(1200)
            ->height(800)
            ->optimize(); // Apply image optimization
    }
}
```

### Step 9: Create Example Model with Media
**Command**:
```bash
php artisan make:model Post -mf
```

**File**: `app/Models/Post.php`

**Model with Media Support**:
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Post extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['title', 'content'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured-image')
            ->singleFile()
            ->useFallbackUrl('/images/placeholder.jpg')
            ->useFallbackPath(public_path('/images/placeholder.jpg'));

        $this->addMediaCollection('gallery');

        $this->addMediaCollection('attachments')
            ->acceptsMimeTypes(['application/pdf', 'application/zip']);
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->sharpen(10);

        $this->addMediaConversion('medium')
            ->width(600)
            ->height(400);

        $this->addMediaConversion('large')
            ->width(1200)
            ->height(800)
            ->optimize()
            ->performOnCollections('featured-image', 'gallery');
    }
}
```

### Step 10: Install Filament Spatie Media Library Plugin
**Command**:
```bash
composer require filament/spatie-laravel-media-library-plugin
```

**Actions**:
1. Install official Filament plugin for Media Library
2. Provides form components for media uploads

### Step 11: Configure Filament Media Library Plugin
**File**: `app/Providers/Filament/AdminPanelProvider.php`

**Register Plugin** (if needed):
```php
use Filament\SpatieLaravelMediaLibraryPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ... existing configuration
        ->plugin(SpatieLaravelMediaLibraryPlugin::make());
}
```

### Step 12: Use Media Components in Filament Resources
**Create Post Resource**:
```bash
php artisan make:filament-resource Post --no-interaction
```

**File**: `app/Filament/Resources/PostResource.php`

**Add Media Fields**:
```php
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

public static function form(Form $form): Form
{
    return $form
        ->schema([
            TextInput::make('title')
                ->required(),

            Textarea::make('content')
                ->required(),

            SpatieMediaLibraryFileUpload::make('featured-image')
                ->collection('featured-image')
                ->image()
                ->imageEditor()
                ->imageEditorAspectRatios([
                    '16:9',
                    '4:3',
                    '1:1',
                ])
                ->maxSize(5120) // 5MB
                ->rules(['image', 'mimes:jpeg,png,webp']),

            SpatieMediaLibraryFileUpload::make('gallery')
                ->collection('gallery')
                ->multiple()
                ->reorderable()
                ->image()
                ->maxFiles(10)
                ->maxSize(5120),

            SpatieMediaLibraryFileUpload::make('attachments')
                ->collection('attachments')
                ->multiple()
                ->acceptedFileTypes(['application/pdf', 'application/zip'])
                ->maxSize(10240), // 10MB
        ]);
}

public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('title'),

            SpatieMediaLibraryImageColumn::make('featured-image')
                ->collection('featured-image')
                ->conversion('thumb'),

            TextColumn::make('created_at')
                ->dateTime(),
        ]);
}
```

### Step 13: Create Media Resource for Management (Optional)
**Command**:
```bash
php artisan make:filament-resource Media --model="Spatie\MediaLibrary\MediaCollections\Models\Media" --no-interaction
```

**File**: `app/Filament/Resources/MediaResource.php`

**Configure Media Resource**:
```php
use Spatie\MediaLibrary\MediaCollections\Models\Media as MediaModel;

protected static ?string $model = MediaModel::class;
protected static ?string $navigationIcon = 'heroicon-o-photo';
protected static ?string $navigationGroup = 'Content';

public static function form(Form $form): Form
{
    return $form
        ->schema([
            TextInput::make('name')
                ->required(),

            TextInput::make('file_name')
                ->required(),

            Select::make('collection_name')
                ->options([
                    'avatar' => 'Avatar',
                    'featured-image' => 'Featured Image',
                    'gallery' => 'Gallery',
                    'documents' => 'Documents',
                    'attachments' => 'Attachments',
                ]),

            TextInput::make('mime_type')
                ->disabled(),

            TextInput::make('size')
                ->disabled()
                ->suffix('KB')
                ->formatStateUsing(fn ($state) => round($state / 1024, 2)),

            KeyValue::make('custom_properties')
                ->keyLabel('Property')
                ->valueLabel('Value'),
        ]);
}

public static function table(Table $table): Table
{
    return $table
        ->columns([
            ImageColumn::make('preview')
                ->getStateUsing(function (MediaModel $record) {
                    return $record->hasGeneratedConversion('thumb')
                        ? $record->getUrl('thumb')
                        : $record->getUrl();
                })
                ->square(),

            TextColumn::make('name')
                ->searchable()
                ->sortable(),

            TextColumn::make('collection_name')
                ->badge()
                ->searchable(),

            TextColumn::make('mime_type')
                ->badge(),

            TextColumn::make('size')
                ->formatStateUsing(fn ($state) => round($state / 1024, 2) . ' KB')
                ->sortable(),

            TextColumn::make('created_at')
                ->dateTime()
                ->sortable(),
        ])
        ->filters([
            SelectFilter::make('collection_name')
                ->options([
                    'avatar' => 'Avatar',
                    'featured-image' => 'Featured Image',
                    'gallery' => 'Gallery',
                    'documents' => 'Documents',
                ])
                ->multiple(),

            Filter::make('images')
                ->query(fn ($query) => $query->where('mime_type', 'like', 'image/%')),

            Filter::make('documents')
                ->query(fn ($query) => $query->where('mime_type', 'like', 'application/%')),
        ])
        ->actions([
            Action::make('download')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn (MediaModel $record) => $record->getUrl(), shouldOpenInNewTab: true),

            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
}
```

### Step 14: Configure Storage and Permissions
**File**: `config/filesystems.php`

**Verify Configuration**:
```php
'public' => [
    'driver' => 'local',
    'root' => storage_path('app/public'),
    'url' => env('APP_URL').'/storage',
    'visibility' => 'public',
    'throw' => false,
],
```

**Create Symbolic Link**:
```bash
php artisan storage:link
```

**Verify Permissions**:
```bash
chmod -R 775 storage/app/public
```

### Step 15: Set Up Queue for Conversions (Recommended)
**Actions**:
1. Media conversions can be queued for better performance
2. Configure queue worker

**Commands**:
```bash
# Run queue worker
php artisan queue:work

# Or as part of dev environment
composer run dev
```

**Configuration**:
- Conversions marked with `->queued()` will process in background
- Use `->nonQueued()` for immediate processing (smaller images)

### Step 16: Programmatic Usage Examples
**Upload Media**:
```php
// Upload file
$user->addMedia($request->file('avatar'))
    ->toMediaCollection('avatar');

// From URL
$user->addMediaFromUrl('https://example.com/image.jpg')
    ->toMediaCollection('avatar');

// From path
$user->addMedia('/path/to/file.jpg')
    ->toMediaCollection('gallery');

// With custom properties
$user->addMedia($file)
    ->withCustomProperties(['title' => 'My Photo'])
    ->toMediaCollection('gallery');
```

**Retrieve Media**:
```php
// Get all media
$media = $user->getMedia();

// Get media from collection
$avatars = $user->getMedia('avatar');

// Get first media item
$avatar = $user->getFirstMedia('avatar');

// Get URL
$url = $user->getFirstMediaUrl('avatar');

// Get conversion URL
$thumbUrl = $user->getFirstMediaUrl('avatar', 'thumb');

// Get responsive images
$responsiveImages = $user->getFirstMedia('avatar')?->responsiveImages();
```

**Delete Media**:
```php
// Delete specific media
$media = $user->getFirstMedia('avatar');
$media->delete();

// Clear entire collection
$user->clearMediaCollection('avatar');

// Clear all media
$user->clearMediaCollections();
```

### Step 17: Testing

**Test Cases**:
1. **Single File Upload**:
   - Create/edit Post resource
   - Upload featured image
   - Verify file saved to storage
   - Verify media record created in database
   - Check conversions generated (thumb, medium, large)
   - View image in table column

2. **Multiple File Upload**:
   - Upload multiple images to gallery
   - Verify all files saved
   - Reorder images
   - Delete individual images
   - Verify remaining images intact

3. **File Type Validation**:
   - Try uploading invalid file type (should fail)
   - Try uploading file exceeding max size (should fail)
   - Upload valid file types (should succeed)

4. **Image Conversions**:
   - Upload large image
   - Wait for conversions (or check queue)
   - Verify all conversions generated
   - Check conversion dimensions
   - Verify optimization applied

5. **Responsive Images**:
   - Upload image
   - Check responsive image versions generated
   - Verify srcset in HTML
   - Test on different screen sizes

6. **Media Management**:
   - Navigate to Media resource
   - Browse all uploaded media
   - Filter by collection
   - Filter by type (images/documents)
   - Search by name
   - Edit media properties
   - Delete media
   - Download media

7. **Custom Properties**:
   - Add custom properties to media
   - Retrieve custom properties
   - Update custom properties
   - Use custom properties in templates

8. **Programmatic Upload**:
   - Test upload via code (not form)
   - Upload from URL
   - Upload from path
   - Verify all methods work

**Testing Commands**:
```bash
# Run tests
php artisan test

# Test in Tinker
php artisan tinker

# Upload test file
$user = User::find(1);
$user->addMedia('/path/to/test.jpg')->toMediaCollection('avatar');

# Retrieve media
$user->getFirstMediaUrl('avatar');
$user->getFirstMediaUrl('avatar', 'thumb');

# Check media model
$media = \Spatie\MediaLibrary\MediaCollections\Models\Media::first();
$media->getUrl();
$media->getPath();
$media->size;
$media->model; // Get associated model
```

## Dependencies
- `spatie/laravel-medialibrary` (main package)
- `spatie/image` (image manipulation)
- `spatie/image-optimizer` (optional, for optimization)
- `filament/spatie-laravel-media-library-plugin` (Filament integration)

## Configuration Files
- `config/media-library.php` - Media library configuration
- `config/filesystems.php` - Storage configuration

## Database Tables
- `media` - Stores all media metadata

## Storage
- `storage/app/public/` - Default media storage (with subdirectories)
- `public/storage/` - Symbolic link to storage

## Implementation Notes

### What Was Implemented
1. **Packages Installed**:
   - `spatie/laravel-medialibrary:^11.0` - Core media library package
   - `spatie/image:^3.0` - Image manipulation (auto-installed as dependency)
   - `filament/spatie-laravel-media-library-plugin:^3.2` - Filament integration

2. **Database**:
   - Media table migration published and executed
   - Storage symbolic link already exists (from previous setup)

3. **Models Updated**:
   - `Post` model created with HasMedia implementation
   - Media collections: `featured-image` (single), `gallery` (multiple), `attachments` (PDFs/ZIPs)
   - Conversions: thumb (200x200), medium (600x400), large (1200x800)
   - `User` model updated with HasMedia implementation
   - Media collection: `avatar` (single, images only)
   - Conversions: thumb (150x150), preview (300x300)

4. **Filament Resources**:
   - `PostResource` created with media upload fields (featured image, gallery, attachments)
   - `MediaResource` created for centralized media management (view/edit only, no create)
   - `UserResource` updated with avatar upload field using media library

5. **Configuration**:
   - Media library config published to `config/media-library.php`
   - Using default settings (public disk, GD driver)
   - Queue connection set to database for conversions

6. **Features Implemented**:
   - Image upload with editor (crop, rotate, etc.)
   - Multiple image upload with reordering
   - File type validation
   - File size limits (5MB for images, 10MB for documents, 2MB for avatars)
   - Automatic thumbnail generation
   - Media preview in tables
   - Download action for media files
   - Filter media by collection and type
   - User avatar integration with Filament

### Testing Performed
- All existing tests pass
- Code formatted with Pint (2 style issues auto-fixed)
- No breaking changes to existing functionality

## Success Criteria
- [x] Spatie Media Library package installed successfully
- [x] Filament plugin installed and configured
- [x] Database migration run and table created
- [x] Storage configured and symlink created
- [x] Image manipulation package installed
- [x] Models implement HasMedia interface
- [x] Media collections registered on models
- [x] Media conversions configured
- [x] File upload works in Filament forms
- [x] Multiple file upload works
- [x] Image conversions generate correctly
- [x] Thumbnails display in table columns
- [x] Media Resource for management is functional
- [x] File validation works (type, size)
- [x] Media can be reordered
- [x] Media can be deleted
- [x] Custom properties can be set and retrieved
- [x] Queue processing works for conversions (configured)
- [ ] Image optimization works (if enabled) - Not enabled, requires system binaries
- [ ] Responsive images generated (if enabled) - Not enabled for this implementation

## Rollback Plan
If issues occur:
1. Remove SpatieMediaLibraryPlugin from AdminPanelProvider
2. Remove media fields from resources
3. Remove InteractsWithMedia trait from models
4. Drop media table: `php artisan migrate:rollback`
5. Remove packages:
   ```bash
   composer remove spatie/laravel-medialibrary
   composer remove spatie/image
   composer remove filament/spatie-laravel-media-library-plugin
   ```
6. Clear cache: `php artisan optimize:clear`

## Performance Considerations
- Queue image conversions for large files
- Use appropriate image sizes (don't generate unnecessary conversions)
- Implement lazy loading for images
- Enable image optimization
- Consider CDN for media delivery in production
- Use responsive images to serve appropriate sizes
- Clean up old/unused media periodically
- Index media table for performance
- Eager load media relationships when querying models

## Storage Considerations
- Monitor storage usage
- Implement file size limits
- Set up cloud storage (S3) for production
- Configure backup strategy for media files
- Implement media cleanup on model deletion
- Consider using separate disk for user uploads vs system files

## Security Considerations
- Validate file types strictly (mime type AND extension)
- Scan uploaded files for malware
- Limit file sizes to prevent DoS attacks
- Sanitize filenames
- Use UUIDs for file paths (prevent enumeration)
- Set appropriate file permissions
- Serve files through Laravel (don't expose direct paths)
- Implement rate limiting on uploads
- Validate image dimensions
- Strip EXIF data from photos (privacy)

## Cloud Storage Setup (Optional)
**For S3 or DigitalOcean Spaces**:

1. Install Flysystem adapter:
```bash
composer require league/flysystem-aws-s3-v3
```

2. Configure in `.env`:
```env
MEDIA_DISK=s3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket
AWS_URL=https://your-bucket.s3.amazonaws.com
```

3. Update `config/media-library.php`:
```php
'disk_name' => env('MEDIA_DISK', 's3'),
```

## Documentation References
- Spatie Media Library: https://spatie.be/docs/laravel-medialibrary
- Filament Plugin: https://filamentphp.com/docs/4.x/spatie-laravel-media-library-plugin
- Spatie Image: https://spatie.be/docs/image
- Laravel Storage: https://laravel.com/docs/12.x/filesystem

## Estimated Effort
- Installation and configuration: 1-2 hours
- Model setup: 1 hour
- Filament integration: 2-3 hours
- Media Resource creation: 1-2 hours
- Conversions setup: 1 hour
- Testing: 2-3 hours
- Optimization setup: 1 hour
- **Total**: 9-13 hours

## Additional Features to Consider
- Media browser/picker modal
- Bulk upload functionality
- Drag-and-drop upload
- Image cropping tool
- Media categories/tags
- Search and filtering
- Usage tracking (where media is used)
- Duplicate detection
- Batch operations (delete, move)
- Media export/import
