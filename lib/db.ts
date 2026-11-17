import { neon } from '@neondatabase/serverless';

if (!process.env.DATABASE_URL) {
  // Fail with a clear message only when a database-backed route is used.
}
export function sqlClient() {
  const connectionString = process.env.DATABASE_URL || process.env.POSTGRES_URL || process.env.NEON_DATABASE_URL || process.env.POSTGRES_URL_NON_POOLING;
  if (!connectionString) throw new Error('Neon connection string is missing. Add DATABASE_URL or POSTGRES_URL in Vercel Environment Variables.');
  return neon(connectionString);
}
