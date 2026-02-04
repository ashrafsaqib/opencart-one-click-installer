# OpenCart Instant Installer 🛒

An automated PHP-based installer for OpenCart that streamlines the process of creating multiple OpenCart installations from pre-configured version sets. Perfect for developers who need to quickly deploy and test different OpenCart versions.

## 📋 Overview

This tool automates the entire OpenCart installation process:
- **Extracts** OpenCart ZIP files
- **Creates** MySQL databases automatically
- **Imports** pre-configured SQL dumps
- **Generates** environment-specific configuration files
- **Deploys** ready-to-use OpenCart installations in seconds

Instead of manually installing OpenCart each time, this installer allows you to set up multiple OpenCart versions with a single click by maintaining pre-built installation packages.

## ✨ Features

- **One-Click Installation**: Deploy OpenCart instances in seconds
- **Multi-Version Support**: Manage multiple OpenCart versions simultaneously
- **Automatic Database Creation**: Creates databases with the same name as your installation
- **Dynamic Configuration**: Auto-generates config files with proper paths and credentials
- **Template System**: Uses placeholder-based templates for easy environment customization
- **Clean UI**: Bootstrap-powered interface for easy operation
- **Error Handling**: Comprehensive validation and error reporting

## 📁 Project Structure

```
oci/
├── index.php                           # Main installer interface
├── sources/                            # Version packages directory
│   ├── 3041.zip                       # OpenCart 3.0.4.1 package
│   ├── 3041.sql                       # Pre-configured database dump
│   ├── 3041.config.template           # Frontend config template
│   ├── 3041.admin.config.template     # Admin config template
│   ├── oc23.zip                       # OpenCart 2.3 package
│   ├── oc23.sql
│   ├── oc23.config.template
│   └── oc23.admin.config.template
└── [installed-instances]/             # Created installations
    ├── shop_v3/                       # Example installation
    ├── test_site/
    └── ...
```

## 🚀 Quick Start

### Prerequisites

- PHP 7.4+ with MySQLi and ZipArchive extensions
- MySQL 5.7+ or MariaDB 10.2+
- Apache/Nginx with mod_rewrite enabled
- Write permissions on the project directory

### Installation

1. **Clone or download this project** to your web server directory:
   ```bash
   git clone <repository-url> oci
   cd oci
   ```

2. **Configure database credentials** by editing `index.php`:
   ```php
   define('INSTALLER_DB_HOST', 'localhost');
   define('INSTALLER_DB_USER', 'root');
   define('INSTALLER_DB_PASS', 'yourpassword');
   ```

