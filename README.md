# Microblog - A Simple PHP Blogging Platform

A lightweight microblogging platform built with PHP and SQLite, featuring an admin panel, image uploads, and customizable settings—all contained within a single `index.php` file.

## Table of Contents

- [Features](#features)
- [Demo](#demo)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [Security Considerations](#security-considerations)
- [Customization](#customization)
- [Contributing](#contributing)
- [License](#license)

## Features

- **Single File Deployment**: Entire application runs from a single `index.php` file.
- **SQLite Database**: Uses SQLite for data storage, eliminating the need for a separate database server.
- **Admin Panel**: Manage posts, pages, comments, navbar links, and site settings from an intuitive admin interface.
- **Image Uploads**: Supports featured images for posts and pages, with automatic resizing and storage.
- **Pagination and Infinite Scroll**: Choose between pagination, infinite scroll, or displaying all posts on the homepage.
- **Responsive Design**: Mobile-friendly layout using Bootstrap, with adaptive navbar and content display.
- **Customizable Navbar**: Add, edit, or remove navbar links from the admin panel, including target settings (same tab or new tab).
- **User Comments**: Enable visitors to leave comments on posts, with admin approval required for publication.
- **SEO-Friendly URLs**: Clean URL structure for posts and pages.
- **Favicon Support**: Upload a custom favicon for your site.
- **Security Features**: Basic input sanitization and password hashing.

## Demo

Not available right now.

## Requirements

- PHP 7.0 or higher
- SQLite PDO extension enabled
- Web server (Apache, Nginx, etc.)
- File write permissions for the application directory

## Installation

1. **Download the Repository**

   Clone the repository or download the `index.php` file directly.

   ```bash
   git clone https://github.com/yourusername/microblog.git
   ```

2. **Upload to Server**

   Place the `index.php` file in the root directory of your web server.

3. **Set Permissions**

   Ensure the web server has write permissions for the following directories and files:

   - `microblog.db` (the SQLite database file)
   - `uploads/` directory and its subdirectories (`posts/`, `pages/`, `favicon/`)

   ```bash
   chmod -R 0777 uploads
   chmod 0666 microblog.db
   ```

4. **Access the Application**

   Navigate to your site's URL to view the homepage.

   ```
   http://yourdomain.com/
   ```

5. **Admin Login**

   - Default admin username: `admin`
   - Default admin password: `admin`
   - Access the admin panel:

     ```
     http://yourdomain.com/login
     ```

   **Important:** After logging in, change the admin username and password from the Admin Settings tab for security purposes.

## Usage

### Admin Panel

Access the admin panel to manage your site content and settings.

- **Posts**

  - Add, edit, or delete blog posts.
  - Upload featured images for posts.
  - Posts support comments from visitors.

- **Pages**

  - Create static pages (e.g., About, Contact).
  - Pages do not have comment sections.
  - Upload featured images for pages.

- **Comments**

  - Review, approve, or delete visitor comments on posts.

- **Navbar Links**

  - Add or remove links in the navigation bar.
  - Specify link target: same tab (`_self`) or new tab (`_blank`).

- **Settings**

  - Update site logo, description, footer text, and favicon.
  - Choose homepage display option: Pagination, Infinite Scroll, or Show All.
  - Upload a custom favicon.

- **Admin Settings**

  - Change admin username and password.

### Adding Content

1. **Create a Post**

   - Navigate to the Posts tab.
   - Fill in the Title and Content fields.
   - Upload a featured image (optional).
   - Click **Add** to publish the post.

2. **Create a Page**

   - Navigate to the Pages tab.
   - Fill in the Title and Content fields.
   - Upload a featured image (optional).
   - Click **Add** to publish the page.

### Managing Comments

- Review new comments in the Comments tab.
- Approve comments to make them visible on the site.
- Delete inappropriate comments.

### Customizing Navbar

- Add new links by specifying the Name, Link, and Target.
- Delete existing links as needed.

### Visitor Interaction

- Visitors can read posts and pages.
- On posts, visitors can leave comments, which require admin approval.
- The site adapts to mobile devices with a responsive design.

## Security Considerations

- **Change Default Admin Credentials**

  Update the admin username and password immediately after installation.

- **Input Sanitization**

  While basic sanitization is implemented, consider enhancing validation and sanitization to prevent SQL injection and XSS attacks.

- **File Uploads**

  - Only certain file types are allowed for uploads.
  - Uploaded images are stored in designated directories.
  - Ensure the `uploads/` directory is not publicly writable.

- **Regular Updates**

  Keep your PHP version and extensions up to date to patch security vulnerabilities.

## Customization

- **Adjust Image Dimensions**

  Modify the CSS classes `.card-img-top` and `.featured-image-fixed` in the `<style>` section to change image dimensions.

- **Modify Styles**

  Utilize the Bootstrap framework to customize the look and feel of your site.

- **Enhance Features**

  Since the application is contained in a single file, you can easily add new features or modify existing ones.

## Contributing

Contributions are welcome! Please fork the repository and submit a pull request.

1. Fork the repository.
2. Create a new branch:

   ```bash
   git checkout -b feature-name
   ```

3. Commit your changes:

   ```bash
   git commit -m "Description of changes"
   ```

4. Push to the branch:

   ```bash
   git push origin feature-name
   ```

5. Open a pull request.

## License

This project is licensed under the [MIT License](LICENSE).
