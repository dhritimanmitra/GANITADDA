# Ganit Adda — Next.js + Neon + Vercel Blob

This is a deployable foundation for the Ganit Adda learning website, rebuilt from the supplied `index(1).html` content and designed for a responsive mobile/desktop experience. Brand styling in this build uses the maroon, cream and gold direction.

## Included
- Responsive public website and worksheet catalogue
- Neon PostgreSQL schema for worksheets, enquiries and site settings
- Admin login using a bcrypt password hash and signed, HTTP-only session cookie
- Admin dashboard to upload, list and delete PDF worksheets
- PDF storage using Vercel Blob (not local disk)
- Public enquiry form saved to Neon
- Environment-variable based secrets; no credentials committed

## Not yet included
- Payment gateway checkout and verified payment/webhook flow
- Student accounts, paid-download entitlements, course/video lessons
- Admin enquiry management and settings editor
- Rate limiting, CAPTCHA, email notifications and full production security review

Do not collect payments until a verified server-side payment flow and refund/terms pages are implemented.

## 1. Local setup

Requirements: Node.js 20+, npm, a Neon database.

```bash
npm install
cp .env.example .env.local
```

Set `DATABASE_URL` or `POSTGRES_URL` to the Neon connection string (the app also accepts `NEON_DATABASE_URL` and `POSTGRES_URL_NON_POOLING`). Prefer the pooled connection string for the deployed app. Create a random session secret (at least 32 characters) and set `ADMIN_EMAIL`.

Generate a bcrypt password hash:

```bash
npm run admin:hash -- 'Use-A-Long-Unique-Password'
```

Copy the output into `ADMIN_PASSWORD_HASH` in `.env.local`.

Run the database setup:

```bash
npm run db:init
```

Connect Vercel Blob and set `BLOB_READ_WRITE_TOKEN` in `.env.local` before testing PDF uploads.

Start the website:

```bash
npm run dev
```

Open `http://localhost:3000` and `http://localhost:3000/admin`.

## 2. Deploy to Vercel

1. Push this project to a new GitHub repository.
2. In Vercel, import that repository and select the existing Ganit Adda project only after confirming you have a backup of the current deployment.
3. In Project → Storage, connect the Neon database and Vercel Blob store.
4. In Project → Settings → Environment Variables, add `DATABASE_URL`, `SESSION_SECRET`, `ADMIN_EMAIL`, `ADMIN_PASSWORD_HASH`, and `BLOB_READ_WRITE_TOKEN` for Production, Preview and Development as appropriate.
5. Deploy. Run `npm run db:init` once from a trusted local terminal with the production Neon URL, or run the schema statements in Neon SQL Editor.
6. Test `/`, `/admin`, login, worksheet upload, public worksheet visibility, and enquiry submission.

## 3. Important security notes
- Never put `DATABASE_URL`, `BLOB_READ_WRITE_TOKEN`, `SESSION_SECRET` or the admin password in client-side code.
- Never send secrets in screenshots or chat.
- This starter does not implement payment processing. Do not treat a UPI screenshot or browser redirect as proof of payment.
- Use a verified payment provider webhook before granting paid downloads.
- The Blob uploads in this starter are public URLs; upload only materials intended for public access. For paid/private PDFs, switch to private object storage and short-lived authenticated downloads before launch.
- Add rate limiting and spam protection before publishing the enquiry form widely.