3. **Prepare your version packages** (see [Adding New Versions](#adding-new-versions) below)

4. **Access the installer** via browser:
   ```
   http://localhost/oci/index.php
   ```

5. **Install OpenCart**:
   - Select a version from the dropdown
   - Enter a unique installation name (alphanumeric + underscores only)
   - Click "Install OpenCart"

## 🔧 Customizing for Your Environment

### 1. Update Database Credentials

Edit `index.php` (lines 20-22):
```php
define('INSTALLER_DB_HOST', 'localhost');     // Your DB host
define('INSTALLER_DB_USER', 'your_username'); // Your DB user
define('INSTALLER_DB_PASS', 'your_password'); // Your DB password
```

### 2. Update Configuration Templates

Each version requires two config templates that define paths and URLs. Update these to match your environment:

**Example: `sources/3041.config.template`** (Frontend)
```php
<?php
// HTTP
define('HTTP_SERVER', 'http://your-domain.local/oci/{name}/');

// HTTPS
define('HTTPS_SERVER', 'https://your-domain.local/oci/{name}/');

// DIR
define('DIR_APPLICATION', '/full/path/to/oci/{name}/catalog/');
define('DIR_SYSTEM', '/full/path/to/oci/{name}/system/');
define('DIR_IMAGE', '/full/path/to/oci/{name}/image/');
// ... other paths

// DB
define('DB_DRIVER', 'mysqli');
define('DB_HOSTNAME', 'localhost');
define('DB_USERNAME', 'your_username');
define('DB_PASSWORD', 'your_password');
define('DB_DATABASE', '{name}');
define('DB_PORT', '3306');
define('DB_PREFIX', 'oc_');
```

**Example: `sources/3041.admin.config.template`** (Admin)
```php
<?php
// HTTP
define('HTTP_SERVER', 'http://your-domain.local/oci/{name}/admin/');
define('HTTP_CATALOG', 'http://your-domain.local/oci/{name}/');

// HTTPS
define('HTTPS_SERVER', 'https://your-domain.local/oci/{name}/admin/');
define('HTTPS_CATALOG', 'https://your-domain.local/oci/{name}/');

// DIR
define('DIR_APPLICATION', '/full/path/to/oci/{name}/admin/');
define('DIR_CATALOG', '/full/path/to/oci/{name}/catalog/');
// ... other paths

// DB settings (same as frontend config)
```

### 3. Important Placeholders

The `{name}` placeholder is automatically replaced with your installation name. Make sure to include it in:
- All URL paths: `http://domain.local/oci/{name}/`
- All directory paths: `/path/to/oci/{name}/catalog/`
- Database name: `define('DB_DATABASE', '{name}');`

## ➕ Adding New Versions

To add a new OpenCart version to the installer, you need to create **4 files** with the same base name:

### Step-by-Step Process

**1. Choose a version identifier** (e.g., `oc40` for OpenCart 4.0)

**2. Create the ZIP file: `oc40.zip`**
   - Download OpenCart from official website
   - Extract and optionally pre-configure (install manually first)
   - ZIP the entire OpenCart directory
   - Place in `sources/` folder

**3. Create the SQL dump: `oc40.sql`**
   - Install OpenCart manually (or use existing installation)
   - Export the database using phpMyAdmin or command line:
     ```bash
     mysqldump -u root -p opencart_db > sources/oc40.sql
     ```
   - Make sure to include all tables and data
   - Remove any CREATE DATABASE or USE statements from the dump

**4. Create frontend config template: `oc40.config.template`**
   - Copy `config.php` from a working OpenCart installation
   - Replace installation-specific values with `{name}` placeholder:
     ```php
     // Before
     define('HTTP_SERVER', 'http://dev.local/oci/my_shop/');
     define('DB_DATABASE', 'my_shop');
     
     // After
     define('HTTP_SERVER', 'http://dev.local/oci/{name}/');
     define('DB_DATABASE', '{name}');
     ```
   - Update absolute paths to use `{name}` placeholder
   - Update database credentials to match your installer settings

**5. Create admin config template: `oc40.admin.config.template`**
   - Copy `admin/config.php` from a working OpenCart installation
   - Apply the same placeholder replacements
   - Ensure admin paths include `/admin/` suffix

### Example File Set

```
sources/
├── oc40.zip                    # OpenCart 4.0 installation files
├── oc40.sql                    # Pre-configured database dump
├── oc40.config.template        # Frontend configuration
└── oc40.admin.config.template  # Admin configuration
```

### Validation

After adding a new version:
1. Refresh the installer page
2. The new version should appear in the dropdown
3. If it doesn't appear, check:
   - All 4 files exist with matching names
   - Files are in the `sources/` directory
   - ZIP file is valid and contains OpenCart files
   - SQL file is valid MySQL dump

## 🎯 Usage Examples

### Basic Installation
1. Open installer: `http://localhost/oci/`
2. Select version: `3041`
3. Enter name: `my_store`
4. Click install
5. Access: `http://localhost/oci/my_store/`
6. Admin panel: `http://localhost/oci/my_store/admin/`

### Multiple Installations
You can create multiple instances of the same or different versions:
- `shop_v3` → OpenCart 3.0.4.1
- `shop_v2` → OpenCart 2.3
- `test_site` → OpenCart 3.0.4.1
- `demo_store` → OpenCart 4.0

Each installation has its own:
- Directory
- Database
- Configuration

## 🔍 How It Works

### Installation Process

1. **Validation**
   - Checks installation name (alphanumeric + underscores only)
   - Verifies directory doesn't exist
   - Confirms all required files are present

2. **Extraction**
   - Unzips OpenCart package to temporary directory
   - Handles both single-folder and multi-file ZIP structures
   - Renames/moves to target directory name

3. **Database Setup**
   - Creates new MySQL database (same name as installation)
   - Imports SQL dump with full schema and data
   - Handles multi-statement SQL files

4. **Configuration Generation**
   - Reads template files
   - Replaces `{name}` placeholder with installation name
   - Creates `config.php` and `admin/config.php` files

5. **Cleanup**
   - Removes temporary extraction directory
   - Displays success message with link to new installation

## 🛠️ Troubleshooting

### Common Issues

**"ZIP file missing" error**
- Ensure the ZIP file exists in `sources/` folder
- Check file naming matches exactly (case-sensitive)

**"Directory already exists" error**
- Choose a different installation name
- Or delete the existing directory manually

**"Database Connection Failed"**
- Verify database credentials in `index.php`
- Check MySQL service is running
- Ensure user has CREATE DATABASE privileges

**"SQL Error" during import**
- Check SQL dump is valid MySQL format
- Ensure dump doesn't contain CREATE DATABASE statements
- Verify SQL file isn't corrupted

**Config files not working**
- Verify paths in template files match your server setup
- Check `{name}` placeholder is used correctly
- Ensure database credentials in templates are correct

**Blank page or 500 error**
- Enable error reporting (already enabled in script)
- Check PHP error logs
- Verify file permissions are correct

### File Permissions

Ensure proper permissions for the installer to work:
```bash
chmod 755 oci/
chmod 755 oci/sources/
chmod 644 oci/index.php
```

After installation, you may need to set write permissions for OpenCart:
```bash
chmod -R 755 oci/my_store/image/
chmod -R 755 oci/my_store/system/storage/
```

## 📝 Advanced Configuration

### Custom Database Names

By default, the database name matches the installation name. To customize, modify the installer logic:

```php
// In index.php, around line 84
$dbName = $installName; // Change this to your custom logic
```

### Additional Placeholders

Add custom placeholders for more flexibility:

```php
// In index.php, around line 169
$replacements = [
    '{name}'        => $installName,
    '{admin_user}'  => 'admin',
    '{admin_email}' => 'admin@example.com',
    // Add more as needed
];
```

Then use them in your templates:
```php
define('ADMIN_EMAIL', '{admin_email}');
```

### Environment-Specific Templates

Create different template sets for different environments:
```
sources/
├── 3041.config.template.local
├── 3041.config.template.production
└── 3041.config.template.staging
```

Modify the installer to select templates based on environment variable.

## 🔒 Security Considerations

1. **Never expose this installer on production servers**
2. **Remove or restrict access** after development phase
3. **Use `.htaccess`** or server config to restrict access:
   ```apache
   # .htaccess
   Order Deny,Allow
   Deny from all
   Allow from 127.0.0.1
   Allow from ::1
   ```
4. **Don't commit sensitive credentials** to version control
5. **Use environment variables** for production setups

## 📚 Requirements

- **PHP**: 7.4 or higher
- **MySQL**: 5.7+ or MariaDB 10.2+
- **PHP Extensions**:
  - mysqli
  - zip
  - mbstring
- **Disk Space**: Varies by OpenCart version (typically 50-100 MB per installation)

## 🤝 Contributing

To contribute new version packages or improvements:
1. Test your version package thoroughly
2. Ensure all 4 files are properly configured
3. Document any version-specific requirements
4. Submit with clear naming conventions

## 📄 License

This installer script is provided as-is for development purposes. OpenCart itself is licensed under GPL-3.0.

## 🙋 Support

For issues related to:
- **This installer**: Check troubleshooting section above
- **OpenCart itself**: Visit [OpenCart Forums](https://forum.opencart.com/)
- **Version-specific issues**: Refer to OpenCart documentation for that version

## 📌 Version History

- **v1.0**: Initial release with basic installation functionality
- Supports OpenCart 2.x, 3.x, and 4.x versions
- Template-based configuration system
- Automated database creation and import

---

**Happy Installing! 🚀**
