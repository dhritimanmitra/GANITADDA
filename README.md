# Ganit Adda: PHP & MySQL website (Phase 1: Foundation)

## What is finished in Phase 1
- database.sql (14 tables with keys, indexes, timestamps; no sample revenue or testimonials)
- includes/init.php, database.php, functions.php, auth.php (security core)
- install.php (checks the server, builds the tables, creates your admin, writes config, deletes itself)
- .htaccess protections for includes/, storage/ and assets/uploads/

## What comes next (phases)
2. Public website: homepage, courses, worksheets, about, contact, cart, checkout (maroon/cream/gold)
3. Student register, login, dashboard, secure download.php
4. Admin panel: login, dashboard, courses, worksheets (PDF upload), content, settings
5. Admin: enquiries, students, orders, payments (manual verification, refunds), testimonials
6. Final testing and ZIP

## Install on cPanel
1. In cPanel, create a MySQL database, a user and a strong password; add the user to the database with all privileges.
2. Upload this folder's contents to public_html (File Manager → Upload, or upload the ZIP and Extract).
3. Open yourdomain.com/install.php, fill the form, press Install.
4. Log in at yourdomain.com/admin/login.php (this page arrives in Phase 4).

## Security notes
- Paid PDFs are saved one folder above public_html (ganit_private) when the host allows it; otherwise in storage/, which is blocked by .htaccess.
- Downloads will only go through an authorised PHP script after a verified payment.
- Never share includes/config.php.
